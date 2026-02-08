<?php

declare(strict_types=1);

namespace Tests\CoRex\Container\Definition;

use CoRex\Container\ContainerInterface;
use CoRex\Container\Definition\DefinitionInterface;
use CoRex\Container\Definition\Factory;
use PHPUnit\Framework\TestCase;
use Tests\CoRex\Container\Resource\Test;
use Tests\CoRex\Container\Resource\TestFactory;
use Tests\CoRex\Container\Resource\TestFactoryObject;
use Tests\CoRex\Container\Resource\TestFactoryObjectInterface;
use Tests\CoRex\Container\Resource\TestInterface;

/**
 * @covers \CoRex\Container\Definition\Factory
 */
class FactoryTest extends TestCase
{
    public function testIsClosureFactory(): void
    {
        $factory = new Factory(
            function () {
                return new Test();
            },
            DefinitionInterface::METHOD_INVOKE,
            false
        );

        $this->assertTrue($factory->isClosureFactory());
        $this->assertFalse($factory->isInvokableFactory());
        $this->assertFalse($factory->isDynamicFactory());
        $this->assertFalse($factory->isStaticFactory());
    }

    public function testIsInvokableFactory(): void
    {
        $factory = new Factory(TestFactory::class, DefinitionInterface::METHOD_INVOKE, false);

        $this->assertFalse($factory->isClosureFactory());
        $this->assertTrue($factory->isInvokableFactory());
        $this->assertFalse($factory->isDynamicFactory());
        $this->assertFalse($factory->isStaticFactory());
    }

    public function testIsDynamicFactory(): void
    {
        $factory = new Factory(TestFactory::class, 'createDynamic', false);

        $this->assertFalse($factory->isClosureFactory());
        $this->assertFalse($factory->isInvokableFactory());
        $this->assertTrue($factory->isDynamicFactory());
        $this->assertFalse($factory->isStaticFactory());
    }

    public function testIsStaticFactory(): void
    {
        $factory = new Factory(TestFactory::class, 'createStatic', true);

        $this->assertFalse($factory->isClosureFactory());
        $this->assertFalse($factory->isInvokableFactory());
        $this->assertFalse($factory->isDynamicFactory());
        $this->assertTrue($factory->isStaticFactory());
    }

    public function testInvokeClosureFactory(): void
    {
        $container = $this->createMock(ContainerInterface::class);

        $factory = new Factory(
            function () {
                return new Test();
            },
            DefinitionInterface::METHOD_INVOKE,
            false
        );

        $object = $factory->invoke($container);

        $this->assertInstanceOf(Test::class, $object);
    }

    public function testInvokeInvokableFactory(): void
    {
        $methodName = DefinitionInterface::METHOD_INVOKE;

        $test = $this->createMock(TestInterface::class);

        $testFactoryObject = new TestFactoryObject($test);

        $testFactory = $this->createMock(TestFactory::class);
        $testFactory->expects($this->once())
            ->method($methodName)
            ->willReturn($testFactoryObject);

        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->once())
            ->method('make')
            ->willReturn($testFactory);

        $factory = new Factory(TestFactory::class, $methodName, false);

        $this->assertInstanceOf(
            TestFactoryObjectInterface::class,
            $factory->invoke($container)
        );
    }

    public function testInvokeDynamicFactory(): void
    {
        $methodName = 'createDynamic';

        $test = $this->createMock(TestInterface::class);

        $testFactoryObject = new TestFactoryObject($test);

        $testFactory = $this->createMock(TestFactory::class);
        $testFactory->expects($this->once())
            ->method($methodName)
            ->willReturn($testFactoryObject);

        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->once())
            ->method('make')
            ->willReturn($testFactory);

        $factory = new Factory(TestFactory::class, $methodName, false);

        $this->assertInstanceOf(
            TestFactoryObjectInterface::class,
            $factory->invoke($container)
        );
    }

    public function testInvokeStaticFactory(): void
    {
        $methodName = 'createStatic';

        $test = $this->createMock(TestInterface::class);

        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->once())
            ->method('get')
            ->willReturn($test);

        $factory = new Factory(TestFactory::class, $methodName, true);

        $this->assertInstanceOf(
            TestFactoryObjectInterface::class,
            $factory->invoke($container)
        );
    }
}
