<?php

declare(strict_types=1);

namespace Liip\MetadataParser\Metadata;

use Symfony\Component\TypeInfo\Type;

/**
 * @template T of Type
 */
interface PropertyType extends \Stringable
{
    /**
     * Whether this property can be nullified.
     *
     * This information is available even for unknown properties.
     */
    public function isNullable(): bool;

    /**
     * Creates a new instance with the given nullability
     */
    public function asNullable(bool $nullable = true): self;

    /**
     * Merges another property type into this one.
     *
     * @param PropertyType<*> $other
     *
     * @return PropertyType<*>
     *
     * @throws \UnexpectedValueException if the types are not compatible
     */
    public function merge(self $other): self;

    /**
     * @return T
     */
    public function getTypeInfo(): Type;
}
