<?php

declare(strict_types=1);

namespace CoRex\Container\Definition;

use Closure;
use CoRex\Container\ContainerInterface;

class Factory
{
    private string|Closure $factoryClassOrClosure;
    private ?string $factoryMethod;
    private bool $isStatic;

    public function __construct(Closure|string $factoryClassOrClosure, ?string $factoryMethod, bool $isStatic)
    {
        $this->factoryClassOrClosure = $factoryClassOrClosure;
        $this->factoryMethod = $factoryMethod;
        $this->isStatic = $isStatic;
    }

    public function isClosureFactory(): bool
    {
        return $this->factoryClassOrClosure instanceof Closure;
    }

    public function isInvokableFactory(): bool
    {
        return !$this->isClosureFactory() && $this->factoryMethod === DefinitionInterface::METHOD_INVOKE;
    }

    public function isDynamicFactory(): bool
    {
        return !$this->isStatic && !$this->isInvokableFactory() && !$this->isClosureFactory();
    }

    public function isStaticFactory(): bool
    {
        return $this->isStatic;
    }

    public function invoke(ContainerInterface $container): object
    {
        $factory = $this->factoryClassOrClosure;

        if ($factory instanceof Closure) {
            return $factory($container);
        }

        $factoryMethod = $this->factoryMethod;
        if ($this->isStatic) {
            return $factory::$factoryMethod($container);
        }

        return $container->make($factory)->{$factoryMethod}($container);
    }
}