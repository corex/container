# Simple Dependency Injection Container

![License](https://img.shields.io/packagist/l/corex/container.svg)
![Build Status](https://travis-ci.org/corex/container.svg?branch=master)
![codecov](https://codecov.io/gh/corex/container/branch/master/graph/badge.svg)


- Support for PSR-11 Container Interface.
- Support for setting default parameters on definitions.
- Support for setting forced parameters on definitions.
- Support for setting tag in definitions.
- Support for calling method on definitions which extends specific class.
- Support for calling method on definitions which implements specific interface.
- Support for calling method on definitions which has specific tag.
- Not depending on other packages in production (except psr/container).


### A few examples

Create a container.
```php
// Create new container.
$container = new Container();

// Create/use existing container.
$container = Container::getInstance();
```


#### Make a class without binding.
```php
$myClass = Container::getInstance()->make(MyClass::class);
```
Type-hints will be resolved if they are bound in advance.


#### Make a class with binding and parameters.
```php
$container = Container::getInstance();

$container->bind('myClass', MyClass::class)
    ->setDefaultParameter('firstname', 'Roger');

$myClass = $container->make('myClass', [
    'lastname' => 'Moore'
]);
```


#### Call a method on abstract with parameters
```php
$container = Container::getInstance();

$container->bind('myClass', MyClass::class);

// No need to bind if object is specified instead of abstract.
$result = $container->call('myClass', 'myMethod', [
    'firstname' => 'Roger',
    'lastname' => 'Moore'
]);
```


#### Parameters
Parameters will be resolved in following order:
1. Type-hint ContainerInterface will be resolved to instance of container.
2. Default parameters specified on definition.
3. Default parameters in constructor/method.
4. Specified parameters when calling make() and not already resolved.
5. Forced parameters specified on definition.

Note: default parameters for type-hints will be ignored.
