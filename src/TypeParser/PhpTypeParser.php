<?php

declare(strict_types=1);

namespace Liip\MetadataParser\TypeParser;

use Liip\MetadataParser\Exception\InvalidTypeException;
use Liip\MetadataParser\Metadata\PropertyType;
use Liip\MetadataParser\Metadata\PropertyTypePrimitive;
use Liip\MetadataParser\TypeResolver\LiipStringTypeResolver;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\TypeContext\TypeContextFactory;
use Symfony\Component\TypeInfo\TypeResolver\PhpDocAwareReflectionTypeResolver;
use Symfony\Component\TypeInfo\TypeResolver\ReflectionParameterTypeResolver;
use Symfony\Component\TypeInfo\TypeResolver\ReflectionPropertyTypeResolver;
use Symfony\Component\TypeInfo\TypeResolver\ReflectionReturnTypeResolver;
use Symfony\Component\TypeInfo\TypeResolver\ReflectionTypeResolver;
use Symfony\Component\TypeInfo\TypeResolver\StringTypeResolver;
use Symfony\Component\TypeInfo\TypeResolver\TypeResolver;
use Symfony\Component\TypeInfo\TypeResolver\TypeResolverInterface;

/**
 * @internal
 */
final readonly class PhpTypeParser
{
    private TypeResolverInterface $stringTypeResolver;

    private TypeWrapperFactory $wrapperFactory;

    public function __construct(?TypeResolverInterface $typeResolver = null, ?TypeWrapperFactory $wrapperFactory = null)
    {
        $this->stringTypeResolver = $typeResolver ?? $this->createTypeResolver();
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

    private function createTypeResolver(): TypeResolverInterface
    {
        if (!class_exists(PhpDocParser::class)) {
            return TypeResolver::create();
        }

        $stringTypeResolver = new StringTypeResolver();
        $typeContextFactory = new TypeContextFactory($stringTypeResolver);
        $liipStringTypeResolver = new LiipStringTypeResolver($stringTypeResolver);
        $reflectionTypeResolver = new ReflectionTypeResolver();

        $resolvers = [
            \ReflectionType::class => $reflectionTypeResolver,
            \ReflectionParameter::class => new PhpDocAwareReflectionTypeResolver(
                new ReflectionParameterTypeResolver($reflectionTypeResolver, $typeContextFactory),
                $liipStringTypeResolver,
                $typeContextFactory,
            ),
            \ReflectionProperty::class => new PhpDocAwareReflectionTypeResolver(
                new ReflectionPropertyTypeResolver($reflectionTypeResolver, $typeContextFactory),
                $liipStringTypeResolver,
                $typeContextFactory,
            ),
            \ReflectionFunctionAbstract::class => new PhpDocAwareReflectionTypeResolver(
                new ReflectionReturnTypeResolver($reflectionTypeResolver, $typeContextFactory),
                $liipStringTypeResolver,
                $typeContextFactory,
            ),
            'string' => $liipStringTypeResolver,
        ];

        return TypeResolver::create($resolvers);
    }
}
