<?php

declare(strict_types=1);

namespace CoRex\Container\Helpers;

class Parameter
{
    /** @var string */
    private $name;

    /** @var mixed */
    private $value;

    /** @var bool */
    private $force;

    /**
     * Parameter.
     *
     * @param string $name
     * @param mixed $value
     * @param bool $force
     */
    public function __construct(string $name, $value, bool $force)
    {
        $this->name = $name;
        $this->value = $value;
        $this->force = $force;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Is forced.
     *
     * @return bool
     */
    public function isForced(): bool
    {
        return $this->force;
    }

    /**
     * Get value.
     *
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }
}