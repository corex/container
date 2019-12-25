<?php

declare(strict_types=1);

namespace CoRex\Container;

use CoRex\Container\Exceptions\ContainerException;
use CoRex\Container\Exceptions\NotFoundException;
use CoRex\Container\Helpers\Definition;
use CoRex\Container\Helpers\Parameter;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionObject;
use ReflectionParameter;

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
     * @param string|null $concrete
     * @param bool $shared
     * @return Definition
     * @throws ContainerException
     */
    public function bind(string $abstract, ?string $concrete = null, bool $shared = false): Definition
    {
        // Check if already bound.
        if ($this->has($abstract)) {
            throw new ContainerException('Abstract ' . $abstract . ' already bound.');
        }

        if ($concrete === null) {
            $concrete = $abstract;
        }

        $definition = new Definition($abstract, $concrete, $shared);
        $this->definitions[$abstract] = $definition;

        return $definition;
    }

    /**
     * Bind singleton.
     *
     * @param string $abstract
     * @param string|null $concrete
     * @return Definition
     * @throws ContainerException
     */
    public function bindSingleton(string $abstract, ?string $concrete = null): Definition
    {
        return $this->bind($abstract, $concrete, true);
    }

    /**
     * Bind shared.
     *
     * @param string $abstract
     * @param string|null $concrete
     * @return Definition
     * @throws ContainerException
     */
    public function bindShared(string $abstract, ?string $concrete = null): Definition
    {
        return $this->bind($abstract, $concrete, true);
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
     * @throws ContainerException
     * @throws ReflectionException
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

        // CHeck if concrete class exists.
        if (!class_exists($concrete)) {
            throw new ContainerException('Class ' . $concrete . ' does not exist.');
        }

        // If shared and has instance, return it.
        if ($isShared && $this->hasInstance($abstractOrConcrete)) {
            return $this->instances[$abstractOrConcrete];
        }

        // Resolve and create instance.
        $reflectionClass = new ReflectionClass($concrete);
        $constructor = $reflectionClass->getConstructor();
        $reflectionParameters = $constructor !== null ? $constructor->getParameters() : [];
        $definitionParameters = $definition !== null ? $definition->getParameters() : [];
        $resolvedParameters = $this->resolve($reflectionParameters, $parameters, $definitionParameters);
        $instance = $this->newInstance($concrete, $resolvedParameters);

        // If shared, store instance.
        if ($isShared) {
            $this->instances[$abstractOrConcrete] = $instance;
        }

        return $instance;
    }

    /**
     * @inheritDoc
     * @throws NotFoundException
     * @throws ContainerException
     * @throws ReflectionException
     */
    public function get($id)
    {
        if ($this->has($id)) {
            return $this->make($id);
        }

        throw new NotFoundException($id);
    }

    /**
     * @inheritDoc
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
     * @throws ContainerException
     * @throws ReflectionException
     */
    public function call($abstractOrObject, string $method, array $parameters = [])
    {
        if (!is_object($abstractOrObject)) {
            $abstractOrObject = $this->make($abstractOrObject);
        }

        $reflectionObject = new ReflectionObject($abstractOrObject);
        $reflectionMethod = $reflectionObject->getMethod($method);
        $reflectionParameters = $reflectionMethod->getParameters();

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

        if ($this->hasInstance($abstract)) {
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
     * Has instance.
     *
     * @param string $abstract
     * @return bool
     */
    public function hasInstance(string $abstract): bool
    {
        return array_key_exists($abstract, $this->instances) && is_object($this->instances[$abstract]);
    }

    /**
     * Set instance.
     *
     * @param string $abstract
     * @param object $object
     * @throws ContainerException
     */
    public function setInstance(string $abstract, object $object): void
    {
        if (!$this->has($abstract)) {
            $this->bindSingleton($abstract, get_class($object));
        }

        $this->getDefinition($abstract)->setShared();
        $this->instances[$abstract] = $object;
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
     * @throws ContainerException
     * @throws ReflectionException
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
     * @throws ContainerException
     * @throws ReflectionException
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
     * @throws ContainerException
     * @throws ReflectionException
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
     * @param Parameter[] $definitionParameters
     * @return mixed[] [name => value]
     * @throws ContainerException
     * @throws ReflectionException
     */
    private function resolve(
        array $reflectionParameters,
        array $parameters = [],
        array $definitionParameters = []
    ): array {
        if (count($reflectionParameters) === 0) {
            return [];
        }

        // Resolve typehint parameters.
        $resolvedParameters = [];
        foreach ($reflectionParameters as $reflectionParameter) {
            $name = $reflectionParameter->getName();
            $hasDefaultValue = $reflectionParameter->isDefaultValueAvailable();
            $defaultValue = $hasDefaultValue
                ? $reflectionParameter->getDefaultValue()
                : null;

            // Extract type hint.
            $typeHint = null;
            $reflectionParameterClass = $reflectionParameter->getClass();
            if ($reflectionParameterClass !== null) {
                $typeHint = $reflectionParameterClass->getName();
            }

            // Handle values in order typehint, specified parameters, default value.
            $value = null;
            if ($typeHint !== null) {
                $value = $this->make($typeHint);
            } elseif ($hasDefaultValue) {
                if (array_key_exists($name, $definitionParameters) && !$definitionParameters[$name]->isForced()) {
                    $value = $definitionParameters[$name]->getValue();
                } else {
                    $value = $defaultValue;
                }
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
     * @throws ContainerException
     * @throws ReflectionException
     */
    private function getAbstractInstance(string $abstract, bool $make = false): ?object
    {
        $instance = null;

        // Get instance if exists.
        if ($this->hasInstance($abstract)) {
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
     * @throws ContainerException
     */
    private function newInstance(string $class, array $params): object
    {
        try {
            $reflectionClass = new ReflectionClass($class);

            return $reflectionClass->newInstanceArgs($params);
        } catch (ReflectionException $e) {
            throw new ContainerException($e->getMessage(), $e->getCode(), $e);
        }
    }
}