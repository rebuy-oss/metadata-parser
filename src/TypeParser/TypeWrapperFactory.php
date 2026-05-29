<?php

declare(strict_types=1);

namespace Liip\MetadataParser\TypeParser;

use Liip\MetadataParser\Exception\InvalidTypeException;
use Liip\MetadataParser\Metadata\PropertyType;
use Liip\MetadataParser\Metadata\PropertyTypeClass;
use Liip\MetadataParser\Metadata\PropertyTypeDateTime;
use Liip\MetadataParser\Metadata\PropertyTypeEnum;
use Liip\MetadataParser\Metadata\PropertyTypeIterable;
use Liip\MetadataParser\Metadata\PropertyTypePrimitive;
use Liip\MetadataParser\Metadata\PropertyTypeUnknown;
use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\Type\BuiltinType;
use Symfony\Component\TypeInfo\Type\CollectionType;
use Symfony\Component\TypeInfo\Type\NullableType;
use Symfony\Component\TypeInfo\Type\ObjectType;
use Symfony\Component\TypeInfo\Type\UnionType;
use Symfony\Component\TypeInfo\Type\WrappingTypeInterface;
use Symfony\Component\TypeInfo\TypeIdentifier;

/**
 * @internal
 */
final class TypeWrapperFactory
{
    /**
     * @return PropertyType<*>
     */
    public function fromType(Type $type): PropertyType
    {
        if ($type instanceof NullableType) {
            return $this->fromTypeNullable($type->getWrappedType(), true);
        }

        return $this->fromTypeNullable($type, false);
    }

    /**
     * @return PropertyType<*>
     */
    private function fromTypeNullable(Type $inner, bool $nullable): PropertyType
    {
        return match (true) {
            $inner instanceof UnionType => $this->fromUnionType($inner, $nullable),
            $inner instanceof CollectionType => $this->fromCollectionType($inner, $nullable),
            $inner instanceof BuiltinType => $this->fromBuiltinType($inner, $nullable),
            $inner instanceof ObjectType => $this->fromObjectType($inner, $nullable),
            default => new PropertyTypeUnknown($nullable),
        };
    }

    /**
     * @param BuiltinType<*> $type
     *
     * @return PropertyType<*>
     */
    public function fromBuiltinType(BuiltinType $type, bool $nullable): PropertyType
    {
        $id = $type->getTypeIdentifier();

        if (TypeIdentifier::RESOURCE === $id) {
            throw new InvalidTypeException('Type "resource" is not supported');
        }

        if (\in_array($id, [TypeIdentifier::ARRAY, TypeIdentifier::ITERABLE], true)) {
            $subType = new PropertyTypeUnknown(false);

            return new PropertyTypeIterable(
                Type::list($subType->getTypeInfo()),
                $nullable,
                $subType,
            );
        }

        if (PropertyTypePrimitive::isTypePrimitive($id->value)) {
            return new PropertyTypePrimitive($type, $nullable);
        }

        if (\in_array($id, [TypeIdentifier::MIXED, TypeIdentifier::NULL], true)) {
            return new PropertyTypeUnknown(true);
        }

        return new PropertyTypeUnknown($nullable);
    }

    /**
     * @param ObjectType<*> $type
     *
     * @return PropertyType<*>
     */
    public function fromObjectType(ObjectType $type, bool $nullable): PropertyType
    {
        $className = $type->getClassName();

        if (PropertyTypeDateTime::isTypeDateTime($className)) {
            return new PropertyTypeDateTime(Type::object($className), $nullable);
        }

        if (enum_exists($className)) {
            $enumType = Type::enum($className);

            return new PropertyTypeEnum($enumType, $nullable);
        }

        return new PropertyTypeClass($type, $nullable);
    }

    /**
     * @param CollectionType<*> $type
     *
     * @return PropertyType<*>
     */
    private function fromCollectionType(CollectionType $type, bool $nullable): PropertyType
    {
        $subType = $this->fromType($type->getCollectionValueType());

        return new PropertyTypeIterable(
            $type,
            $nullable,
            $subType,
            $this->getTraversableClassFromCollectionType($type),
        );
    }

    /**
     * @param UnionType<*> $type
     *
     * @return PropertyType<*>
     */
    private function fromUnionType(UnionType $type, bool $nullable): PropertyType
    {
        $traversableClass = null;
        $mainTypes = [];

        foreach ($type->getTypes() as $memberType) {
            if ($memberType instanceof BuiltinType && \in_array($memberType->getTypeIdentifier(), [TypeIdentifier::NULL, TypeIdentifier::MIXED], true)) {
                $nullable = true;
            } elseif ($memberType instanceof CollectionType && $memberType->getWrappedType() instanceof ObjectType) {
                $traversableClass = $memberType->getWrappedType()->getClassName();
            } else {
                $mainTypes[] = $memberType;
            }
        }

        if (0 === \count($mainTypes)) {
            return new PropertyTypeUnknown($nullable);
        }
        if (\count($mainTypes) > 1) {
            throw new InvalidTypeException(\sprintf('Multiple types are not supported (%s)', $type));
        }

        $converted = $this->fromTypeNullable($mainTypes[0], $nullable);

        if (null !== $traversableClass && $converted instanceof PropertyTypeIterable) {
            $innerCollection = $converted->getTypeInfo();
            $innerCollection = $innerCollection instanceof NullableType ? $innerCollection->getWrappedType() : $innerCollection;

            return new PropertyTypeIterable(
                $innerCollection,
                $nullable,
                $converted->getSubType(),
                $traversableClass,
            );
        }

        return $converted;
    }

    /**
     * @return class-string|null
     */
    private function getTraversableClassFromCollectionType(Type $type): ?string
    {
        if ($type instanceof WrappingTypeInterface) {
            return $this->getTraversableClassFromCollectionType($type->getWrappedType());
        }

        if ($type instanceof ObjectType) {
            return $type->getClassName();
        }

        return null;
    }
}
