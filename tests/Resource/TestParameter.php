<?php

declare(strict_types=1);

namespace Tests\CoRex\Container\Resource;

class TestParameter
{
    private mixed $firstname;

    // phpcs:disable
    // @phpstan-ignore-next-line
    public function __construct($firstname)
    {
        $this->firstname = $firstname;
    }

    public function getFirstname(): mixed
    {
        return $this->firstname;
    }
}