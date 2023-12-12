<?php

declare(strict_types=1);

namespace CoRex\Container\Definition;

interface DefinitionInterface
{
    /**
     * Set shared.
     *
     * @param bool $isShared
     */
    public function setShared(bool $isShared): self;

    /**
     * Is shared.
     *
     * @return bool
     */
    public function isShared(): bool;

    /**
     * Get id.
     *
     * @return string
     */
    public function getId(): string;

    /**
     * Get class.
     *
     * @return string
     */
    public function getClass(): string;

    /**
     * Add tag.
     *
     * @param string $tag
     * @return $this
     */
    public function addTag(string $tag): self;

    /**
     * Add tags.
     *
     * @param array<string> $tags
     * @return $this
     */
    public function addTags(array $tags): self;

    /**
     * Has tag.
     *
     * @param string $tag
     * @return bool
     */
    public function hasTag(string $tag): bool;

    /**
     * Get tags.
     *
     * @return array<string>
     */
    public function getTags(): array;

    /**
     * Set argument.
     *
     * @param string $name
     * @param mixed $value
     * @return $this
     */
    public function setArgument(string $name, mixed $value): self;

    /**
     * Set arguments.
     *
     * @param array<mixed, mixed> $arguments
     * @return $this
     */
    public function setArguments(array $arguments): self;

    /**
     * Has argument.
     *
     * @param string $name
     * @return bool
     */
    public function hasArgument(string $name): bool;

    /**
     * Get argument.
     *
     * @param string $name
     * @return mixed
     */
    public function getArgument(string $name): mixed;

    /**
     * Get arguments.
     *
     * @return array<string, mixed>
     */
    public function getArguments(): array;

    /**
     * Is resolved.
     *
     * @return bool
     */
    public function isResolved(): bool;

    /**
     * Set resolved and set as shared.
     *
     * @param object $object
     * @return void
     */
    public function setResolved(object $object): void;

    /**
     * Get resolved.
     *
     * @return object
     */
    public function getResolved(): object;
}