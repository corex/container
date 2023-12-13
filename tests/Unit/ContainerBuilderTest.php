<?php

declare(strict_types=1);

namespace Tests\CoRex\Container\Unit;

use CoRex\Container\Container;
use CoRex\Container\ContainerBuilder;
use CoRex\Container\Exceptions\ContainerException;
use CoRex\Container\Exceptions\NotFoundException;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Tests\CoRex\Container\Resource\Test;
use Tests\CoRex\Container\Resource\TestExtended;
use Tests\CoRex\Container\Resource\TestInjected;
use Tests\CoRex\Container\Resource\TestInterface;
use Tests\CoRex\Container\Resource\TestNoImplements;
use Tests\CoRex\Container\Resource\TestWithMultipleInterfaces;

/**
 * @covers \CoRex\Container\ContainerBuilder
 */
class ContainerBuilderTest extends TestCase
{
    public function testBindWorks(): void
    {
        $containerBuilder = new ContainerBuilder();
        $definitionBuilder = $containerBuilder->bind(Test::class, Test::class);
        $this->assertTrue($containerBuilder->has(Test::class));
        $this->assertFalse($definitionBuilder->isShared());
    }

    public function testBindClassNotFound(): void
    {
        $containerBuilder = new ContainerBuilder();
        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Class test not found.');
        $containerBuilder->bind('test', 'test');
    }

    public function testBindAlreadyBound(): void
    {
        $containerBuilder = new ContainerBuilder();
        $containerBuilder->bind(Test::class, Test::class);
        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage(sprintf('Id %s already bound', Test::class));
        $containerBuilder->bind(Test::class, Test::class);
    }

    public function testBindClassWorks(): void
    {
        $containerBuilder = new ContainerBuilder();

        $this->assertFalse($containerBuilder->has(Test::class));
        $this->assertFalse($containerBuilder->has(TestInterface::class));

        $containerBuilder->bindClass(Test::class);

        $this->assertTrue($containerBuilder->has(Test::class));
        $this->assertFalse($containerBuilder->has(TestInterface::class));
    }

    public function testBindClassByInterfaceWorks(): void
    {
        $containerBuilder = new ContainerBuilder();
        $this->assertFalse($containerBuilder->has(TestInterface::class));
        $containerBuilder->bindClassByInterface(Test::class);
        $this->assertTrue($containerBuilder->has(TestInterface::class));
    }

    public function testBindClassByInterfaceWithUnknownClass(): void
    {
        $containerBuilder = new ContainerBuilder();

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage(
            sprintf('Class %s not found.', 'unknown')
        );

        $containerBuilder->bindClassByInterface('unknown');
    }

    public function testBindClassByInterfaceWhenNoInterface(): void
    {
        $containerBuilder = new ContainerBuilder();

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage(
            sprintf(
                'Class %s does not implement an interface.',
                TestNoImplements::class
            )
        );

        $containerBuilder->bindClassByInterface(TestNoImplements::class);
    }

    public function testBindClassByInterfaceWhenMoreThanOneInterface(): void
    {
        $containerBuilder = new ContainerBuilder();

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage(
            sprintf(
                'Class %s must only implement 1 interface to use %s().',
                TestWithMultipleInterfaces::class,
                'bindClassByInterface'
            )
        );

        $containerBuilder->bindClassByInterface(TestWithMultipleInterfaces::class);
    }

    public function testBindClassByInterfaceWhenInterfaceIsSpecified(): void
    {
        $containerBuilder = new ContainerBuilder();

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage(
            sprintf(
                'Must specify a class when using %s().',
                'bindClassByInterface'
            )
        );

        $containerBuilder->bindClassByInterface(TestInterface::class);
    }

    public function testHas(): void
    {
        $containerBuilder = new ContainerBuilder();
        $this->assertFalse($containerBuilder->has(Test::class));
        $containerBuilder->bind(Test::class, Test::class);
        $this->assertTrue($containerBuilder->has(Test::class));
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testSetWorks(): void
    {
        $testExtended = new TestExtended();

        $containerBuilder = new ContainerBuilder();
        $containerBuilder->bind('test', Test::class);

        $containerBuilder->set('test', $testExtended);

        $container = new Container($containerBuilder);

        $testExtendedResolved = $container->get('test');

        $this->assertSame($testExtended, $testExtendedResolved);
    }

    public function testSetSameOrExtendsClass(): void
    {
        $classNotExtendingCorrectClass = new TestInjected();

        $containerBuilder = new ContainerBuilder();
        $containerBuilder->bind('test', Test::class);

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage(
            sprintf(
                'Object is not same as or extends "%s".',
                Test::class
            )
        );

        $containerBuilder->set('test', $classNotExtendingCorrectClass);
    }

    public function testSetNotBound(): void
    {
        $testExtended = new TestExtended();

        $containerBuilder = new ContainerBuilder();

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage(
            sprintf(
                'Id %s must be bound before setting object.',
                'test'
            )
        );

        $containerBuilder->set('test', $testExtended);
    }

    public function testGetIds(): void
    {
        $containerBuilder = new ContainerBuilder();
        $containerBuilder->bind('test1', Test::class);
        $containerBuilder->bind('test2', Test::class);
        $this->assertSame(
            ['test1', 'test2'],
            $containerBuilder->getIds()
        );
    }

    public function testGetTaggedIds(): void
    {
        $containerBuilder = new ContainerBuilder();
        $containerBuilder->bind('test1', Test::class)->addTag('tagA');
        $containerBuilder->bind('test2', Test::class)->addTag('tagB');
        $containerBuilder->bind('test3', Test::class)->addTag('tagB');
        $containerBuilder->bind('test4', Test::class)->addTag('tagC');

        $this->assertSame(
            ['test2', 'test3'],
            $containerBuilder->getTaggedIds('tagB')
        );
    }

    public function testGetDefinitionWorks(): void
    {
        $containerBuilder = new ContainerBuilder();
        $containerBuilder->bind(Test::class, Test::class);
        $definitionBuilder = $containerBuilder->getDefinition(Test::class);
        $this->assertSame(Test::class, $definitionBuilder->getId());
        $this->assertSame(Test::class, $definitionBuilder->getClass());
    }

    public function testGetDefinitionUnknown(): void
    {
        $containerBuilder = new ContainerBuilder();
        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Id unknown not found.');
        $containerBuilder->getDefinition('unknown');
    }
}