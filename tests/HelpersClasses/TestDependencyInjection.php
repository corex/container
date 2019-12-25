<?php

declare(strict_types=1);

namespace Tests\CoRex\Container\HelpersClasses;

class TestDependencyInjection
{
    /** @var TestInjectedInterface */
    private $testInjected;

    /** @var string */
    private $test;

    /**
     * TestDependencyInjection.
     *
     * @param TestInjectedInterface $testInjected
     * @param string $test
     */
    public function __construct(TestInjectedInterface $testInjected, string $test)
    {
        $this->testInjected = $testInjected;
        $this->test = $test;
    }
}