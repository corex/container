<?php

declare(strict_types=1);

namespace Tests\CoRex\Container\HelpersClasses;

class Test extends BaseTest
{
    /** @var string */
    private $value;

    /**
     * Set test value.
     *
     * @param string $value
     */
    public function setTestValue(string $value): void
    {
        $this->value = $value;
    }
}