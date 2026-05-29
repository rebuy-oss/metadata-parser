<?php

declare(strict_types=1);

namespace Liip\MetadataParser\TypeParser;

use Doctrine\Common\Collections\ArrayCollection;
use JMS\Serializer\Type\Parser;
use Liip\MetadataParser\Exception\InvalidTypeException;
use Liip\MetadataParser\Metadata\DateTimeOptions;
use Liip\MetadataParser\Metadata\PropertyType;
use Liip\MetadataParser\Metadata\PropertyTypeClass;
use Liip\MetadataParser\Metadata\PropertyTypeDateTime;
use Liip\MetadataParser\Metadata\PropertyTypeEnum;
use Liip\MetadataParser\Metadata\PropertyTypeIterable;
use Liip\MetadataParser\Metadata\PropertyTypePrimitive;
use Liip\MetadataParser\Metadata\PropertyTypeUnknown;
use Liip\MetadataParser\Metadata\SerializationMode;
use Symfony\Component\TypeInfo\Type;

final class JMSTypeParser
{
    private const string TYPE_ARRAY = 'array';
    private const string TYPE_ENUM = 'enum';
    private const string TYPE_ARRAY_COLLECTION = 'ArrayCollection';
    private const string TYPE_GENERATOR = 'Generator';
    private const string TYPE_ARRAY_ITERATOR = 'ArrayIterator';
    private const string TYPE_ITERATOR = 'Iterator';
    private const string TYPE_DATETIME_INTERFACE = 'DateTimeInterface';

    private Parser $jmsTypeParser;

    public function __construct()
    {
        $this->jmsTypeParser = new Parser();
    }

    /**
     * @return PropertyType<*>
     */
    public function parse(string $rawType, \ReflectionProperty|\ReflectionMethod|null $reflection = null, bool $isSubType = false): PropertyType
    {
        if ('' === $rawType) {
            return new PropertyTypeUnknown(true);
        }

        return $this->parseType($this->jmsTypeParser->parse($rawType), $reflection, $isSubType);
    }

