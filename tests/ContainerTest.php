<?php

declare(strict_types=1);

namespace Tests\CoRex\Container;

use CoRex\Container\Container;
use CoRex\Container\Exceptions\NotFoundException;
use CoRex\Container\Helpers\Definition;
use CoRex\Helpers\Obj;
use PHPUnit\Framework\TestCase;
use ReflectionException;
use Tests\CoRex\Container\HelpersClasses\BadClass;
use Tests\CoRex\Container\HelpersClasses\BaseTest;
use Tests\CoRex\Container\HelpersClasses\BaseTestInterface;
use Tests\CoRex\Container\HelpersClasses\Test;
use Tests\CoRex\Container\HelpersClasses\TestContainerInjected;
use Tests\CoRex\Container\HelpersClasses\TestDependencyInjection;
use Tests\CoRex\Container\HelpersClasses\TestInjected;
use Tests\CoRex\Container\HelpersClasses\TestInjectedInterface;
use Tests\CoRex\Container\HelpersClasses\TestNoExtends;
use Tests\CoRex\Container\HelpersClasses\TestParameters;

class ContainerTest extends TestCase
{
    /**
     * Test.
     *
     * @throws ReflectionException
     */
    public function testConstructor(): void
    {
        $container = $this->container();
        $this->assertEquals([], Obj::getProperty('definitions', $container));
        $this->assertEquals([], Obj::getProperty('instances', $container));
    }

    /**
     * Test get instance.
     */
    public function testGetInstance(): void
    {
        $container = $this->container();
        $this->assertNotNull($container);
        $this->assertEquals(Container::class, get_class($container));
    }

    /**
     * Test clear.
     *
     * @throws NotFoundException
     */
    public function testClear(): void
    {
        $container = $this->container();
        $container->bind(Test::class);
        $container->clear();
        $this->assertEquals([], $container->getDefinitions());
    }

    /**
     * Test bind.
     *
     * @throws NotFoundException
     */
    public function testBind(): void
    {
        $container = $this->container();
        $container->bind(Test::class);
        $this->assertTrue($container->has(Test::class));
        $this->assertFalse($container->isShared(Test::class));
    }

    /**
     * Test bind already bound.
     *
     * @throws NotFoundException
     */
    public function testBindAlreadyBound(): void
    {
        $container = $this->container();

        $container->bindShared('test', Test::class);
        $container->make('test');

        $this->assertInstanceOf(Test::class, $container->get('test'));

        $container->bindShared('test', TestInjected::class);

        $this->assertInstanceOf(TestInjected::class, $container->get('test'));
    }

    /**
     * Test bind singleton.
     *
     * @throws NotFoundException
     */
    public function testBindSingleton(): void
    {
        $container = $this->container();
        $definition = $container->bindSingleton(Test::class);
        $this->assertInstanceOf(Definition::class, $definition);
        $this->assertTrue($container->has(Test::class));
        $this->assertTrue($container->isShared(Test::class));
    }

    /**
     * Test bind shared.
     *
     * @throws NotFoundException
     */
    public function testBindShared(): void
    {
        $container = $this->container();
        $definition = $container->bindShared(Test::class);
        $this->assertInstanceOf(Definition::class, $definition);
        $this->assertTrue($container->has(Test::class));
        $this->assertTrue($container->isShared(Test::class));
    }

    /**
     * Test is shared.
     *
     * @throws NotFoundException
     */
    public function testIsShared(): void
    {
        $container = $this->container();
        $container->bind(Test::class);
        $this->assertFalse($container->isShared(Test::class));

        $container->clear();
        $container->bindSingleton(Test::class);
        $this->assertTrue($container->isShared(Test::class));
    }

    /**
     * Test is noy shared.
     */
    public function testIsNotShared(): void
    {
        $container = $this->container();
        $this->assertFalse($container->isShared(Test::class));
    }

    /**
     * Test is singleton.
     *
     * @throws NotFoundException
     */
    public function testIsSingleton(): void
    {
        $container = $this->container();
        $container->bind(Test::class);
        $this->assertFalse($container->isSingleton(Test::class));

        $container->clear();
        $container->bindSingleton(Test::class);
        $this->assertTrue($container->isSingleton(Test::class));
    }

