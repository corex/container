<?php

declare(strict_types=1);

namespace Tests\CoRex\Container\Definition;

use CoRex\Container\ContainerInterface;
use CoRex\Container\Definition\Definition;
use CoRex\Container\Definition\Factory;
use CoRex\Container\Exceptions\ContainerException;
use CoRex\Container\Exceptions\FactoryException;
use PHPUnit\Framework\TestCase;
use ReflectionException;
use stdClass;
use Tests\CoRex\Container\Resource\Test;
use Tests\CoRex\Container\Resource\TestExtended;
use Tests\CoRex\Container\Resource\TestFactory;
use Tests\CoRex\Container\Resource\TestFactoryObject;
use Tests\CoRex\Container\Resource\TestInterface;

/**
 * @covers \CoRex\Container\Definition\Definition
 */
class DefinitionTest extends TestCase
{
    public function testConstructorWhenClass(): void
    {
        $definitionBuilder = new Definition(Test::class, Test::class);
        $this->assertSame(Test::class, $definitionBuilder->getId());
        $this->assertSame(Test::class, $definitionBuilder->getClass());
        $this->assertFalse($definitionBuilder->isShared());
    }

    public function testConstructorWhenInterface(): void
    {
        $definitionBuilder = new Definition(TestInterface::class, Test::class);
        $this->assertSame(TestInterface::class, $definitionBuilder->getId());
        $this->assertSame(Test::class, $definitionBuilder->getClass());
        $this->assertFalse($definitionBuilder->isShared());
    }

    public function testConstructorWhenString(): void
    {
        $definitionBuilder = new Definition('test', Test::class);
        $this->assertSame('test', $definitionBuilder->getId());
        $this->assertSame(Test::class, $definitionBuilder->getClass());
        $this->assertFalse($definitionBuilder->isShared());
    }

    public function testSetSharedAndIsShared(): void
    {
        $definitionBuilder = new Definition(Test::class, Test::class);
        $this->assertFalse($definitionBuilder->isShared());
        $definitionBuilder->setShared(true);
        $this->assertTrue($definitionBuilder->isShared());
    }

    public function testGetId(): void
    {
        $definitionBuilder = new Definition(Test::class, Test::class);
        $this->assertSame(Test::class, $definitionBuilder->getId());
    }

    public function testGetClass(): void
    {
        $definitionBuilder = new Definition(Test::class, Test::class);
        $this->assertSame(Test::class, $definitionBuilder->getClass());
    }

    public function testGetClassWhenClassDoesNotExists(): void
    {
        $definitionBuilder = new Definition(Test::class, 'test');

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('Class test does not exist.');

        $definitionBuilder->getClass();
    }

    public function testAddTag(): void
    {
        $definitionBuilder = new Definition('test', Test::class);
        $this->assertSame([], $definitionBuilder->getTags());
        $this->assertSame($definitionBuilder, $definitionBuilder->addTag('testing'));
        $this->assertSame(['testing'], $definitionBuilder->getTags());
    }

    public function testAddTags(): void
    {
        $definitionBuilder = new Definition('test', Test::class);
        $this->assertSame([], $definitionBuilder->getTags());
        $this->assertSame($definitionBuilder, $definitionBuilder->addTags(['test1', 'test2']));
        $this->assertSame(['test1', 'test2'], $definitionBuilder->getTags());
    }

    public function testHasTag(): void
    {
        $definitionBuilder = new Definition('test', Test::class);

        $this->assertFalse($definitionBuilder->hasTag('test1'));
        $this->assertFalse($definitionBuilder->hasTag('test2'));

        $this->assertSame($definitionBuilder, $definitionBuilder->addTags(['test1', 'test2']));

        $this->assertTrue($definitionBuilder->hasTag('test1'));
        $this->assertTrue($definitionBuilder->hasTag('test2'));
    }

    public function testAddTagTwice(): void
    {
        $definitionBuilder = new Definition('test', Test::class);

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('Tag "test1" already added.');

        $this->assertSame($definitionBuilder, $definitionBuilder->addTags(['test1', 'test1']));
    }

    public function testSetArgumentAndHasArgument(): void
    {
        $definitionBuilder = new Definition(Test::class, Test::class);
        $this->assertFalse($definitionBuilder->hasArgument('test'));
        $definitionBuilder->setArgument('test', 'value');
        $this->assertTrue($definitionBuilder->hasArgument('test'));
    }

    public function testSetArgumentTwice(): void
    {
        $definitionBuilder = new Definition(Test::class, Test::class);
        $definitionBuilder->setArgument('test', 'value');

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('Argument test already set.');

        $definitionBuilder->setArgument('test', 'value');
    }

