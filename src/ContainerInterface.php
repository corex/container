<?php

declare(strict_types=1);

namespace CoRex\Container;

use CoRex\Container\Exceptions\ContainerException;
use CoRex\Container\Exceptions\NotFoundException;
use Psr\Container\ContainerInterface as PsrContainerInterface;

interface ContainerInterface extends PsrContainerInterface
{
    /**
     * Make.
     *
     * @param string $idOrClass
     * @param array<int|string, mixed> $arguments
     * @return object
     * @throws NotFoundException
     * @throws ContainerException
     */
    public function make(string $idOrClass, array $arguments = []): object;
}