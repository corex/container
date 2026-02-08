<?php

declare(strict_types=1);

namespace Tests\CoRex\Container\Resource;

use CoRex\Container\ContainerInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class TestFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container): TestFactoryObjectInterface
    {
        return self::createTestObject($container);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function createDynamic(ContainerInterface $container): TestFactoryObjectInterface
    {
        return self::createTestObject($container);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public static function createStatic(ContainerInterface $container): TestFactoryObjectInterface
    {
        return self::createTestObject($container);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private static function createTestObject(ContainerInterface $container): TestFactoryObjectInterface
    {
        /** @var TestInterface $test */
        $test = $container->get(TestInterface::class);

        return new TestFactoryObject($test);
    }
}