    public function testSetArgumentsAndGetArguments(): void
    {
        $definitionBuilder = new Definition(Test::class, Test::class);

        $this->assertSame([], $definitionBuilder->getArguments());
        $this->assertFalse($definitionBuilder->hasArgument('test1'));
        $this->assertFalse($definitionBuilder->hasArgument('test2'));

        $definitionBuilder->setArguments(['test1' => 'value1', 'test2' => 'value2']);

        $this->assertTrue($definitionBuilder->hasArgument('test1'));
        $this->assertTrue($definitionBuilder->hasArgument('test2'));
        $this->assertSame(
            ['test1' => 'value1', 'test2' => 'value2'],
            $definitionBuilder->getArguments()
        );
    }

    public function testGetArgument(): void
    {
        $definitionBuilder = new Definition(Test::class, Test::class);
        $definitionBuilder->setArguments(['test1' => 'value1', 'test2' => 'value2']);
        $this->assertSame('value1', $definitionBuilder->getArgument('test1'));
        $this->assertSame('value2', $definitionBuilder->getArgument('test2'));
    }

    public function testGetArgumentWhenNotFound(): void
    {
        $definitionBuilder = new Definition(Test::class, Test::class);

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('Argument test not set.');

        $definitionBuilder->getArgument('test');
    }

    public function testIsResolvedAndSetResolved(): void
    {
        $definition = new Definition('test', Test::class);

        $this->assertFalse($definition->isResolved());

        $definition->setResolved(new TestExtended());

        $this->assertTrue($definition->isResolved());
    }

    public function testSetResolvedWhenAlreadySet(): void
    {
        $definition = new Definition('test', Test::class);

        $definition->setResolved(new TestExtended());

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage(
            sprintf(
                'Id %s is already resolved.',
                'test'
            )
        );

        // Set twice to provoke exception.
        $definition->setResolved(new TestExtended());
    }

    public function testGetResolvedWorks(): void
    {
        $definition = new Definition('test', Test::class);

        $testExtended = new TestExtended();

        $definition->setResolved($testExtended);

        $this->assertSame(
            $testExtended,
            $definition->getResolved()
        );
    }

    public function testGetResolvedWhenNotSet(): void
    {
        $definition = new Definition('test', Test::class);

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage(
            sprintf(
                'Id %s is not resolved.',
                'test'
            )
        );

        $definition->getResolved();
    }

    /**
     * @throws ReflectionException
     */
    public function testHasFactory(): void
    {
        $definition = new Definition(TestInterface::class, Test::class);

        $this->assertFalse($definition->hasFactory());

        $definition->setFactory(TestFactory::class);

        $this->assertTrue($definition->hasFactory());
    }

    /**
     * @throws ReflectionException
     */
    public function testGetFactory(): void
    {
        $definition = new Definition(TestInterface::class, Test::class);

        $definition->setFactory(TestFactory::class);

        $this->assertInstanceOf(
            Factory::class,
            $definition->getFactory()
        );
    }

    public function testGetFactoryWhenNoFactoryHasBeenSpecified(): void
    {
        $definition = new Definition(TestInterface::class, Test::class);

        $this->expectException(FactoryException::class);
        $this->expectExceptionMessage(
            sprintf(
                'No factory is set for %s',
                TestInterface::class
            )
        );

        $definition->getFactory();
    }

    /**
     * @throws ReflectionException
     */
    public function testSetFactoryClosure(): void
    {
        $definition = new Definition(TestInterface::class, Test::class);

        $this->assertFalse($definition->hasFactory());

        $definition->setFactory(function (ContainerInterface $container) {
            return new TestFactoryObject(
                $container->make(Test::class)
            );
        });

        $this->assertTrue($definition->hasFactory());

        $factory = $definition->getFactory();

        $this->assertTrue($factory->isClosureFactory());
        $this->assertFalse($factory->isInvokableFactory());
        $this->assertFalse($factory->isDynamicFactory());
        $this->assertFalse($factory->isStaticFactory());
    }

    /**
     * @throws ReflectionException
     */
    public function testSetFactoryInvokable(): void
    {
        $definition = new Definition(TestInterface::class, Test::class);

        $definition->setFactory(TestFactory::class);

        $factory = $definition->getFactory();

        $this->assertFalse($factory->isClosureFactory());
        $this->assertTrue($factory->isInvokableFactory());
        $this->assertFalse($factory->isDynamicFactory());
        $this->assertFalse($factory->isStaticFactory());
    }

    /**
     * @throws ReflectionException
     */
    public function testSetFactoryDynamic(): void
    {
        $definition = new Definition(TestInterface::class, Test::class);

        $definition->setFactory(TestFactory::class, 'createDynamic');

        $factory = $definition->getFactory();

        $this->assertFalse($factory->isClosureFactory());
        $this->assertFalse($factory->isInvokableFactory());
        $this->assertTrue($factory->isDynamicFactory());
        $this->assertFalse($factory->isStaticFactory());
    }

