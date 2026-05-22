<?php

declare(strict_types=1);

namespace Liip\MetadataParser\Metadata;

/**
 * A single property represents one item of a class that is serialized to a specific name.
 */
abstract class AbstractPropertyMetadata implements \JsonSerializable, \Stringable
{
    /**
     * @var string[]
     */
    private array $groups = [];

    private PropertyAccessor $accessor;

    private VersionRange $versionRange;

    private ?int $maxDepth = null;

    /**
     * Hashmap of custom information about this property.
     *
     * @var mixed[]
     */
    private array $customInformation = [];

    /**
     * @param string $name Name of the property in PHP or the method name for a virtual property
     */
    public function __construct(private readonly string $name, public bool $readOnly, public bool $public)
    {
        $this->accessor = PropertyAccessor::none();
        $this->versionRange = VersionRange::all();
    }

    public function __toString(): string
    {
        return $this->name;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isReadOnly(): bool
    {
        return $this->readOnly;
    }

    public function isPublic(): bool
    {
        return $this->public;
    }

    /**
     * @return string[]
     */
    public function getGroups(): array
    {
        return $this->groups;
    }

    public function getAccessor(): PropertyAccessor
    {
        return $this->accessor;
    }

    public function getVersionRange(): VersionRange
    {
        return $this->versionRange;
    }

    public function hasCustomInformation(string $key): bool
    {
        return \array_key_exists($key, $this->customInformation);
    }

    /**
     * Get the value stored as custom information, if it exists.
     *
     * @return mixed The information in whatever format it has been set
     *
     * @throws \InvalidArgumentException if no such custom value is available for this property
     */
    public function getCustomInformation(string $key): mixed
    {
        if (!\array_key_exists($key, $this->customInformation)) {
            throw new \InvalidArgumentException(\sprintf('Property %s has no custom information %s', $this->name, $key));
        }

        return $this->customInformation[$key];
    }

    /**
     * Hashmap of custom information with the key as index and the information as value.
     *
     * @return mixed[]
     */
    public function getAllCustomInformation(): array
    {
        return $this->customInformation;
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        $data = [
            'name' => $this->name,
            'is_public' => $this->public,
            'is_read_only' => $this->readOnly,
            'max_depth' => $this->maxDepth,
        ];

        if (\count($this->groups) > 0) {
            $data['groups'] = $this->groups;
        }
        if ($this->accessor->isDefined()) {
            $data['accessor'] = $this->accessor;
        }
        if ($this->versionRange->isDefined()) {
            $data['version'] = $this->versionRange;
        }
        if (\count($this->customInformation)) {
            $data['custom_information'] = array_map(
                static function ($information) {
                    if (!\is_object($information) || $information instanceof \JsonSerializable) {
                        return $information;
                    }

                    return '[non JsonSerializable custom information]';
                },
                $this->customInformation
            );
        }

        return $data;
    }

    abstract public function getType(): PropertyType;

    protected function setVersionRange(VersionRange $version): void
    {
        $this->versionRange = $version;
    }

    protected function setAccessor(PropertyAccessor $accessor): void
    {
        $this->accessor = $accessor;
    }

    /**
     * @param string[] $groups
     */
    protected function setGroups(array $groups): void
    {
        $this->groups = $groups;
    }

    protected function setReadOnly(bool $readOnly): void
    {
        $this->readOnly = $readOnly;
    }

    protected function setPublic(bool $public): void
    {
        $this->public = $public;
    }

    protected function setCustomInformation(string $key, mixed $value): void
    {
        $this->customInformation[$key] = $value;
    }

    protected function getMaxDepth(): ?int
    {
        return $this->maxDepth;
    }

    protected function setMaxDepth(?int $maxDepth): void
    {
        $this->maxDepth = $maxDepth;
    }
}
