<?php

declare(strict_types=1);

namespace Liip\MetadataParser;

use Liip\MetadataParser\Metadata\PropertyMetadata;
use Liip\MetadataParser\Metadata\PropertyTypeClass;
use Liip\MetadataParser\Metadata\PropertyTypeIterable;

final class RecursionContext implements \Stringable
{
    private const string MATCH_EVERYTHING = '*';

    /**
     * @var PropertyMetadata[]
     */
    private array $stack = [];

    public function __construct(private readonly string $root)
    {
    }

    public function __toString(): string
    {
        if (0 === \count($this->stack)) {
            return $this->root;
        }

        $stack = array_map(static fn (PropertyMetadata $propertyMetadata): string => $propertyMetadata->getSerializedName(), $this->stack);

        return \sprintf('%s->%s', $this->root, implode('->', $stack));
    }

    public function push(PropertyMetadata $property): self
    {
        $context = clone $this;
        $context->stack[] = $property;

        return $context;
    }

    /**
     * Check if we are at the specified stack.
     *
     * @see RecursionChecker
     *
     * @param string[] $stackToCheck List of optional root class and properties to go through
     */
    public function matches(array $stackToCheck): bool
    {
        if (0 === \count($stackToCheck) || \count($this->stack) + 1 < \count($stackToCheck)) {
            return false;
        }

        $current = [$this->root];
        foreach ($this->stack as $property) {
            $current[] = $property->getSerializedName();
        }

        foreach ($current as $i => $name) {
            if ($stackToCheck[0] === $name) {
                $valid = array_all($stackToCheck, static fn ($nameToCheck, $j): bool => !(self::MATCH_EVERYTHING !== $nameToCheck && ($current[$i + (int) $j] ?? null) !== $nameToCheck));
                if ($valid) {
                    return true;
                }
            }
        }

        return false;
    }

    public function countClassNames(string $className): int
    {
        $count = 0;
        if ($this->root === $className) {
            ++$count;
        }
        foreach ($this->stack as $property) {
            $type = $property->getType();
            if ($type instanceof PropertyTypeIterable) {
                $type = $type->getLeafType();
            }
            if ($type instanceof PropertyTypeClass && $type->getClassName() === $className) {
                ++$count;
            }
        }

        return $count;
    }
}
