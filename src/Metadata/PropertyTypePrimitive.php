<?php

declare(strict_types=1);

namespace Liip\MetadataParser\Metadata;

use Symfony\Component\TypeInfo\Type\BuiltinType;
use Symfony\Component\TypeInfo\Type\NullableType;

/**
 * @extends AbstractPropertyType<BuiltinType|NullableType<BuiltinType>, bool>
 */
final class PropertyTypePrimitive extends AbstractPropertyType
{
    private const array TYPE_MAP = [
        'boolean' => 'bool',
        'integer' => 'int',
        'double' => 'float',
        'real' => 'float',
    ];

    private const array PRIMITIVE_TYPES = [
        'string',
        'int',
        'float',
        'bool',
        'null',
        'true',
        'false',
    ];

    /**
     * @param BuiltinType<*> $type
     */
    public function __construct(BuiltinType $type, bool $nullable)
    {
        if (!self::isTypePrimitive($type->getTypeIdentifier()->value)) {
            throw new \UnexpectedValueException(\sprintf('Given type "%s" is not primitive', $type));
        }

        parent::__construct($type, $nullable);
    }

    public function getTypeName(): string
    {
        return $this->typeInfo->getTypeIdentifier()->value;
    }

    public function merge(PropertyType $other): PropertyType
    {
        $nullable = $this->isNullable() && $other->isNullable();

        if ($other instanceof PropertyTypeUnknown) {
            return new self($this->typeInfo, $nullable);
        }
        if (!$other instanceof self) {
            throw new \UnexpectedValueException(\sprintf('Can\'t merge type %s with %s, they must be the same or unknown', self::class, $other::class));
        }
        if ($this->getTypeName() !== $other->getTypeName()) {
            throw new \UnexpectedValueException(\sprintf('Can\'t merge type %s with %s, they must be equal', self::class, $other::class));
        }

        return new self($this->typeInfo, $nullable);
    }

    public static function isTypePrimitive(string $typeName): bool
    {
        return \in_array(self::normalize($typeName), self::PRIMITIVE_TYPES, true);
    }

    public static function normalize(string $typeName): string
    {
        return self::TYPE_MAP[$typeName] ?? $typeName;
    }
}
