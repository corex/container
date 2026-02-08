<?php

declare(strict_types=1);

namespace Tests\CoRex\Container\Resource;

class TestFactoryObject implements TestFactoryObjectInterface
{
    private TestInterface $test;

    public function __construct(TestInterface $test)
    {
        $this->test = $test;
    }

    public function getTest(): TestInterface
    {
        return $this->test;
    }
}