<?php

declare(strict_types=1);

namespace Tests\CoRex\Container\HelpersClasses;

class TestParameters
{
    /** @var string */
    private $name;

    /**
     * TestParameters.
     *
     * @param string|null $name
     */
    public function __construct(?string $name = null)
    {
        $this->name = $name;
    }

    /**
     * Get name.
     *
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }
}