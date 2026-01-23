<?php

declare(strict_types=1);

namespace Tests\CoRex\Container\Resource;

class TestParameterWithTypeHint
{
    private mixed $firstname;

    public function __construct(string $firstname)
    {
        $this->firstname = $firstname;
    }

    public function getFirstname(): mixed
    {
        return $this->firstname;
    }
}