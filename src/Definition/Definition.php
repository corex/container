<?php

declare(strict_types=1);

namespace CoRex\Container\Definition;

use Closure;
use CoRex\Container\Exceptions\ContainerException;
use CoRex\Container\Exceptions\FactoryException;
use ReflectionMethod;

final class Definition implements DefinitionInterface
{
    private string $id;
    private string $class;
    private bool $isShared = false;
    private ?object $resolvedObject = null;
    private ?Factory $factory = null;

    /** @var array<string> */
    private array $tags = [];

    /** @var array<string, mixed> */
    private array $arguments = [];

    public function __construct(string $id, string $class)
    {
        $this->id = $id;
        $this->class = $class;
    }

    public function setShared(bool $isShared): self
    {
        $this->isShared = $isShared;

        return $this;
    }

    public function isShared(): bool
    {
        return $this->isShared;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getClass(): string
    {
        if (!class_exists($this->class)) {
            throw new ContainerException(
                sprintf(
                    'Class %s does not exist.',
                    $this->class
                )
            );
        }

        return $this->class;
    }

    public function addTag(string $tag): DefinitionInterface
    {
        if ($this->hasTag($tag)) {
            throw new ContainerException(
                sprintf(
                    'Tag "%s" already added.',
                    $tag
                )
            );
        }

        $this->tags[] = $tag;

        return $this;
    }

    /** @inheritDoc */
    public function addTags(array $tags): DefinitionInterface
    {
        foreach ($tags as $tag) {
            $this->addTag($tag);
        }

        return $this;
    }

    public function hasTag(string $tag): bool
    {
        return in_array($tag, $this->tags, true);
    }

    /** @inheritDoc */
    public function getTags(): array
    {
        return $this->tags;
    }

    public function setArgument(string $name, mixed $value): self
    {
        if (array_key_exists($name, $this->arguments)) {
            throw new ContainerException(
                sprintf(
                    'Argument %s already set.',
                    $name
                )
            );
        }

        $this->arguments[$name] = $value;

        return $this;
    }

    /** @inheritDoc */
    public function setArguments(array $arguments): self
    {
        foreach ($arguments as $name => $value) {
            $this->setArgument((string)$name, $value);
        }

        return $this;
    }

    public function hasArgument(string $name): bool
    {
        return array_key_exists($name, $this->arguments);
    }

    public function getArgument(string $name): mixed
    {
        if (!$this->hasArgument($name)) {
            throw new ContainerException(
                sprintf(
                    'Argument %s not set.',
                    $name
                )
            );
        }

        return $this->arguments[$name];
    }

    /** @inheritDoc */
    public function getArguments(): array
    {
        return $this->arguments;
    }

    /** @inheritDoc */
    public function isResolved(): bool
    {
        return $this->resolvedObject !== null;
    }

    /** @inheritDoc */
    public function setResolved(object $object): void
    {
        if ($this->isResolved()) {
            throw new ContainerException(
                sprintf(
                    'Id %s is already resolved.',
                    $this->getId()
                )
            );
        }

        $this->resolvedObject = $object;
        $this->setShared(true);
    }

    /** @inheritDoc */
    public function getResolved(): object
    {
        if ($this->resolvedObject === null) {
            throw new ContainerException(
                sprintf(
                    'Id %s is not resolved.',
                    $this->getId()
                )
            );
        }

        return $this->resolvedObject;
    }

    /** @inheritDoc
     */
    public function setFactory(Closure|string $factoryClassOrClosure, string $factoryMethod = self::METHOD_INVOKE): self
    {
        if ($this->factory !== null) {
            throw new FactoryException(
                sprintf(
                    'Factory is already set for id "%s".',
                    $this->id
                )
            );
        }

        if ($factoryClassOrClosure instanceof Closure) {
            if ($factoryMethod !== self::METHOD_INVOKE) {
                throw new FactoryException(
                    sprintf(
                        'It is not allowed to set method name "%s()" for a closure. Id: "%s".',
                        $factoryMethod,
                        $this->id
                    )
                );
            }

            $this->factory = new Factory($factoryClassOrClosure, null, false);

            return $this;
        }

        $isFactoryMethodStatic = false;

        if (str_contains($factoryClassOrClosure, '::')) {
            [$factoryClassOrClosure, $factoryMethod] = explode('::', $factoryClassOrClosure);
            if ((string)trim($factoryMethod) === '') {
                throw new FactoryException(
                    sprintf(
                        'Factory method not specified for factory class "%s". Id: "%s".',
                        $factoryClassOrClosure,
                        $this->id
                    )
                );
            }

            $isFactoryMethodStatic = true;
        }

        if (!class_exists($factoryClassOrClosure)) {
            throw new FactoryException(
                sprintf(
                    'Factory class "%s" does not exist. Id: "%s".',
                    $factoryClassOrClosure,
                    $this->id
                )
            );
        }

        if (str_starts_with($factoryMethod, '::')) {
            $factoryMethod = substr($factoryMethod, 2);
            if ((string)trim($factoryMethod) === '') {
                throw new FactoryException(
                    sprintf(
                        'Factory method not specified for factory class "%s". Id: "%s".',
                        $factoryClassOrClosure,
                        $this->id
                    )
                );
            }

            $isFactoryMethodStatic = true;
        }

        // Validate factory method existence.
        if (!method_exists($factoryClassOrClosure, $factoryMethod)) {
            throw new FactoryException(
                sprintf(
                    'Factory class "%s" does not have method "%s". Id: "%s".',
                    $factoryClassOrClosure,
                    $factoryMethod,
                    $this->id
                )
            );
        }

        // Validate factory method.
        $reflectionMethod = new ReflectionMethod($factoryClassOrClosure, $factoryMethod);
        $methodExceptionMessage = $this->validateFactoryMethod(
            $factoryClassOrClosure,
            $factoryMethod,
            $reflectionMethod->isStatic(),
            $isFactoryMethodStatic
        );
        if ($methodExceptionMessage !== null) {
            throw new FactoryException($methodExceptionMessage);
        }

        $this->factory = new Factory($factoryClassOrClosure, $factoryMethod, $isFactoryMethodStatic);

        return $this;
    }

    /** @inheritDoc */
    public function hasFactory(): bool
    {
        return $this->factory !== null;
    }

    /** @inheritDoc */
    public function getFactory(): Factory
    {
        if ($this->factory === null) {
            throw new FactoryException(
                sprintf(
                    'No factory is set for %s',
                    $this->id
                )
            );
        }

        return $this->factory;
    }

    private function validateFactoryMethod(
        string $factoryClass,
        string $factoryMethod,
        bool $isMethodStatic,
        bool $isMethodSpecifiedStatic
    ): ?string {
        if ($isMethodStatic && !$isMethodSpecifiedStatic) {
            return sprintf(
                'Factory method "%s::%s" is static but definition specify not static. Id: "%s".',
                $factoryClass,
                $factoryMethod,
                $this->id
            );
        }

        if (!$isMethodStatic && $isMethodSpecifiedStatic) {
            return sprintf(
                'Factory method "%s::%s" is dynamic but definition specify static. Id: "%s".',
                $factoryClass,
                $factoryMethod,
                $this->id
            );
        }

        return null;
    }
}