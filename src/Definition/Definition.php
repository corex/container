<?php

declare(strict_types=1);

namespace CoRex\Container\Definition;

use CoRex\Container\Exceptions\ContainerException;

final class Definition implements DefinitionInterface
{
    private string $id;
    private string $class;
    private bool $isShared = false;

    /** @var array<string> */
    private array $tags = [];

    /** @var array<string, mixed> */
    private array $arguments = [];

    public function __construct(string $id, string $class)
    {
        $this->id = $id;
        $this->class = $class;
    }

    public function setShared(bool $isShared): self
    {
        $this->isShared = $isShared;

        return $this;
    }

    public function isShared(): bool
    {
        return $this->isShared;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getClass(): string
    {
        if (!class_exists($this->class)) {
            throw new ContainerException(
                sprintf(
                    'Class %s does not exist.',
                    $this->class
                )
            );
        }

        return $this->class;
    }

    public function addTag(string $tag): DefinitionInterface
    {
        if ($this->hasTag($tag)) {
            throw new ContainerException(
                sprintf(
                    'Tag "%s" already added.',
                    $tag
                )
            );
        }

        $this->tags[] = $tag;

        return $this;
    }

    /** @inheritDoc */
    public function addTags(array $tags): DefinitionInterface
    {
        foreach ($tags as $tag) {
            $this->addTag($tag);
        }

        return $this;
    }

    public function hasTag(string $tag): bool
    {
        return in_array($tag, $this->tags, true);
    }

    /** @inheritDoc */
    public function getTags(): array
    {
        return $this->tags;
    }

    public function setArgument(string $name, mixed $value): self
    {
        if (array_key_exists($name, $this->arguments)) {
            throw new ContainerException(
                sprintf(
                    'Argument %s already set.',
                    $name
                )
            );
        }

        $this->arguments[$name] = $value;

        return $this;
    }

    /** @inheritDoc */
    public function setArguments(array $arguments): self
    {
        foreach ($arguments as $name => $value) {
            $this->setArgument((string)$name, $value);
        }

        return $this;
    }

    public function hasArgument(string $name): bool
    {
        return array_key_exists($name, $this->arguments);
    }

    public function getArgument(string $name): mixed
    {
        if (!$this->hasArgument($name)) {
            throw new ContainerException(
                sprintf(
                    'Argument %s not set.',
                    $name
                )
            );
        }

        return $this->arguments[$name];
    }

    /** @inheritDoc */
    public function getArguments(): array
    {
        return $this->arguments;
    }
}