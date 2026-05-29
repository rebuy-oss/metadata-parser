<?php

declare(strict_types=1);

namespace Liip\MetadataParser\Metadata;

use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\Type\CollectionType;

/**
 * This property type can be merged with PropertyTypeClass<T>, provided that T is, inherits from, or is a parent class of {@see PropertyTypeIterable::traversableClass}
 * This property type can be merged with PropertyTypeIterable, if :
 *  - we're not merging a plain array PropertyTypeIterable into a hashmap one,
 *  - and the traversable classes of each are either not present on either sides, or are the same, or parent-child of one another
 *
 * @template T of \Traversable
 *
 * @extends AbstractPropertyType<CollectionType, bool>
 */
final class PropertyTypeIterable extends AbstractPropertyType
{
    /**
     * @param CollectionType<*> $type
     * @param class-string<T>|null $traversableClass
     * @param PropertyType<*> $subType
     */
    public function __construct(
        CollectionType $type,
        bool $nullable,
        private readonly PropertyType $subType,
        private readonly ?string $traversableClass = null,
    ) {
        parent::__construct($type, $nullable);
    }

    public function isHashmap(): bool
    {
        return !$this->typeInfo->isList();
    }

    /**
     * Returns the type of the next level, which could be an array or hashmap or another type.
     *
     * @return PropertyType<*>
     */
    public function getSubType(): PropertyType
    {
        return $this->subType;
    }

    /**
     * @return class-string<T>
     */
    public function getTraversableClass(): string
    {
        if (!$this->isTraversable()) {
            throw new \UnexpectedValueException("Iterable type '{$this}' is not traversable.");
        }

        return $this->traversableClass;
    }

    public function isTraversable(): bool
    {
        return null != $this->traversableClass;
    }

    /**
     * Goes down the type until it is not an array or hashmap anymore.
     *
     * @return PropertyType<*>
     */
    public function getLeafType(): PropertyType
    {
        $type = $this->getSubType();
        while ($type instanceof self) {
            $type = $type->getSubType();
        }

        return $type;
    }

    public function merge(PropertyType $other): PropertyType
    {
        $nullable = $this->isNullable() && $other->isNullable();
        $thisTraversableClass = $this->isTraversable() ? $this->getTraversableClass() : null;

        if ($other instanceof PropertyTypeUnknown) {
            return new self($this->typeInfo, $nullable, $this->getSubType(), $thisTraversableClass);
        }

        if ($this->isTraversable() && (($other instanceof PropertyTypeClass) && is_a($other->getClassName(), \Traversable::class, true))) {
            $commonClass = $this->findCommonTraversableClass($thisTraversableClass, $other->getClassName());

            return new self($this->typeInfo, $nullable, $this->getSubType(), $commonClass);
        }
        if (!$other instanceof self) {
            throw new \UnexpectedValueException(\sprintf('Can\'t merge type %s with %s, they must be the same or unknown', self::class, $other::class));
        }

        /*
         * We allow converting array to hashmap (but not the other way round).
         *
         * PHPDoc has no clear definition for hashmaps with string indexes, but JMS Serializer attributes do.
         */
        if ($this->isHashmap() && !$other->isHashmap()) {
            throw new \UnexpectedValueException(\sprintf('Can\'t merge type %s with %s, can\'t change hashmap into plain array', self::class, $other::class));
        }

        $otherTraversableClass = $other->isTraversable() ? $other->getTraversableClass() : null;
        $commonClass = $this->findCommonTraversableClass($thisTraversableClass, $otherTraversableClass);

        // Promote to hashmap when either side is one (list can upgrade to hashmap).
        $keyHolder = $this->isHashmap() ? $this : $other;

        if ($other->getSubType() instanceof PropertyTypeUnknown) {
            return new self($this->typeInfo, $nullable, $this->getSubType(), $commonClass);
        }

        if ($this->getSubType() instanceof PropertyTypeUnknown) {
            return new self($other->typeInfo, $nullable, $other->getSubType(), $commonClass);
        }

        return new self($keyHolder->typeInfo, $nullable, $this->getSubType()->merge($other->getSubType()), $commonClass);
    }

    /**
     * Find the most derived class that doesn't deny both class hints, meaning the most derived
     * between left and right if one is a child of the other
     */
    private function findCommonTraversableClass(?string $left, ?string $right): ?string
    {
        if (null === $right) {
            return $left;
        }
        if (null === $left) {
            return $right;
        }

        if (is_a($left, $right, true)) {
            return $left;
        }
        if (is_a($right, $left, true)) {
            return $right;
        }

        throw new \UnexpectedValueException("Traversable classes '{$left}' and '{$right}' do not match.");
    }
}
