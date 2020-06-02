<?php

declare(strict_types=1);

namespace CoRex\Container;

use Closure;
use CoRex\Container\Exceptions\NotFoundException;
use CoRex\Container\Helpers\Definition;
use Exception;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionFunction;
use ReflectionObject;
use ReflectionParameter;
use Throwable;

class Container implements ContainerInterface
{
    /** @var Container */
    private static $instance;

    /** @var Definition[] */
    private $definitions;

    /** @var object[] */
    private $instances;

    /**
     * Container.
     */
    public function __construct()
    {
        $this->clear();
    }

    /**
     * Get instance.
     *
     * @return Container
     */
    public static function getInstance(): self
    {
        if (!is_object(self::$instance)) {
            self::$instance = new static();
        }

        return self::$instance;
    }

    /**
     * Clear.
     */
    public function clear(): void
    {
        $this->definitions = [];
        $this->instances = [];
    }

    /**
     * Bind.
     *
     * @param string $abstract
     * @param string|Closure|null $concrete
     * @param bool $shared
     * @return Definition
     * @throws NotFoundException
     */
    public function bind(string $abstract, $concrete = null, bool $shared = false): Definition
    {
        if ($concrete === null) {
            $concrete = $abstract;
        }

        $definition = new Definition($abstract, $concrete, $shared);
        $this->definitions[$abstract] = $definition;

        // Check if already bound.
        if ($this->resolved($abstract)) {
            unset($this->instances[$abstract]);
            $this->make($abstract);
        }

        return $definition;
    }

    /**
     * Bind if not bound.
     *
     * @param string $abstract
     * @param string|Closure|null $concrete
     * @param bool $shared
     * @return Definition
     * @throws NotFoundException
     */
    public function bindIf(string $abstract, $concrete = null, bool $shared = false): Definition
    {
        if (!$this->has($abstract)) {
            return $this->bind($abstract, $concrete, $shared);
        }

        return $this->getDefinition($abstract);
    }

    /**
     * Bind singleton.
     *
     * @param string $abstract
     * @param string|Closure|null $concrete
     * @return Definition
     * @throws NotFoundException
     */
    public function bindSingleton(string $abstract, $concrete = null): Definition
    {
        return $this->bind($abstract, $concrete, true);
    }

    /**
     * Bind singleton if not bound.
     *
     * @param string $abstract
     * @param string|Closure|null $concrete
     * @return Definition
     * @throws NotFoundException
     */
    public function bindSingletonIf(string $abstract, $concrete = null): Definition
    {
        if (!$this->has($abstract)) {
            return $this->bindSingleton($abstract, $concrete);
        }

        return $this->getDefinition($abstract);
    }

    /**
     * Bind shared.
     *
     * @param string $abstract
     * @param string|Closure|null $concrete
     * @return Definition
     * @throws NotFoundException
     */
    public function bindShared(string $abstract, $concrete = null): Definition
    {
        return $this->bind($abstract, $concrete, true);
    }

    /**
     * Bind shared.
     *
     * @param string $abstract
     * @param string|Closure|null $concrete
     * @return Definition
     * @throws NotFoundException
     */
    public function bindSharedIf(string $abstract, $concrete = null): Definition
    {
        if (!$this->has($abstract)) {
            return $this->bindShared($abstract, $concrete);
        }

        return $this->getDefinition($abstract);
    }

    /**
     * Is shared.
     *
     * @param string $abstract
     * @return bool
     */
    public function isShared(string $abstract): bool
    {
        if ($this->has($abstract)) {
            return $this->getDefinition($abstract)->isShared();
        }

        return false;
    }

    /**
     * Is singleton.
     *
     * @param string $abstract
     * @return bool
     */
    public function isSingleton(string $abstract): bool
    {
        return $this->isShared($abstract);
    }

    /**
     * Make.
     *
     * @param string $abstractOrConcrete
     * @param mixed[] $parameters [name => value].
     * @return object
     * @throws NotFoundException
     */
    public function make(string $abstractOrConcrete, array $parameters = []): object
    {
        // Get definition details.
        $definition = $this->getDefinition($abstractOrConcrete);
        $isShared = false;
        $concrete = null;
        if ($definition !== null) {
            $isShared = $definition->isShared();
            $concrete = $definition->getConcrete();
        }

        // Validate classes.
        if ($concrete === null) {
            $concrete = $abstractOrConcrete;
        }

        // If shared and has instance, return it.
        if ($isShared && $this->resolved($abstractOrConcrete)) {
            return $this->instances[$abstractOrConcrete];
        }

        if (is_callable($concrete)) {
            // Resolve and create instance.
            try {
                $reflectionFunction = new ReflectionFunction($concrete);
                $reflectionParameters = $reflectionFunction->getParameters();
                $resolvedParameters = $this->resolve($reflectionParameters, $parameters);
                $instance = call_user_func_array($concrete, $resolvedParameters);

                // Validate object.
                if (!is_object($instance)) {
                    throw new Exception('Class ' . $abstractOrConcrete . ' does not exist.');
                }
            } catch (Throwable $throwable) {
                throw new NotFoundException($throwable->getMessage());
            }
        } else {
            // Resolve and create instance.
            try {
                $reflectionClass = new ReflectionClass($concrete);
                $constructor = $reflectionClass->getConstructor();
                $reflectionParameters = $constructor !== null ? $constructor->getParameters() : [];
                $resolvedParameters = $this->resolve($reflectionParameters, $parameters, $abstractOrConcrete);
                $instance = $this->newInstance($concrete, $resolvedParameters);
            } catch (Throwable $throwable) {
                throw new NotFoundException($throwable->getMessage());
            }
        }

        // If shared, store instance.
        if ($isShared) {
            $this->instances[$abstractOrConcrete] = $instance;
        }

        return $instance;
    }