    /**
     * Test make.
     *
     * @throws NotFoundException
     */
    public function testMake(): void
    {
        $container = $this->container();
        $container->bind(BaseTestInterface::class, Test::class);
        $instance = $container->make(BaseTestInterface::class);
        $this->assertEquals(Test::class, get_class($instance));
    }

    /**
     * Test make with singleton.
     *
     * @throws NotFoundException
     */
    public function testMakeWithSingleton(): void
    {
        $container = $this->container();
        $container->bindSingleton(BaseTestInterface::class, Test::class);

        // Make instance 1 and set test value.
        $instance1 = $container->make(BaseTestInterface::class);
        $instance1->test = md5((string)mt_rand(1, 100000));

        // Make instance 2 and test previous set value.
        $instance2 = $container->make(BaseTestInterface::class);

        $this->assertEquals($instance1, $instance2);
    }

    /**
     * Test make closure.
     *
     * @throws NotFoundException
     */
    public function testMakeClosure(): void
    {
        $container = $this->container();
        $container->bindSingleton(
            'test',
            function () {
                return new Test();
            }
        );
        $instance = $container->make('test');
        $this->assertInstanceOf(Test::class, $instance);
    }

    /**
     * Test make container injected.
     *
     * @throws NotFoundException
     * @throws ReflectionException
     */
    public function testMakeContainerInjected(): void
    {
        $container = $this->container();
        $container->bindSingleton('test', TestContainerInjected::class);

        $instance = $container->make('test');

        $this->assertInstanceOf(TestContainerInjected::class, $instance);
        $this->assertInstanceOf(Container::class, Obj::getProperty('container', $instance));
    }

    /**
     * Test make closure class not found.
     *
     * @throws NotFoundException
     */
    public function testMakeClosureClassNotFound(): void
    {
        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Class test does not exist.');
        $container = $this->container();
        $container->bindSingleton(
            'test',
            function () {
                return 'unknown.class';
            }
        );
        $container->make('test');
    }

    /**
     * Test closure extends.
     *
     * @throws NotFoundException
     */
    public function testClosureExtends(): void
    {
        $container = $this->container();
        $container->bindSingleton(
            'test',
            function () {
                return new Test();
            }
        );
        $this->assertFalse($container->getDefinition('test')->extendsClass(BaseTest::class));
    }

    /**
     * Test closure implements.
     *
     * @throws NotFoundException
     */
    public function testClosureImplements(): void
    {
        $container = $this->container();
        $container->bindSingleton(
            'test',
            function () {
                return new Test();
            }
        );
        $this->assertFalse($container->getDefinition('test')->implementsInterface(BaseTestInterface::class));
    }

    /**
     * Test make concrete not found.
     *
     * @throws NotFoundException
     */
    public function testMakeConcreteNotFound(): void
    {
        $check = md5((string)mt_rand(1, 100000));
        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Class ' . $check . ' does not exist');
        $container = $this->container();
        $container->make($check);
    }

    /**
     * Test get.
     *
     * @throws NotFoundException
     */
    public function testGet(): void
    {
        $check = md5((string)mt_rand(1, 100000));
        $container = $this->container();
        $container->bind($check, Test::class);
        $instance = $container->get($check);
        $this->assertEquals(Test::class, get_class($instance));
    }

    /**
     * Test get not found.
     *
     * @throws NotFoundException
     */
    public function testGetNotFound(): void
    {
        $check = md5((string)mt_rand(1, 100000));
        $container = $this->container();

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage($check);

        $container->get($check);
    }

    /**
     * Test get class not exist.
     *
     * @throws NotFoundException
     */
    public function testGetClassNotFound(): void
    {
        $container = $this->container();
        $container->bind('bad.class', BadClass::class);
        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('bad.class');
        $container->get('bad.class');
    }

    /**
     * Test has.
     *
     * @throws NotFoundException
     */
    public function testHas(): void
    {
        $container = $this->container();
        $this->assertFalse($container->has(Test::class));
        $container->bind(Test::class);
        $this->assertTrue($container->has(Test::class));
    }

    /**
     * Test call abstract.
     *
     * @throws NotFoundException
     */
    public function testCallAbstract(): void
    {
        $check = md5((string)mt_rand(1, 100000));
        $container = $this->container();

        $container->bind('params', TestParameters::class)->setDefaultParameter('name', $check);

        $result = $container->call(
            'params',
            'getName',
            [
                'name' => $check
            ]
        );

        $this->assertEquals($check, $result);
    }