    /**
     * @param array<int|string, mixed> $typeInfo
     *
     * @return PropertyType<*>
     */
    private function parseType(array $typeInfo, \ReflectionProperty|\ReflectionMethod|null $reflection, bool $isSubType = false): PropertyType
    {
        $typeInfo = array_merge(
            [
                'name' => null,
                'params' => [],
            ],
            $typeInfo
        );

        // JMS types are nullable except if it's a sub type (part of array)
        $nullable = !$isSubType;

        if (0 === \count($typeInfo['params']) && self::TYPE_ENUM !== $typeInfo['name']) {
            if (self::TYPE_ARRAY === $typeInfo['name']) {
                $subType = new PropertyTypeUnknown(false);
                $typeInfo = Type::list(Type::mixed());

                return new PropertyTypeIterable($typeInfo, $nullable, $subType);
            }

            if (PropertyTypePrimitive::isTypePrimitive($typeInfo['name'])) {
                return new PropertyTypePrimitive(Type::builtin(PropertyTypePrimitive::normalize($typeInfo['name'])), $nullable);
            }
            if (PropertyTypeDateTime::isTypeDateTime($typeInfo['name'])) {
                return new PropertyTypeDateTime(Type::object($typeInfo['name']), $nullable);
            }

            return new PropertyTypeClass(Type::object($typeInfo['name']), $nullable);
        }

        $traversableClass = $this->getTraversableClass($typeInfo['name']);
        if (self::TYPE_ARRAY === $typeInfo['name'] || $traversableClass) {
            if (1 === \count($typeInfo['params'])) {
                $subType = $this->parseType($typeInfo['params'][0], $reflection, true);

                return new PropertyTypeIterable(
                    Type::list($subType->getTypeInfo()),
                    $nullable,
                    $subType,
                    $traversableClass,
                );
            }
            if (2 === \count($typeInfo['params'])) {
                $key = Type::builtin($typeInfo['params'][0]['name']);
                $subType = $this->parseType($typeInfo['params'][1], $reflection, true);

                return new PropertyTypeIterable(
                    Type::array($subType->getTypeInfo(), $key),
                    $nullable,
                    $subType,
                    $traversableClass,
                );
            }

            throw new InvalidTypeException(\sprintf('JMS property type array can\'t have more than 2 parameters (%s)', var_export($typeInfo, true)));
        }

        if (PropertyTypeDateTime::isTypeDateTime($typeInfo['name'])) {
            // the case of datetime without params is already handled above, we know we have params
            $serializeFormat = $typeInfo['params'][0] ?: null;
            // {@link \JMS\Serializer\Handler\DateHandler} of jms/serializer defaults to using the serialization format as a deserialization format if none was supplied...
            $deserializeFormats = ($typeInfo['params'][2] ?? null) ?: $serializeFormat;
            // ... and always converts single strings to arrays
            $deserializeFormats = \is_string($deserializeFormats) ? [$deserializeFormats] : $deserializeFormats;
            // Jms defaults to DateTime when given DateTimeInterface despite the documentation saying DateTimeImmutable, {@see \JMS\Serializer\Handler\DateHandler} in jms/serializer
            $className = (self::TYPE_DATETIME_INTERFACE === $typeInfo['name']) ? \DateTime::class : $typeInfo['name'];

            return new PropertyTypeDateTime(
                Type::object($className),
                $nullable,
                new DateTimeOptions(
                    $serializeFormat,
                    ($typeInfo['params'][1] ?? null) ?: null,
                    $deserializeFormats,
                )
            );
        }

        if (self::TYPE_ENUM === $typeInfo['name']) {
            $enumType = $this->getEnumType($typeInfo, $reflection);

            $serializationMode = $this->getEnumSerializationMode($enumType, $typeInfo['params']);

            return new PropertyTypeEnum(Type::enum($enumType), $nullable, $serializationMode);
        }

        throw new InvalidTypeException(\sprintf('Unknown JMS property found (%s)', var_export($typeInfo, true)));
    }

    private function getTraversableClass(string $name): ?string
    {
        return match ($name) {
            self::TYPE_ARRAY_COLLECTION => ArrayCollection::class,
            self::TYPE_GENERATOR => \Generator::class,
            self::TYPE_ARRAY_ITERATOR, self::TYPE_ITERATOR => \ArrayIterator::class,
            default => is_a($name, \Traversable::class, true) ? $name : null,
        };
    }

    /**
     * @param array<int|string, mixed> $typeParams
     */
    private function getEnumSerializationMode(string $enumType, array $typeParams): ?SerializationMode
    {
        $mode = $typeParams[1] ?? null;
        if (null === $mode) {
            return null;
        }

        $serializationMode = SerializationMode::tryFrom($mode);
        if (SerializationMode::Value === $serializationMode && !is_a($enumType, \BackedEnum::class, true)) {
            throw new InvalidTypeException(\sprintf('The type "%s" is not a backed enum, thus you cannot use "value" as serialization mode for its value.', $enumType));
        }

        return $serializationMode;
    }

    /**
     * @param array<int|string, mixed> $typeInfo
     */
    private function getEnumType(array $typeInfo, \ReflectionProperty|\ReflectionMethod|null $reflection): string
    {
        $enumType = $typeInfo['params'][0] ?? null;
        $enumType = $enumType['name'] ?? $enumType;
        if (null !== $enumType) {
            return $enumType;
        }

        $class = null === $reflection ? null : $reflection::class;
        $type = match ($class) {
            \ReflectionMethod::class => $reflection->getReturnType(),
            \ReflectionProperty::class => $reflection->getType(),
            default => null,
        };

        if (!$type instanceof \ReflectionNamedType) {
            throw new InvalidTypeException('Can not determine enum type from reflection, please define one by using "enum<MyEnum>"');
        }

        return $type->getName();
    }
}
