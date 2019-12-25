<?php

declare(strict_types=1);

namespace Tests\CoRex\Container\Helpers;

use CoRex\Container\Exceptions\ContainerException;
use CoRex\Container\Helpers\Definition;
use CoRex\Helpers\Obj;
use PHPUnit\Framework\TestCase;
use ReflectionException;
use Tests\CoRex\Container\HelpersClasses\BaseTest;
use Tests\CoRex\Container\HelpersClasses\BaseTestInterface;
use Tests\CoRex\Container\HelpersClasses\Test;
use Tests\CoRex\Container\HelpersClasses\TestInjected;
use Tests\CoRex\Container\HelpersClasses\TestInjectedInterface;

class DefinitionTest extends TestCase
{
    /**
     * Test constructor class.
     *
     * @throws ReflectionException
     * @throws ContainerException
     */
    public function testConstructorClass(): void
    {
        $abstract = BaseTest::class;
        $concrete = Test::class;
        $shared = mt_rand(0, 1) === 1;
        $definition = new Definition($abstract, $concrete, $shared);

        $this->assertEquals($abstract, Obj::getProperty('abstract', $definition));
        $this->assertEquals($concrete, Obj::getProperty('concrete', $definition));
        $this->assertEquals($shared, Obj::getProperty('shared', $definition));
    }

    /**
     * Test constructor interface.
     *
     * @throws ReflectionException
     * @throws ContainerException
     */
    public function testConstructorInterface(): void
    {
        $abstract = BaseTestInterface::class;
        $concrete = Test::class;
        $shared = mt_rand(0, 1) === 1;
        $definition = new Definition($abstract, $concrete, $shared);

        $this->assertEquals($abstract, Obj::getProperty('abstract', $definition));
        $this->assertEquals($concrete, Obj::getProperty('concrete', $definition));
        $this->assertEquals($shared, Obj::getProperty('shared', $definition));
    }

    /**
     * Test constructor not class.
     *
     * @throws ReflectionException
     * @throws ContainerException
     */
    public function testConstructorNotClass(): void
    {
        $abstract = md5((string)mt_rand(1, 100000));
        $concrete = Test::class;
        $shared = mt_rand(0, 1) === 1;

        $definition = new Definition($abstract, $concrete, $shared);

        $this->assertEquals($abstract, Obj::getProperty('abstract', $definition));
        $this->assertEquals($concrete, Obj::getProperty('concrete', $definition));
        $this->assertEquals($shared, Obj::getProperty('shared', $definition));
    }

    /**
     * Test constructor not concrete class.
     *
     * @throws ContainerException
     */
    public function testConstructorNotConcreteClass(): void
    {
        $abstract = BaseTest::class;
        $concrete = md5((string)mt_rand(1, 100000));
        $shared = mt_rand(0, 1) === 1;

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('Class ' . $concrete . ' does not exist.');

        new Definition($abstract, $concrete, $shared);
    }

    /**
     * Test is shared.
     *
     * @throws ContainerException
     */
    public function testIsShared(): void
    {
        $abstract = BaseTest::class;
        $concrete = Test::class;
        $shared = mt_rand(0, 1) === 1;
        $definition = new Definition($abstract, $concrete, $shared);
        $this->assertEquals($shared, call_user_func([$definition, 'isShared']));
    }

    /**
     * Test set shared.
     */
    public function testSetShared(): void
    {
        $definition = new Definition('test', BaseTest::class, false);
        $this->assertFalse($definition->isShared());

        $definition->setShared();
        $this->assertTrue($definition->isShared());

        $definition->setShared(true);
        $this->assertTrue($definition->isShared());

        $definition->setShared(false);
        $this->assertFalse($definition->isShared());
    }

    /**
     * Test get concrete class.
     *
     * @throws ContainerException
     */
    public function testGetConcreteClass(): void
    {
        $abstract = BaseTest::class;
        $concrete = Test::class;
        $shared = mt_rand(0, 1) === 1;
        $definition = new Definition($abstract, $concrete, $shared);
        $this->assertEquals($concrete, call_user_func([$definition, 'getConcrete']));
    }

    /**
     * Test extends class.
     *
     * @throws ContainerException
     */
    public function testExtendsClass(): void
    {
        $definition = new Definition('test', Test::class, false);
        $this->assertTrue($definition->extendsClass(BaseTest::class));
        $this->assertFalse($definition->extendsClass(TestInjected::class));
    }

    /**
     * Test implements intercace.
     *
     * @throws ContainerException
     */
    public function testImplementsInterface(): void
    {
        $definition = new Definition('test', Test::class, false);
        $this->assertTrue($definition->implementsInterface(BaseTestInterface::class));
        $this->assertFalse($definition->implementsInterface(TestInjectedInterface::class));
    }

    /**
     * Test parameters.
     *
     * @throws ContainerException
     */
    public function testParameters(): void
    {
        $check1 = md5((string)mt_rand(1, 100000)) . '1';
        $check2 = md5((string)mt_rand(1, 100000)) . '2';

        $definition = new Definition('test', Test::class, false);

        $definition->setDefaultParameter('default', $check1);
        $definition->setForcedParameter('forced', $check2);

        $parameters = $definition->getParameters();

        $this->assertArrayHasKey('default', $parameters);
        $this->assertEquals($check1, $parameters['default']->getValue());
        $this->assertFalse($parameters['default']->isForced());

        $this->assertArrayHasKey('forced', $parameters);
        $this->assertEquals($check2, $parameters['forced']->getValue());
        $this->assertTrue($parameters['forced']->isForced());
    }

    /**
     * Test set/get/has tag.
     *
     * @throws ContainerException
     */
    public function testSetGetHasTag(): void
    {
        $definition = new Definition('test', Test::class, false);

        // Check default.
        $this->assertNull($definition->getTag());

        // Check set tag.
        $check = md5((string)mt_rand(1, 100000));
        $definition->setTag($check);
        $this->assertEquals($check, $definition->getTag());

        // Check cleared tag.
        $definition->setTag(null);
        $this->assertNull($definition->getTag());
    }
}