    /**
     * Finds an entry of the container by its identifier and returns it.
     *
     * @param string $id Identifier of the entry to look for.
     * @return mixed Entry.
     * @throws NotFoundException
     */
    public function get($id)
    {
        try {
            if ($this->has($id)) {
                return $this->make($id);
            }
        } catch (Throwable $throwable) {
            // Do nothing since NotFoundException is thrown later.
        }

        throw new NotFoundException('No entry was found for ' . $id . ' identifier.');
    }

    /**
     * Returns true if the container can return an entry for the given identifier.
     * Returns false otherwise.
     *
     * `has($id)` returning true does not mean that `get($id)` will not throw an exception.
     * It does however mean that `get($id)` will not throw a `NotFoundExceptionInterface`.
     *
     * @param string $id Identifier of the entry to look for.
     * @return bool
     */
    public function has($id): bool
    {
        return array_key_exists($id, $this->definitions);
    }

    /**
     * Call method on abstract/object.
     *
     * @param string|object $abstractOrObject
     * @param string $method
     * @param mixed[] $parameters [name => value].
     * @return mixed
     * @throws NotFoundException
     */
    public function call($abstractOrObject, string $method, array $parameters = [])
    {
        if (!is_object($abstractOrObject)) {
            $abstractOrObject = $this->make($abstractOrObject);
        }

        try {
            $reflectionObject = new ReflectionObject($abstractOrObject);
            $reflectionMethod = $reflectionObject->getMethod($method);
            $reflectionParameters = $reflectionMethod->getParameters();
        } catch (Throwable $throwable) {
            throw new NotFoundException($throwable->getMessage());
        }

        $resolvedParameters = $this->resolve($reflectionParameters, $parameters);

        return call_user_func_array([$abstractOrObject, $method], $resolvedParameters);
    }

    /**
     * Forget.
     *
     * @param string $abstract
     */
    public function forget(string $abstract): void
    {
        if ($this->has($abstract)) {
            unset($this->definitions[$abstract]);
        }

        if ($this->resolved($abstract)) {
            unset($this->instances[$abstract]);
        }
    }

    /**
     * Get definitions.
     *
     * @return Definition[] [abstract => Definition[]
     */
    public function getDefinitions(): array
    {
        return $this->definitions;
    }

    /**
     * Get definition.
     *
     * @param string $abstract
     * @return Definition|null
     */
    public function getDefinition(string $abstract): ?Definition
    {
        if ($this->has($abstract)) {
            return $this->definitions[$abstract];
        }

        return null;
    }

    /**
     * Get abstracts.
     *
     * @return string[]
     */
    public function getAbstracts(): array
    {
        return array_keys($this->definitions);
    }

    /**
     * Get instances.
     *
     * @return object[] [abstract => object]
     */
    public function getInstances(): array
    {
        return $this->instances;
    }

    /**
     * Set.
     *
     * @param string $abstract
     * @param object $object
     * @throws NotFoundException
     */
    public function set(string $abstract, object $object): void
    {
        if (!$this->has($abstract)) {
            $this->bindSingleton($abstract, get_class($object));
        }

        $this->getDefinition($abstract)->setShared();
        $this->instances[$abstract] = $object;
    }

    /**
     * Resolved.
     *
     * @param string $abstract
     * @return bool
     */
    public function resolved(string $abstract): bool
    {
        return array_key_exists($abstract, $this->instances) && is_object($this->instances[$abstract]);
    }

    /**
     * Tag.
     *
     * @param string $abstract
     * @param string|null $tag
     * @return bool
     */
    public function tag(string $abstract, ?string $tag): bool
    {
        $definition = $this->getDefinition($abstract);
        if ($definition !== null) {
            $definition->setTag($tag);

            return true;
        }

        return false;
    }

    /**
     * Run on extends.
     *
     * @param string $extends
     * @param string $method
     * @param mixed[] $parameters
     * @param bool $make
     * @throws NotFoundException
     */
    public function runOnExtends(string $extends, string $method, array $parameters = [], bool $make = false): void
    {
        $instances = [];
        $definitions = $this->getDefinitions();
        foreach ($definitions as $abstract => $definition) {
            if (!$definition->extendsClass($extends)) {
                continue;
            }

            $instance = $this->getAbstractInstance($abstract, $make);
            if ($instance !== null) {
                $instances[] = $instance;
            }
        }

        $this->runOnInstances($instances, $method, $parameters);
    }

