<?php

declare(strict_types=1);

namespace Tests\CoRex\Container\HelpersClasses;

class TestDependencyInjectionDefaultValue
{
    public const DEFAULT_VALUE = 'default.value';

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
    public function __construct(TestInjectedInterface $testInjected, string $test = self::DEFAULT_VALUE)
    {
        $this->testInjected = $testInjected;
        $this->test = $test;
    }
}