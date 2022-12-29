# Simple Dependency Injection Container

![license](https://img.shields.io/github/license/corex/container?label=license)
![build](https://github.com/corex/container/workflows/build/badge.svg?branch=main)
[![Code Coverage](https://img.shields.io/endpoint?url=https://gist.githubusercontent.com/corex/2a65b73db868d3be461dede9b1d5ceba/raw/test-coverage__master.json)](https://github.com/corex/container/actions)
[![PHPStan Level](https://img.shields.io/endpoint?url=https://gist.githubusercontent.com/corex/2a65b73db868d3be461dede9b1d5ceba/raw/phpstan-level__master.json)](https://github.com/corex/container/actions)


> **Breaking changes** - this package has been rewritten from scratch to be more strict and simple to use.

- Support for PSR-11 Container Interface.
- Support for setting default parameters on definitions.

## A few examples


### Make a class without binding.
```php
$myClass = (new Container())->make(MyClass::class);
```
Type-hints will be resolved if they are bound in advance.


### Make a class with binding and parameters.
```php
$container = new Container();

$container->bind('myClass', MyClass::class)
    ->setArgument('firstname', 'Roger');

$myClass = $container->make('myClass', [
    'lastname' => 'Moore'
]);
```


## Parameters
Parameters will be resolved in following order:
1. Type-hint ContainerInterface will be resolved to instance of container.
2. Default parameters specified on definition.
3. Default parameters in constructor/method.
4. Specified parameters when calling make() and not already resolved.
