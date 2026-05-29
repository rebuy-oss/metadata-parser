<?php

declare(strict_types=1);

namespace Liip\MetadataParser\Metadata;

use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\Type\NullableType;

/**
 * @template T of Type
 * @template TNullable as bool
 *
 * @implements PropertyType<T>
 */
abstract class AbstractPropertyType implements PropertyType
{
    /**
     * @param T         $typeInfo
     * @param TNullable $nullable
     */
    protected function __construct(
        protected readonly Type $typeInfo,
        protected readonly bool $nullable,
    ) {
    }

    public function __toString(): string
    {
        return $this->getTypeInfo()->__toString();
    }

    public function isNullable(): bool
    {
        return $this->nullable;
    }

    /**
     * @return (TNullable is true ? NullableType<T> : T)
     */
    public function getTypeInfo(): Type
    {
        return $this->nullable ? Type::nullable($this->typeInfo) : $this->typeInfo;
    }
}
