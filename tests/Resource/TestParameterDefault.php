<?php

declare(strict_types=1);

namespace Tests\CoRex\Container\Resource;

class TestParameterDefault
{
    public const DEFAULT_FIRSTNAME = 'Jesus';

    private mixed $firstname;

    // phpcs:disable
    // @phpstan-ignore-next-line
    public function __construct($firstname = self::DEFAULT_FIRSTNAME)
    {
        $this->firstname = $firstname;
    }

    public function getFirstname(): mixed
    {
        return $this->firstname;
    }
}