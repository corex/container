<?php

declare(strict_types=1);

namespace Tests\CoRex\Container\Unit;

use CoRex\Container\Container;
use CoRex\Container\ContainerBuilder;
use CoRex\Container\ContainerBuilderInterface;
use CoRex\Container\Definition\DefinitionInterface;
use CoRex\Container\Exceptions\ContainerException;
use CoRex\Container\Exceptions\NotFoundException;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionException;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use Tests\CoRex\Container\Resource\BadClass;
use Tests\CoRex\Container\Resource\Test;
use Tests\CoRex\Container\Resource\TestContainerInjected;
use Tests\CoRex\Container\Resource\TestDependencyInjection;
use Tests\CoRex\Container\Resource\TestDependencyInjectionDefaultValue;
use Tests\CoRex\Container\Resource\TestExtended;
use Tests\CoRex\Container\Resource\TestInjected;
use Tests\CoRex\Container\Resource\TestInjectedInterface;
use Tests\CoRex\Container\Resource\TestParameter;
use Tests\CoRex\Container\Resource\TestParameterDefault;

/**
 * @covers \CoRex\Container\Container
 */
class ContainerTest extends TestCase
{
    public function testMakeWhenBound(): void
    {
        $id = 'test';
        $definitionBuilder = $this->getMockBuilder(DefinitionInterface::class)->getMock();
        $definitionBuilder->expects($this->exactly(2))
            ->method('isShared')
            ->willReturnOnConsecutiveCalls(true, true);
        $definitionBuilder->expects($this->once())
            ->method('getClass')
            ->willReturn(Test::class);
        $definitionBuilder->expects($this->once())
            ->method('getArguments')
            ->willReturn([]);

        $containerBuilder = $this->getMockBuilder(ContainerBuilderInterface::class)->getMock();
        $containerBuilder->expects($this->exactly(2))
            ->method('has')
            ->withConsecutive(
                [$id],
                [$id]
            )
            ->willReturnOnConsecutiveCalls(true, true);
        $containerBuilder->expects($this->exactly(2))
            ->method('getDefinition')
            ->withConsecutive(
                [$id],
                [$id]
            )
            ->willReturnOnConsecutiveCalls($definitionBuilder, $definitionBuilder);

        $container = new Container($containerBuilder);

        $instance = $container->make('test');
        $this->assertInstanceOf(Test::class, $instance);
        $this->assertSame(
            $instance,
            $container->make('test')
        );
    }

    public function testMakeWhenNotBound(): void
    {
        $container = new Container();
        $test = $container->make(Test::class);
        $this->assertInstanceOf(Test::class, $test);
    }

    public function testMakeWhenClassNotFound(): void
    {
        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('unknownClass not found.');
        $container = new Container();
        $container->make('unknownClass');
    }

    public function testMakeWhenResolvingParameters(): void
    {
        $containerBuilder = new ContainerBuilder();
        $containerBuilder->bind(TestInjectedInterface::class, TestInjected::class);
        $container = new Container($containerBuilder);

        /** @var TestDependencyInjection $testDependencyInjection */
        $testDependencyInjection = $container->make(TestDependencyInjection::class, ['test' => 'hello']);
        $this->assertInstanceOf(TestInjected::class, $testDependencyInjection->getTestInjected());
        $this->assertSame('hello', $testDependencyInjection->getTest());
    }

    public function testMakeWhenResolvingDefaultTypeHintedParameter(): void
    {
        $containerBuilder = new ContainerBuilder();
        $containerBuilder->bind(TestInjectedInterface::class, TestInjected::class);
        $container = new Container($containerBuilder);

        /** @var TestDependencyInjectionDefaultValue $testDependencyInjectionDefaultValue */
        $testDependencyInjectionDefaultValue = $container->make(TestDependencyInjectionDefaultValue::class);
        $this->assertInstanceOf(TestInjected::class, $testDependencyInjectionDefaultValue->getTestInjected());
        $this->assertSame(
            TestDependencyInjectionDefaultValue::DEFAULT_VALUE,
            $testDependencyInjectionDefaultValue->getTest()
        );
    }

    public function testMakeWhenResolvingContainerParameter(): void
    {
        $container = new Container();

        /** @var TestContainerInjected $testContainerInjected */
        $testContainerInjected = $container->make(TestContainerInjected::class);
        $this->assertSame($container, $testContainerInjected->getContainer());
    }

    public function testMakeWhenResolvingDefaultParameter(): void
    {
        $container = new Container();

        /** @var TestParameterDefault $testParameterDefault */
        $testParameterDefault = $container->make(TestParameterDefault::class);
        $this->assertSame(TestParameterDefault::DEFAULT_FIRSTNAME, $testParameterDefault->getFirstname());
    }

