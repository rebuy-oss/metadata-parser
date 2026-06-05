<?php

declare(strict_types=1);

namespace Liip\MetadataParser\TypeParser;

use Liip\MetadataParser\Exception\InvalidTypeException;
use Liip\MetadataParser\Metadata\PropertyType;
use Liip\MetadataParser\Metadata\PropertyTypePrimitive;
use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\TypeResolver\TypeResolver;

/**
 * @internal
 */
final readonly class PhpTypeParser
{
    private TypeResolver $stringTypeResolver;

    private TypeWrapperFactory $wrapperFactory;

    public function __construct(?TypeResolver $typeResolver = null, ?TypeWrapperFactory $wrapperFactory = null)
    {
        $this->stringTypeResolver = $typeResolver ?? TypeResolver::create();
        $this->wrapperFactory = $wrapperFactory ?? new TypeWrapperFactory();
    }

    /**
     * @return PropertyType<*>
     *
     * @throws InvalidTypeException if an invalid type or multiple types were defined
     */
    public function parseAnnotationType(\ReflectionProperty|\ReflectionMethod $subject): PropertyType
    {
        try {
            $type = $this->stringTypeResolver->resolve($subject);
        } catch (\Throwable $e) {
            throw new InvalidTypeException(\sprintf('Could not parse type for "%s"', $subject->getName()), 0, $e);
        }

        return $this->wrapperFactory->fromType($type);
    }

    /**
     * @return PropertyType<*>
     *
     * @throws InvalidTypeException if an invalid type was defined
     */
    public function parseReflectionType(\ReflectionType $reflType): PropertyType
    {
        if (!$reflType instanceof \ReflectionNamedType) {
            throw new InvalidTypeException(\sprintf('No type information found, got %s but expected %s', \ReflectionType::class, \ReflectionNamedType::class));
        }

        $name = $reflType->getName();

        if ('resource' === $name) {
            throw new InvalidTypeException('Type "resource" is not supported');
        }

        $type = match (true) {
            'array' === $name, 'iterable' === $name => Type::builtin($name),
            PropertyTypePrimitive::isTypePrimitive($name) => Type::builtin(PropertyTypePrimitive::normalize($name)),
            default => Type::object($name),
        };

        if ($reflType->allowsNull()) {
            $type = Type::nullable($type);
        }

        return $this->wrapperFactory->fromType($type);
    }
}