    /**
     * @throws ReflectionException
     */
    public function testSetFactoryStatic(): void
    {
        $definition = new Definition(TestInterface::class, Test::class);

        $definition->setFactory(TestFactory::class, '::createStatic');

        $factory = $definition->getFactory();

        $this->assertFalse($factory->isClosureFactory());
        $this->assertFalse($factory->isInvokableFactory());
        $this->assertFalse($factory->isDynamicFactory());
        $this->assertTrue($factory->isStaticFactory());
    }

    public function testSetFactoryStaticForDoubleColon(): void
    {
        $definition = new Definition(TestInterface::class, Test::class);

        $definition->setFactory(TestFactory::class . '::createStatic');

        $factory = $definition->getFactory();

        $this->assertFalse($factory->isClosureFactory());
        $this->assertFalse($factory->isInvokableFactory());
        $this->assertFalse($factory->isDynamicFactory());
        $this->assertTrue($factory->isStaticFactory());
    }

    /**
     * @throws ReflectionException
     */
    public function testSetFactoryWhenAlreadySet(): void
    {
        $definition = new Definition(TestInterface::class, Test::class);

        $definition->setFactory(TestFactory::class);

        $this->expectException(FactoryException::class);
        $this->expectExceptionMessage(
            sprintf(
                'Factory is already set for id "%s".',
                TestInterface::class
            )
        );

        $definition->setFactory(TestFactory::class);
    }

    /**
     * @throws ReflectionException
     */
    public function testSetFactoryWhenMethodIsStaticAndNotSpecifiedStatic(): void
    {
        $definition = new Definition(TestInterface::class, Test::class);

        $this->expectException(FactoryException::class);
        $this->expectExceptionMessage(
            sprintf(
                'Factory method "%s::%s" is static but definition specify not static. Id: "%s".',
                TestFactory::class,
                'createStatic',
                TestInterface::class
            )
        );

        $definition->setFactory(TestFactory::class, 'createStatic');
    }

    /**
     * @throws ReflectionException
     */
    public function testSetFactoryWhenMethodIsNotStaticAndSpecifiedStatic(): void
    {
        $definition = new Definition(TestInterface::class, Test::class);

        $this->expectException(FactoryException::class);
        $this->expectExceptionMessage(
            sprintf(
                'Factory method "%s::%s" is dynamic but definition specify static. Id: "%s".',
                TestFactory::class,
                'createDynamic',
                TestInterface::class
            )
        );

        $definition->setFactory(TestFactory::class, '::createDynamic');
    }

    /**
     * @throws ReflectionException
     */
    public function testSetFactoryWhenMethodDoesNotExist(): void
    {
        $definition = new Definition(TestInterface::class, Test::class);

        $this->expectException(FactoryException::class);
        $this->expectExceptionMessage(
            sprintf(
                'Factory class "%s" does not have method "%s". Id: "%s".',
                TestFactory::class,
                'unknown',
                TestInterface::class
            )
        );

        $definition->setFactory(TestFactory::class, 'unknown');
    }

    /**
     * @throws ReflectionException
     */
    public function testSetFactoryWhenClosureAndMethodIsSpecified(): void
    {
        $definition = new Definition(TestInterface::class, Test::class);

        $this->expectException(FactoryException::class);
        $this->expectExceptionMessage(
            sprintf(
                'It is not allowed to set method name "%s()" for a closure. Id: "%s".',
                'notAllowed',
                TestInterface::class
            )
        );

        $definition->setFactory(function (): object {
            return new stdClass();
        }, 'notAllowed');
    }

    /**
     * @throws ReflectionException
     */
    public function testSetFactoryWhenStaticFactoryMethodNotSpecified(): void
    {
        $definition = new Definition(TestInterface::class, Test::class);

        $this->expectException(FactoryException::class);
        $this->expectExceptionMessage(
            sprintf(
                'Factory method not specified for factory class "%s". Id: "%s".',
                TestFactory::class,
                TestInterface::class
            )
        );

        $definition->setFactory(TestFactory::class . '::');
    }

    /**
     * @throws ReflectionException
     */
    public function testSetFactoryWhenFactoryMethodNotSpecified(): void
    {
        $definition = new Definition(TestInterface::class, Test::class);

        $this->expectException(FactoryException::class);
        $this->expectExceptionMessage(
            sprintf(
                'Factory method not specified for factory class "%s". Id: "%s".',
                TestFactory::class,
                TestInterface::class
            )
        );

        $definition->setFactory(TestFactory::class, '::');
    }

    /**
     * @throws ReflectionException
     */
    public function testSetFactoryWhenFactoryClassDoesNotExist(): void
    {
        $definition = new Definition(TestInterface::class, Test::class);

        $this->expectException(FactoryException::class);
        $this->expectExceptionMessage(
            sprintf(
                'Factory class "%s" does not exist. Id: "%s".',
                'unknownClass',
                TestInterface::class
            )
        );

        $definition->setFactory('unknownClass');
    }
}
