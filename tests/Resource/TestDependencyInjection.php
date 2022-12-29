<?php

declare(strict_types=1);

namespace Tests\CoRex\Container\Resource;

class TestDependencyInjection
{
    private TestInjectedInterface $testInjected;
    private string $test;

    public function __construct(TestInjectedInterface $testInjected, string $test)
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