    /**
     * Test call object.
     *
     * @throws NotFoundException
     */
    public function testCallObject(): void
    {
        $check = md5((string)mt_rand(1, 100000));
        $container = $this->container();

        $result = $container->call(
            $this,
            'methodDependecyInjectionCheck',
            [
                'name' => $check
            ]
        );

        $this->assertInstanceOf(TestParameters::class, $result['object']);
        $this->assertEquals($check, $result['name']);
    }

    /**
     * Test call object not found.
     *
     * @throws NotFoundException
     */
    public function testCallObjectNotFound(): void
    {
        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Method unknownMethod does not exist');

        $check = md5((string)mt_rand(1, 100000));
        $container = $this->container();

        $container->call(
            $this,
            'unknownMethod',
            [
                'name' => $check
            ]
        );
    }

    /**
     * Test forget.
     *
     * @throws NotFoundException
     */
    public function testForget(): void
    {
        $container = $this->container();
        $container->bindSingleton(Test::class);
        $container->bindSingleton(TestNoExtends::class);

        $this->assertTrue($container->has(Test::class));
        $this->assertTrue($container->has(TestNoExtends::class));

        $container->make(Test::class);

        $container->forget(Test::class);

        $this->assertFalse($container->has(Test::class));
        $this->assertTrue($container->has(TestNoExtends::class));
    }

    /**
     * Test get definitions none.
     */
    public function testGetDefinitionsNone(): void
    {
        $this->assertEquals([], $this->container()->getDefinitions());
    }

    /**
     * Test get definitions one.
     *
     * @throws NotFoundException
     */
    public function testGetDefinitionsOne(): void
    {
        $container = $this->container();
        $container->bind(Test::class);
        $definition = new Definition(Test::class, Test::class, false);
        $this->assertEquals([Test::class => $definition], $container->getDefinitions());
    }

    /**
     * Test get definition.
     *
     * @throws NotFoundException
     */
    public function testGetDefinition(): void
    {
        $container = $this->container();
        $container->bind(Test::class);
        $definition = new Definition(Test::class, Test::class, false);
        $this->assertEquals($definition, $container->getDefinition(Test::class));
    }

    /**
     * Test get definition unknown.
     */
    public function testGetDefinitionUnknown(): void
    {
        $container = $this->container();
        $this->assertNull($container->getDefinition('unknown'));
    }

    /**
     * Test get abstracts.
     *
     * @throws NotFoundException
     */
    public function testGetAbstracts(): void
    {
        $container = $this->container();
        $this->assertEquals(0, count($container->getAbstracts()));

        $container->bind(Test::class);

        $this->assertEquals([Test::class], $container->getAbstracts());
    }

    /**
     * Test get instances.
     *
     * @throws NotFoundException
     */
    public function testGetInstances(): void
    {
        $container = $this->container();
        $this->assertEquals(0, count($container->getInstances()));

        $container->bind('test', Test::class);

        $this->assertEquals(0, count($container->getInstances()));

        $container->make('test');

        $this->assertEquals(['test'], $container->getAbstracts());
    }

    /**
     * Test resolved.
     *
     * @throws NotFoundException
     */
    public function testResolved(): void
    {
        $container = $this->container();
        $this->assertFalse($container->resolved(Test::class));
        $container->set(Test::class, new Test());
        $this->assertTrue($container->resolved(Test::class));
        $this->assertTrue($container->getDefinition(Test::class)->isShared());
    }

    /**
     * Test set instance.
     *
     * @throws NotFoundException
     */
    public function testSetInstance(): void
    {
        $container = $this->container();
        $container->bind(Test::class);
        $this->assertFalse($container->resolved(Test::class));
        $container->set(Test::class, new Test());
        $this->assertTrue($container->resolved(Test::class));
        $this->assertTrue($container->getDefinition(Test::class)->isShared());
    }

    /**
     * Test tag.
     *
     * @throws NotFoundException
     */
    public function testTag(): void
    {
        $container = $this->container();

        $container->bind('test', Test::class);

        $definition = $container->getDefinition('test');
        $this->assertNull($definition->getTag());

        $check = md5((string)mt_rand(1, 100000));
        $isTagged = $container->tag('test', $check);
        $this->assertTrue($isTagged);

        $definition = $container->getDefinition('test');
        $this->assertEquals($check, $definition->getTag());
    }

