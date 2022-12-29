<?php

declare(strict_types=1);

namespace Tests\CoRex\Container\Unit;

use CoRex\Container\ContainerBuilder;
use CoRex\Container\Exceptions\ContainerException;
use CoRex\Container\Exceptions\NotFoundException;
use PHPUnit\Framework\TestCase;
use Tests\CoRex\Container\Resource\Test;

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

    public function testHas(): void
    {
        $containerBuilder = new ContainerBuilder();
        $this->assertFalse($containerBuilder->has(Test::class));
        $containerBuilder->bind(Test::class, Test::class);
        $this->assertTrue($containerBuilder->has(Test::class));
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