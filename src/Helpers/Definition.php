<?php

declare(strict_types=1);

namespace CoRex\Container\Helpers;

use CoRex\Container\Exceptions\ContainerException;

class Definition
{
    /** @var string */
    private $abstract;

    /** @var string */
    private $concrete;

    /** @var bool */
    private $shared;

    /** @var Parameter[] */
    private $parameters = [];

    /** @var string */
    private $tag;

    /**
     * Definition.
     *
     * @param string $abstract
     * @param string $concrete
     * @param bool $shared
     * @throws ContainerException
     */
    public function __construct(string $abstract, string $concrete, bool $shared)
    {
        $this->abstract = $abstract;
        $this->concrete = $concrete;
        $this->shared = $shared;

        // CHeck if concrete class exists.
        if (!class_exists($concrete)) {
            throw new ContainerException('Class ' . $concrete . ' does not exist.');
        }
    }

    /**
     * Set shared.
     *
     * @param bool $shared
     */
    public function setShared(bool $shared = true): void
    {
        $this->shared = $shared;
    }

    /**
     * Is shared.
     *
     * @return bool
     */
    public function isShared(): bool
    {
        return $this->shared;
    }

    /**
     * Get instance class.
     *
     * @return string
     */
    public function getConcrete(): string
    {
        return $this->concrete;
    }

    /**
     * Extends class.
     *
     * @param string $class
     * @return bool
     */
    public function extendsClass(string $class): bool
    {
        return in_array($class, array_values(class_parents($this->concrete)));
    }

    /**
     * Implements interface.
     *
     * @param string $interface
     * @return bool
     */
    public function implementsInterface(string $interface): bool
    {
        return in_array($interface, class_implements($this->concrete));
    }

    /**
     * Set default parameter.
     *
     * @param string $name
     * @param mixed $value
     * @return $this
     */
    public function setDefaultParameter(string $name, $value): self
    {
        $this->parameters[$name] = new Parameter($name, $value, false);

        return $this;
    }

    /**
     * Set forced parameter.
     *
     * @param string $name
     * @param mixed $value
     * @return $this
     */
    public function setForcedParameter(string $name, $value): self
    {
        $this->parameters[$name] = new Parameter($name, $value, true);

        return $this;
    }

    /**
     * Get parameters.
     *
     * @return Parameter[]
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * Set tag.
     *
     * @param string|null $tag
     */
    public function setTag(?string $tag): void
    {
        $this->tag = $tag;
    }

    /**
     * Get tag.
     *
     * @return string|null
     */
    public function getTag(): ?string
    {
        return $this->tag;
    }
}