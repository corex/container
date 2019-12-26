<?php

declare(strict_types=1);

namespace CoRex\Container\Helpers;

use Closure;

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
     * @param string|Closure $concrete
     * @param bool $shared
     */
    public function __construct(string $abstract, $concrete, bool $shared)
    {
        $this->abstract = $abstract;
        $this->concrete = $concrete;
        $this->shared = $shared;
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
     * @return string|Closure
     */
    public function getConcrete()
    {
        return $this->concrete;
    }

    /**
     * Is closure.
     *
     * @return bool
     */
    public function isClosure(): bool
    {
        return is_callable($this->concrete);
    }

    /**
     * Extends class.
     *
     * @param string $class
     * @return bool
     */
    public function extendsClass(string $class): bool
    {
        if ($this->isClosure()) {
            return false;
        }

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
        if ($this->isClosure()) {
            return false;
        }

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