    /**
     * Test tag unknown.
     */
    public function testTagUnknown(): void
    {
        $container = $this->container();
        $check = md5((string)mt_rand(1, 100000));
        $isTagged = $container->tag('test', $check);
        $this->assertFalse($isTagged);
    }

    /**
     * Test run on extends.
     *
     * @throws NotFoundException
     * @throws ReflectionException
     */
    public function testRunOnExtends(): void
    {
        $container = $this->container();

        // Bind and check test1.
        $container->bindShared('test1', TestNoExtends::class);
        $test1 = $container->get('test1');
        $this->assertNull(Obj::getProperty('value', $test1));

        // Bind and check test2.
        $container->bindShared('test2', Test::class);
        $test2 = $container->get('test2');
        $this->assertNull(Obj::getProperty('value', $test2));

        $check = md5((string)mt_rand(1, 100000));
        $container->runOnExtends(
            BaseTest::class,
            'setTestValue',
            [
                'value' => $check
            ]
        );

        // Check test1.
        $test1 = $container->get('test1');
        $this->assertNull(Obj::getProperty('value', $test1));

        // Check test2.
        $test2 = $container->get('test2');
        $this->assertEquals($check, Obj::getProperty('value', $test2));
    }

    /**
     * Test run on interface.
     *
     * @throws NotFoundException
     * @throws ReflectionException
     */
    public function testRunOnInterface(): void
    {
        $container = $this->container();

        // Bind and check test1.
        $container->bindShared('test1', TestNoExtends::class);
        $test1 = $container->get('test1');
        $this->assertNull(Obj::getProperty('value', $test1));

        // Bind and check test2.
        $container->bindShared('test2', Test::class);
        $test2 = $container->get('test2');
        $this->assertNull(Obj::getProperty('value', $test2));

        $check = md5((string)mt_rand(1, 100000));
        $container->runOnInterface(
            BaseTestInterface::class,
            'setTestValue',
            [
                'value' => $check
            ]
        );

        // Check test1.
        $test1 = $container->get('test1');
        $this->assertNull(Obj::getProperty('value', $test1));

        // Check test2.
        $test2 = $container->get('test2');
        $this->assertEquals($check, Obj::getProperty('value', $test2));
    }

    /**
     * Test run on tag.
     *
     * @throws NotFoundException
     * @throws ReflectionException
     */
    public function testRunOnTag(): void
    {
        $container = $this->container();

        // Bind and check test1.
        $container->bindShared('test1', TestNoExtends::class)->setTag('tag1');
        $test1 = $container->get('test1');
        $this->assertNull(Obj::getProperty('value', $test1));

        // Bind and check test2.
        $container->bindShared('test2', Test::class)->setTag('tag2');
        $test2 = $container->get('test2');
        $this->assertNull(Obj::getProperty('value', $test2));

        $check = md5((string)mt_rand(1, 100000));
        $container->runOnTag(
            'tag2',
            'setTestValue',
            [
                'value' => $check
            ]
        );

        // Check test1.
        $test1 = $container->get('test1');
        $this->assertNull(Obj::getProperty('value', $test1));

        // Check test2.
        $test2 = $container->get('test2');
        $this->assertEquals($check, Obj::getProperty('value', $test2));
    }

    /**
     * Test get abstract instance make true/false.
     *
     * @throws NotFoundException
     * @throws ReflectionException
     */
    public function testGetAbstractInstance(): void
    {
        $container = $this->container();

        // Bind and check test.
        $container->bindShared('test', Test::class);

        $instance = Obj::callMethod(
            'getAbstractInstance',
            $container,
            [
                'abstract' => 'test',
                'make' => false
            ]
        );
        $this->assertNull($instance);

        $instance = Obj::callMethod(
            'getAbstractInstance',
            $container,
            [
                'abstract' => 'test',
                'make' => true
            ]
        );
        $this->assertInstanceOf(Test::class, $instance);
    }

    /**
     * Test get abstract instance has instance.
     *
     * @throws NotFoundException
     * @throws ReflectionException
     */
    public function testGetAbstractInstanceHasInstance(): void
    {
        $container = $this->container();

        // Bind and check test.
        $container->bindShared('test', Test::class);
        $container->make('test');

        $instance = Obj::callMethod(
            'getAbstractInstance',
            $container,
            [
                'abstract' => 'test',
                'make' => false
            ]
        );
        $this->assertInstanceOf(Test::class, $instance);
    }

