<?php

declare(strict_types=1);

namespace Liip\MetadataParser\Metadata;

use Symfony\Component\TypeInfo\Type\BackedEnumType;
use Symfony\Component\TypeInfo\Type\EnumType;

/**
 * @extends PropertyTypeClass<EnumType|BackedEnumType>
 */
final class PropertyTypeEnum extends PropertyTypeClass
{
    /**
     * @param EnumType<*>|BackedEnumType<*, *> $type
     */
    public function __construct(
        EnumType|BackedEnumType $type,
        bool $nullable,
        private readonly ?SerializationMode $serializationMode = null,
    ) {
        parent::__construct($type, $nullable);
    }

    public function getBackingType(): ?string
    {
        if ($this->typeInfo instanceof BackedEnumType) {
            return $this->typeInfo->getBackingType()->getTypeIdentifier()->value;
        }

        return null;
    }

    public function isBackedEnum(): bool
    {
        return $this->typeInfo instanceof BackedEnumType;
    }

    public function getSerializationMode(): ?SerializationMode
    {
        return $this->serializationMode;
    }

    public function shouldSerializeAsValue(): bool
    {
        return $this->isBackedEnum() && SerializationMode::Name !== $this->serializationMode;
    }

    public function merge(PropertyType $other): PropertyType
    {
        $nullable = $this->isNullable() && $other->isNullable();

        if ($other instanceof PropertyTypeUnknown) {
            return new self($this->typeInfo, $nullable, $this->serializationMode);
        }

        if (!$other instanceof self) {
            throw new \UnexpectedValueException(\sprintf('Can\'t merge type %s with %s, they must be the same or unknown', self::class, $other::class));
        }

        if ($this->getClassName() !== $other->getClassName()) {
            throw new \UnexpectedValueException(\sprintf('Can\'t merge type %s with %s, they must be equal', self::class, $other::class));
        }

        if (null !== $this->serializationMode && null !== $other->getSerializationMode() && $this->serializationMode !== $other->getSerializationMode()) {
            throw new \UnexpectedValueException(\sprintf('Can\'t merge type %s with conflicting serialization modes "%s" and "%s"', self::class, $this->serializationMode->value, $other->getSerializationMode()->value));
        }

        $serializationMode = $this->serializationMode ?? $other->getSerializationMode();

        return new self($this->typeInfo, $nullable, $serializationMode);
    }
}