    public function testMakeWhenResolvingParameterWithNoTypehintAndNoDefaultValue(): void
    {
        $container = new Container();

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage(
            sprintf(
                '"firstname" could not be resolved for id/class "%s".',
                TestParameter::class
            )
        );

        $container->make(TestParameter::class);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testMakeWhenResolved(): void
    {
        $testExtended = new TestExtended();

        $containerBuilder = new ContainerBuilder();
        $containerBuilder->bind('test', Test::class);

        $containerBuilder->set('test', $testExtended);

        $container = new Container($containerBuilder);

        $testExtendedResolved = $container->get('test');

        $this->assertSame($testExtended, $testExtendedResolved);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testGet(): void
    {
        $id = 'test';
        $definitionBuilder = $this->getMockBuilder(DefinitionInterface::class)->getMock();
        $definitionBuilder->expects($this->once())
            ->method('isShared')
            ->willReturnOnConsecutiveCalls(true);
        $definitionBuilder->expects($this->once())
            ->method('getClass')
            ->willReturn(Test::class);
        $definitionBuilder->expects($this->once())
            ->method('getArguments')
            ->willReturn([]);

        $containerBuilder = $this->getMockBuilder(ContainerBuilderInterface::class)->getMock();
        $containerBuilder->expects($this->exactly(2))
            ->method('has')
            ->withConsecutive(
                [$id],
                [$id]
            )
            ->willReturnOnConsecutiveCalls(true, true);
        $containerBuilder->expects($this->once())
            ->method('getDefinition')
            ->with($id)
            ->willReturn($definitionBuilder);

        $container = new Container($containerBuilder);
        $instance = $container->get('test');
        $this->assertInstanceOf(Test::class, $instance);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testGetNotFound(): void
    {
        $containerBuilder = $this->getMockBuilder(ContainerBuilderInterface::class)->getMock();
        $containerBuilder->expects($this->once())
            ->method('has')
            ->with('test')
            ->willReturn(false);

        $container = new Container($containerBuilder);
        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('No entry was found for test identifier.');
        $container->get('test');
    }

    public function testHasTrue(): void
    {
        $containerBuilder = new ContainerBuilder();
        $containerBuilder->bind(Test::class, Test::class);
        $container = new Container($containerBuilder);
        $this->assertTrue($container->has(Test::class));
    }

    public function testHasFalse(): void
    {
        $containerBuilder = $this->getMockBuilder(ContainerBuilderInterface::class)->getMock();
        $containerBuilder->expects($this->once())
            ->method('has')
            ->with(Test::class)
            ->willReturn(true);

        $container = new Container($containerBuilder);
        $this->assertTrue($container->has(Test::class));
    }

    /** @throws ReflectionException */
    public function testNewInstanceClassNotFound(): void
    {
        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('unknownClass not found.');
        $containerBuilder = $this->getMockBuilder(ContainerBuilderInterface::class)->getMock();
        $container = new Container($containerBuilder);
        $this->callMethod(
            'newInstance',
            $container,
            [
                'class' => 'unknownClass',
                'params' => []
            ]
        );
    }

    /** @throws ReflectionException */
    public function testNewInstanceReflectionException(): void
    {
        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('fail.on.purpose');
        $containerBuilder = $this->getMockBuilder(ContainerBuilderInterface::class)->getMock();
        $container = new Container($containerBuilder);
        $this->callMethod(
            'newInstance',
            $container,
            [
                'class' => BadClass::class,
                'params' => []
            ]
        );
    }

    public function testGetReflectionParametersFromClass(): void
    {
        $container = new Container();

        /** @var array<ReflectionParameter> $reflectionParameters */
        $reflectionParameters = $this->callMethod(
            'getReflectionParametersFromClass',
            $container,
            ['class' => TestDependencyInjection::class]
        );

        $reflectionParameter1 = $reflectionParameters[0];
        /** @var ReflectionNamedType $reflectionType */
        $reflectionType = $reflectionParameter1->getType();
        $this->assertSame(
            TestInjectedInterface::class,
            $reflectionType->getName()
        );

        $reflectionParameter2 = $reflectionParameters[1];
        /** @var ReflectionNamedType $reflectionType */
        $reflectionType = $reflectionParameter2->getType();
        $this->assertSame(
            'string',
            $reflectionType->getName()
        );
    }

    public function testGetReflectionParametersWhenClassNotFound(): void
    {
        $container = new Container();

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('Class "unknownClass" does not exist');

        $this->callMethod(
            'getReflectionParametersFromClass',
            $container,
            ['class' => 'unknownClass']
        );
    }

    /**
     * @param string $name
     * @param object $object
     * @param array<int|string, mixed> $arguments
     * @return mixed
     * @throws ReflectionException
     */
    private function callMethod(string $name, object $object, array $arguments = []): mixed
    {
        $method = new ReflectionMethod($object, $name);
        if (count($arguments) > 0) {
            return $method->invokeArgs($object, $arguments);
        }

        return $method->invoke($object);
    }
}