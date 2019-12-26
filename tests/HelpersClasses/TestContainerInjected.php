<?php

declare(strict_types=1);

namespace Tests\CoRex\Container\HelpersClasses;

use Psr\Container\ContainerInterface;

class TestContainerInjected
{
    /** @var ContainerInterface */
    private $container;

    /**
     * TestContainerInjected.
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }
}