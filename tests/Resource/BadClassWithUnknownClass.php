<?php

declare(strict_types=1);

namespace Tests\CoRex\Container\Resource;

class BadClassWithUnknownClass
{
    // phpcs:disable
    // @phpstan-ignore-next-line
    public function __construct(UnknownClass $unknownClass)
    {
    }
}