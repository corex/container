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

    public function has(string $id): bool
    {
        return array_key_exists($id, $this->definitions);
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

    public function getDefinition(string $id): DefinitionInterface
    {
        if (!array_key_exists($id, $this->definitions)) {
            throw new NotFoundException(sprintf('Id %s not found.', $id));
        }

        return $this->definitions[$id];
    }
}