<?php

declare(strict_types=1);

namespace Tests\CoRex\Container\Helpers;

use CoRex\Container\Helpers\Parameter;
use CoRex\Helpers\Obj;
use PHPUnit\Framework\TestCase;
use ReflectionException;

class ParameterTest extends TestCase
{
    /** @var string */
    private $check1;

    /** @var string */
    private $check2;

    /**
     * Test.
     *
     * @throws ReflectionException
     */
    public function testConstructor(): void
    {
        $parameter = new Parameter($this->check1, $this->check2, true);
        $this->assertEquals($this->check1, Obj::getProperty('name', $parameter));
        $this->assertEquals($this->check2, Obj::getProperty('value', $parameter));
        $this->assertTrue(Obj::getProperty('force', $parameter));
    }

    /**
     * Test getName(), getValue() and isForced().
     *
     * @throws ReflectionException
     */
    public function testGetNameAndValueAndForced(): void
    {
        $parameter = new Parameter($this->check1, $this->check2, true);
        $this->assertEquals($this->check1, $parameter->getName());
        $this->assertEquals($this->check2, $parameter->getValue());
        $this->assertTrue($parameter->isForced());
    }

    /**
     * Setup.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->check1 = md5((string)mt_rand(1, 100000)) . '1';
        $this->check2 = md5((string)mt_rand(1, 100000)) . '2';
    }
}
