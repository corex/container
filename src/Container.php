<?php

declare(strict_types=1);

namespace CoRex\Container;

use CoRex\Container\Exceptions\ContainerException;
use CoRex\Container\Exceptions\NotFoundException;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionParameter;
use Throwable;

class Container implements ContainerInterface
{
    private ContainerBuilderInterface $containerBuilder;

    /** @var array<string, object> */
    private array $instances = [];

    public function __construct(?ContainerBuilderInterface $containerBuilder = null)
    {
        $this->containerBuilder = $containerBuilder ?? new ContainerBuilder();
    }

    /** @inheritDoc */
    public function make(string $idOrClass, array $arguments = []): object
    {
        $definition = $this->has($idOrClass) ? $this->containerBuilder->getDefinition($idOrClass) : null;
        $isShared = $definition !== null && $definition->isShared();

        // If shared and instance resolved, return it.
        if ($isShared && array_key_exists($idOrClass, $this->instances)) {
            return $this->instances[$idOrClass];
        }

        if ($definition !== null) {
            $class = $definition->getClass();
            $arguments = array_merge($definition->getArguments(), $arguments);
        } else {
            $class = $idOrClass;
        }

        if (!class_exists($class)) {
            throw new NotFoundException(sprintf('%s not found.', $class));
        }

        $reflectionParameters = $this->getReflectionParametersFromClass($class);
        $resolvedParameters = $this->resolveReflectionParameters($idOrClass, $reflectionParameters, $arguments);

        $instance = $this->newInstance($class, $resolvedParameters);

        // If shared, store resolved instance.
        if ($isShared) {
            $this->instances[$idOrClass] = $instance;
        }

        return $instance;
    }

    /** @inheritDoc */
    public function get(string $id)
    {
        if (!$this->has($id)) {
            throw new NotFoundException('No entry was found for ' . $id . ' identifier.');
        }

        return $this->make($id);
    }

    public function has(string $id): bool
    {
        return $this->containerBuilder->has($id);
    }

    /**
     * @param class-string $class
     * @param array<int|string, mixed> $params
     * @return object
     */
    private function newInstance(string $class, array $params): object
    {
        if (!class_exists($class)) {
            throw new NotFoundException(sprintf('%s not found.', $class));
        }

        try {
            return (new ReflectionClass($class))->newInstanceArgs($params);
        } catch (Throwable $throwable) {
            throw new ContainerException($throwable->getMessage(), $throwable->getCode(), $throwable);
        }
    }

    /**
     * @param array<ReflectionParameter> $reflectionParameters
     * @param array<int|string, mixed> $arguments
     * @return array<int|string, mixed>
     */
    private function resolveReflectionParameters(
        string $idOrClass,
        array $reflectionParameters,
        array $arguments = []
    ): array {
        if (count($reflectionParameters) === 0) {
            return [];
        }

        // Resolve type-hint parameters.
        try {
            $resolvedParameters = [];
            foreach ($reflectionParameters as $reflectionParameter) {
                $name = $reflectionParameter->getName();
                $hasDefaultValue = $reflectionParameter->isDefaultValueAvailable();

                // Extract type hint.
                $typeHint = null;
                /** @var ReflectionNamedType|null $reflectionParameterType */
                $reflectionParameterType = $reflectionParameter->getType();
                if ($reflectionParameterType !== null) {
                    $typeHint = $reflectionParameterType->getName();
                }

                // Handle values in order typehint, specified parameters, default value.
                $value = null;
                if ($typeHint !== null) {
                    if ($typeHint === ContainerInterface::class) {
                        $value = $this;
                    } elseif (class_exists($typeHint) || interface_exists($typeHint)) {
                        $value = $this->make($typeHint);
                    } elseif (array_key_exists($name, $arguments)) {
                        $value = $arguments[$name];
                    } elseif ($hasDefaultValue) {
                        $value = $reflectionParameter->getDefaultValue();
                    } else {
                        throw new ContainerException(
                            sprintf(
                                '"%s %s" could not be resolved for id/class "%s".',
                                $typeHint,
                                $name,
                                $idOrClass
                            )
                        );
                    }
                } elseif ($hasDefaultValue) {
                    $value = $reflectionParameter->getDefaultValue();
                } else {
                    throw new ContainerException(
                        sprintf(
                            '"%s" could not be resolved for id/class "%s".',
                            $name,
                            $idOrClass
                        )
                    );
                }

                $resolvedParameters[$name] = $value;
            }
        } catch (Throwable $throwable) {
            throw new ContainerException($throwable->getMessage(), $throwable->getCode(), $throwable);
        }

        return $resolvedParameters;
    }

    /**
     * @param class-string $class
     * @return array<ReflectionParameter>
     * @throws ContainerException
     * @throws NotFoundException
     */
    private function getReflectionParametersFromClass(string $class): array
    {
        try {
            $constructor = (new ReflectionClass($class))->getConstructor();

            return $constructor !== null ? $constructor->getParameters() : [];
        } catch (Throwable $throwable) {
            throw new ContainerException($throwable->getMessage(), $throwable->getCode(), $throwable);
        }
    }
}