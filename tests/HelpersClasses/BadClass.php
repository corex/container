<?php

declare(strict_types=1);

namespace Tests\CoRex\Container\HelpersClasses;

use Exception;

class BadClass
{
    /**
     * BadClass.
     *
     * @throws Exception
     */
    public function __construct()
    {
        throw new Exception('fail.on.purpose');
    }
}