    /**
     * Run on interface.
     *
     * @param string $interface
     * @param string $method
     * @param mixed[] $parameters
     * @param bool $make
     * @throws NotFoundException
     */
    public function runOnInterface(string $interface, string $method, array $parameters = [], bool $make = false): void
    {
        $instances = [];
        $definitions = $this->getDefinitions();
        foreach ($definitions as $abstract => $definition) {
            if (!$definition->implementsInterface($interface)) {
                continue;
            }

            $instance = $this->getAbstractInstance($abstract, $make);
            if ($instance !== null) {
                $instances[] = $instance;
            }
        }

        $this->runOnInstances($instances, $method, $parameters);
    }

    /**
     * Run on tag.
     *
     * @param string $tag
     * @param string $method
     * @param mixed[] $parameters
     * @param bool $make
     * @throws NotFoundException
     */
    public function runOnTag(string $tag, string $method, array $parameters = [], bool $make = false): void
    {
        $instances = [];
        $definitions = $this->getDefinitions();
        foreach ($definitions as $abstract => $definition) {
            if ($definition->getTag() !== $tag) {
                continue;
            }

            $instance = $this->getAbstractInstance($abstract, $make);
            if ($instance !== null) {
                $instances[] = $instance;
            }
        }

        $this->runOnInstances($instances, $method, $parameters);
    }

    /**
     * Run on instances.
     *
     * @param object[] $instances
     * @param string $method
     * @param mixed[] $parameters
     */
    private function runOnInstances(array $instances, string $method, array $parameters = []): void
    {
        if (count($instances) > 0) {
            foreach ($instances as $instance) {
                if (method_exists($instance, $method)) {
                    call_user_func_array([$instance, $method], $parameters);
                }
            }
        }
    }

    /**
     * Resolve.
     *
     * @param ReflectionParameter[] $reflectionParameters
     * @param mixed[] $parameters
     * @param string|null $abstract
     * @return mixed[] [name => value]
     * @throws NotFoundException
     */
    private function resolve(array $reflectionParameters, array $parameters = [], ?string $abstract = null): array
    {
        if (count($reflectionParameters) === 0) {
            return [];
        }

        // Get definition parameters.
        $definitionParameters = [];
        if ($abstract !== null) {
            $definition = $this->getDefinition($abstract);
            if ($definition !== null) {
                $definitionParameters = $definition->getParameters();
            }
        }

        // Resolve type-hint parameters.
        $resolvedParameters = [];
        foreach ($reflectionParameters as $reflectionParameter) {
            $name = $reflectionParameter->getName();
            $hasDefaultValue = $reflectionParameter->isDefaultValueAvailable();

            // Extract type hint.
            $typeHint = null;
            $reflectionParameterClass = $reflectionParameter->getClass();
            if ($reflectionParameterClass !== null) {
                $typeHint = $reflectionParameterClass->getName();
            }

            // Handle values in order typehint, specified parameters, default value.
            $value = null;
            if ($typeHint !== null) {
                if ($typeHint === ContainerInterface::class) {
                    $value = $this;
                } else {
                    $value = $this->make($typeHint);
                }
            } elseif (array_key_exists($name, $definitionParameters) && !$definitionParameters[$name]->isForced()) {
                $value = $definitionParameters[$name]->getValue();
            } elseif ($hasDefaultValue) {
                $value = call_user_func([$reflectionParameter, 'getDefaultValue']);
            }

            // Override parameter if specified.
            if (array_key_exists($name, $parameters)) {
                $value = $parameters[$name];
            }

            $resolvedParameters[$name] = $value;
        }

        // Set forced parameters.
        if (count($definitionParameters) > 0) {
            foreach ($definitionParameters as $name => $parameter) {
                if (!$parameter->isForced()) {
                    continue;
                }

                if (array_key_exists($name, $resolvedParameters)) {
                    $resolvedParameters[$name] = $parameter->getValue();
                }
            }
        }

        return $resolvedParameters;
    }

    /**
     * Get abstract instance.
     *
     * @param string $abstract
     * @param bool $make
     * @return object
     * @throws NotFoundException
     */
    private function getAbstractInstance(string $abstract, bool $make = false): ?object
    {
        $instance = null;

        // Get instance if exists.
        if ($this->resolved($abstract)) {
            $instance = $this->instances[$abstract];
        }

        // If not instantiated, instantiate.
        if ($instance === null && $make) {
            $instance = $this->make($abstract);
        }

        return $instance;
    }

    /**
     * New instance.
     *
     * @param string $class
     * @param mixed[] $params
     * @return object
     * @throws NotFoundException
     */
    private function newInstance(string $class, array $params): object
    {
        try {
            $reflectionClass = new ReflectionClass($class);

            return $reflectionClass->newInstanceArgs($params);
        } catch (Throwable $throwable) {
            throw new NotFoundException($throwable->getMessage());
        }
    }
}