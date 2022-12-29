<?php

declare(strict_types=1);

namespace CoRex\Container\Exceptions;

use Psr\Container\ContainerExceptionInterface;

final class ContainerException extends \RuntimeException implements ContainerExceptionInterface
{
}