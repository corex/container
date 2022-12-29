<?php

declare(strict_types=1);

namespace Tests\CoRex\Container\Resource;

use RuntimeException;

class BadClass
{
    public function __construct()
    {
        throw new RuntimeException('fail.on.purpose');
    }
}