<?php

declare(strict_types=1);

namespace Tests\CoRex\Container\Resource;

use CoRex\Container\ContainerInterface;

class TestContainerInjected
{
    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function getContainer(): ContainerInterface
    {
        return $this->container;
    }
}