    /**
     * Test dependency injection not found.
     *
     * @throws NotFoundException
     */
    public function testDependencyInjectionNotFound(): void
    {
        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Cannot instantiate interface ' . TestInjectedInterface::class);
        $container = $this->container();
        $container->bind(TestDependencyInjection::class);
        $container->make(TestDependencyInjection::class);
    }

    /**
     * Test dependency injection injected.
     *
     * @throws NotFoundException
     * @throws ReflectionException
     */
    public function testDependencyInjectionInjected(): void
    {
        $check = md5((string)mt_rand(1, 100000));
        $container = $this->container();
        $container->bind(TestInjectedInterface::class, TestInjected::class);
        $instance = $container->make(TestDependencyInjection::class, ['test' => $check]);
        $this->assertEquals(TestInjected::class, get_class(Obj::getProperty('testInjected', $instance)));
    }

    /**
     * Test dependency injection default value.
     *
     * @throws NotFoundException
     */
    public function testDependencyInjectionDefaultValue(): void
    {
        $check1 = md5((string)mt_rand(1, 100000));
        $container = $this->container();
        $definition = $container->bind('params', TestParameters::class);
        $definition->setDefaultParameter('name', $check1);
        $instance = $container->make('params');
        $this->assertEquals($check1, $instance->getName());
    }

    /**
     * Test dependency injection specified value.
     *
     * @throws NotFoundException
     */
    public function testDependencyInjectionSpecifiedValue(): void
    {
        $check1 = md5((string)mt_rand(1, 100000)) . '1';
        $check2 = md5((string)mt_rand(1, 100000)) . '2';
        $container = $this->container();
        $definition = $container->bind('params', TestParameters::class);
        $definition->setDefaultParameter('name', $check1);
        $instance = $container->make('params', ['name' => $check2]);
        $this->assertEquals($check2, $instance->getName());
    }

    /**
     * Test dependency injection forced value.
     *
     * @throws NotFoundException
     */
    public function testDependencyInjectionForcedValue(): void
    {
        $check1 = md5((string)mt_rand(1, 100000)) . '1';
        $check2 = md5((string)mt_rand(1, 100000)) . '2';
        $check3 = md5((string)mt_rand(1, 100000)) . '3';
        $container = $this->container();
        $definition = $container->bind('params', TestParameters::class);
        $definition->setDefaultParameter('name', $check1);
        $definition->setForcedParameter('name', $check3);
        $instance = $container->make('params', ['name' => $check2]);
        $this->assertEquals($check3, $instance->getName());
    }

    /**
     * Test new instance class not found.
     *
     * @throws ReflectionException
     */
    public function testNewInstanceClassNotFound(): void
    {
        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Class unknown.class does not exist');
        $container = $this->container();
        Obj::callMethod(
            'newInstance',
            $container,
            [
                'class' => 'unknown.class',
                'params' => []
            ]
        );
    }

    /**
     * Test newInstance reflection exception.
     *
     * @throws ReflectionException
     */
    public function testNewInstanceReflectionException(): void
    {
        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('fail.on.purpose');
        $container = $this->container();
        Obj::callMethod(
            'newInstance',
            $container,
            [
                'class' => BadClass::class,
                'params' => []
            ]
        );
    }

    /**
     * Test new instance missing parameters.
     *
     * @throws ReflectionException
     */
    public function testNewInstanceMissingParameters(): void
    {
        $message = 'Too few arguments to function ' . TestDependencyInjection::class . '::__construct(),' .
            ' 0 passed and exactly 2 expected';
        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage($message);
        $container = $this->container();
        Obj::callMethod(
            'newInstance',
            $container,
            [
                'class' => TestDependencyInjection::class,
                'params' => []
            ]
        );
    }

    /**
     * Method dpendency injection check.'
     *
     * @param TestParameters $testParameters
     * @param string|null $name
     * @return mixed[]
     */
    public function methodDependecyInjectionCheck(TestParameters $testParameters, ?string $name = null): array
    {
        return [
            'object' => $testParameters,
            'name' => $name
        ];
    }

    /**
     * Container.
     *
     * @return Container
     */
    private function container(): Container
    {
        $container = Container::getInstance();
        $container->clear();

        return $container;
    }
}