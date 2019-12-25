<?php

declare(strict_types=1);

namespace Tests\CoRex\Container\HelpersClasses;

use CoRex\Container\Exceptions\ContainerException;

class BadClass
{
    /**
     * BadClass.
     *
     * @throws ContainerException
     */
    public function __construct()
    {
        throw new ContainerException('fail.on.purpose');
    }
}