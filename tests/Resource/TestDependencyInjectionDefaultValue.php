<?php

declare(strict_types=1);

namespace Tests\CoRex\Container\Resource;

class TestDependencyInjectionDefaultValue
{
    public const DEFAULT_VALUE = 'default.value';

    private TestInjectedInterface $testInjected;
    private string $test;

    public function __construct(TestInjectedInterface $testInjected, string $test = self::DEFAULT_VALUE)
    {
        $this->testInjected = $testInjected;
        $this->test = $test;
    }

    public function getTestInjected(): TestInjectedInterface
    {
        return $this->testInjected;
    }

    public function getTest(): string
    {
        return $this->test;
    }
}