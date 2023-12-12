<?php

declare(strict_types=1);

namespace CoRex\Container;

use CoRex\Container\Definition\Definition;
use CoRex\Container\Definition\DefinitionInterface;
use CoRex\Container\Exceptions\ContainerException;
use CoRex\Container\Exceptions\NotFoundException;

final class ContainerBuilder implements ContainerBuilderInterface
{
    /** @var array<string, DefinitionInterface> */
    private array $definitions = [];

    /** @inheritDoc */
    public function bind(string $id, string $class): DefinitionInterface
    {
        if ($this->has($id)) {
            throw new ContainerException(sprintf('Id %s already bound', $id));
        }

        if (!class_exists($class)) {
            throw new NotFoundException(sprintf('Class %s not found.', $class));
        }

        $definition = new Definition($id, $class);
        $this->definitions[$id] = $definition;

        return $definition;
    }

    /** @inheritDoc */
    public function has(string $id): bool
    {
        return array_key_exists($id, $this->definitions);
    }

    /** @inheritDoc */
    public function set(string $id, object $object): void
    {
        if (!$this->has($id)) {
            throw new ContainerException(
                sprintf('Id %s must be bound before setting object.', $id)
            );
        }

        $definition = $this->getDefinition($id);
        $boundClass = $definition->getClass();

        if (!$object instanceof $boundClass) {
            throw new ContainerException(
                sprintf(
                    'Object is not same as or extends "%s".',
                    $boundClass
                )
            );
        }

        $definition->setResolved($object);
    }

    /** @inheritDoc */
    public function getIds(): array
    {
        return array_keys($this->definitions);
    }

    /** @inheritDoc */
    public function getTaggedIds(string $tag): array
    {
        $taggedIds = [];
        $ids = $this->getIds();
        foreach ($ids as $id) {
            if ($this->getDefinition($id)->hasTag($tag)) {
                $taggedIds[] = $id;
            }
        }

        return $taggedIds;
    }

    /** @inheritDoc */
    public function getDefinition(string $id): DefinitionInterface
    {
        if (!array_key_exists($id, $this->definitions)) {
            throw new NotFoundException(sprintf('Id %s not found.', $id));
        }

        return $this->definitions[$id];
    }
}