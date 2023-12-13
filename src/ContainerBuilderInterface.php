<?php

declare(strict_types=1);

namespace CoRex\Container;

use CoRex\Container\Definition\DefinitionInterface;
use CoRex\Container\Exceptions\NotFoundException;

interface ContainerBuilderInterface
{
    /**
     * Bind class or closure.
     *
     * @param string $id
     * @param string $class
     * @return DefinitionInterface
     */
    public function bind(string $id, string $class): DefinitionInterface;

    /**
     * Bind class with class name as id.
     *
     * @param string $class
     * @return DefinitionInterface
     */
    public function bindClass(string $class): DefinitionInterface;

    /**
     * Bind by interface on class (Must have only 1 interface).
     *
     * @param string $class
     * @return DefinitionInterface
     */
    public function bindClassByInterface(string $class): DefinitionInterface;

    /**
     * Has definition.
     *
     * @param string $id Identifier of the definition to look for.
     *
     * @return bool
     */
    public function has(string $id): bool;

    /**
     * Set object for bound id.
     *
     * @param string $id
     * @param object $object
     * @return void
     */
    public function set(string $id, object $object): void;

    /**
     * Get ids.
     *
     * @return array<string>
     */
    public function getIds(): array;

    /**
     * Get tagged ids.
     *
     * @param string $tag
     * @return array<string>
     */
    public function getTaggedIds(string $tag): array;

    /**
     * Get definition.
     *
     * @param string $id
     * @return DefinitionInterface
     * @throws NotFoundException
     */
    public function getDefinition(string $id): DefinitionInterface;
}