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
use Symfony\Component\TypeInfo\TypeResolver\TypeResolver;

/**
 * @internal
 */
final class PhpTypeParser
{
    private TypeResolver $stringTypeResolver;

    public function __construct(?TypeResolver $typeResolver = null)
    {
        $this->stringTypeResolver = $typeResolver ?? TypeResolver::create();
    }

    /**
     * @throws InvalidTypeException if an invalid type or multiple types were defined
     */
    public function parseAnnotationType(\ReflectionProperty|\ReflectionMethod $subject): PropertyType
    {
        try {
            $type = $this->stringTypeResolver->resolve($subject);
        } catch (\Throwable $e) {
            throw new InvalidTypeException(\sprintf('Could not parse type for "%s"', $subject->getName()), 0, $e);
        }

        return $this->convertSymfonyType($type);
    }

    /**
     * @throws InvalidTypeException if an invalid type was defined
     */
    public function parseReflectionType(\ReflectionType $reflType): PropertyType
    {
        if ($reflType instanceof \ReflectionNamedType) {
            return $this->createTypeFromReflectionName($reflType->getName(), $reflType->allowsNull());
        }

        throw new InvalidTypeException(\sprintf('No type information found, got %s but expected %s', \ReflectionType::class, \ReflectionNamedType::class));
    }

    private function createTypeFromReflectionName(string $rawType, bool $nullable): PropertyType
    {
        if ('resource' === $rawType) {
            throw new InvalidTypeException('Type "resource" is not supported');
        }

        if ('array' === $rawType) {
            return new PropertyTypeIterable(new PropertyTypeUnknown(false), false, $nullable);
        }

        if (PropertyTypePrimitive::isTypePrimitive($rawType)) {
            return new PropertyTypePrimitive($rawType, $nullable);
        }

        if (PropertyTypeDateTime::isTypeDateTime($rawType)) {
            return PropertyTypeDateTime::fromDateTimeClass($rawType, $nullable);
        }

        if (enum_exists($rawType)) {
            return new PropertyTypeEnum($rawType, $nullable);
        }

        return new PropertyTypeClass($rawType, $nullable);
    }

    private function convertSymfonyType(Type $type, bool $nullable = false): PropertyType
    {
        return match (true) {
            $type instanceof NullableType => $this->convertNullableType($type),
            $type instanceof UnionType => $this->convertUnionType($type, $nullable),
            $type instanceof CollectionType => $this->convertCollectionType($type, $nullable),
            $type instanceof BuiltinType => $this->convertBuiltinType($type, $nullable),
            $type instanceof ObjectType => $this->convertObjectType($type, $nullable),
            default => new PropertyTypeUnknown($nullable),
        };
    }

    private function convertNullableType(NullableType $type): PropertyType
    {
        return $this->convertSymfonyType($type->getWrappedType(), true);
    }

    private function convertUnionType(UnionType $type, bool $nullable): PropertyType
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

        $converted = $this->convertSymfonyType($mainTypes[0], $nullable);

        if (null !== $traversableClass && $converted instanceof PropertyTypeIterable) {
            return new PropertyTypeIterable(
                $converted->getSubType(),
                $converted->isHashmap(),
                $converted->isNullable(),
                $traversableClass,
            );
        }

        return $converted;
    }

    private function convertCollectionType(CollectionType $type, bool $nullable): PropertyType
    {
        $subType = $this->convertSymfonyType($type->getCollectionValueType());
        $keyType = $type->getCollectionKeyType();
        $hashmap = !$type->isList()
            && $keyType instanceof BuiltinType
            && \in_array($keyType->getTypeIdentifier(), [TypeIdentifier::STRING, TypeIdentifier::INT], true);

        return new PropertyTypeIterable($subType, $hashmap, $nullable, $this->getTraversableClassFrommCollectionType($type));
    }

    private function convertBuiltinType(BuiltinType $type, bool $nullable): PropertyType
    {
        $id = $type->getTypeIdentifier();

        if (TypeIdentifier::RESOURCE === $id) {
            throw new InvalidTypeException('Type "resource" is not supported');
        }

        if (PropertyTypePrimitive::isTypePrimitive($id->value)) {
            return new PropertyTypePrimitive($id->value, $nullable);
        }

        if (\in_array($id, [TypeIdentifier::ARRAY, TypeIdentifier::ITERABLE], true)) {
            return new PropertyTypeIterable(new PropertyTypeUnknown(false), false, $nullable);
        }

        if (\in_array($id, [TypeIdentifier::MIXED, TypeIdentifier::NULL], true)) {
            return new PropertyTypeUnknown(true);
        }

        return new PropertyTypeUnknown($nullable);
    }

    private function convertObjectType(ObjectType $type, bool $nullable): PropertyType
    {
        $className = $type->getClassName();

        if (PropertyTypeDateTime::isTypeDateTime($className)) {
            return PropertyTypeDateTime::fromDateTimeClass($className, $nullable);
        }

        if (enum_exists($className)) {
            return new PropertyTypeEnum($className, $nullable);
        }

        return new PropertyTypeClass($className, $nullable);
    }

    /**
     * @return class-string|null
     */
    private function getTraversableClassFrommCollectionType(Type $type): ?string
    {
        if ($type instanceof WrappingTypeInterface) {
            return $this->getTraversableClassFrommCollectionType($type->getWrappedType());
        }

        if ($type instanceof ObjectType) {
            return $type->getClassName();
        }

        return null;
    }
}
