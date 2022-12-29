<?php

declare(strict_types=1);

namespace Tests\CoRex\Container\Definition;

use CoRex\Container\Definition\Definition;
use CoRex\Container\Exceptions\ContainerException;
use PHPUnit\Framework\TestCase;
use Tests\CoRex\Container\Resource\Test;
use Tests\CoRex\Container\Resource\TestInterface;

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
}
