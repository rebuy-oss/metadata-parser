<?php

declare(strict_types=1);

namespace Tests\Liip\MetadataParser\TypeParser;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Liip\MetadataParser\Exception\InvalidTypeException;
use Liip\MetadataParser\Metadata\PropertyTypeEnum;
use Liip\MetadataParser\Metadata\PropertyTypeIterable;
use Liip\MetadataParser\TypeParser\PhpTypeParser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Tests\Liip\MetadataParser\ModelParser\Fixtures\ClassWithPhpDocs;
use Tests\Liip\MetadataParser\ModelParser\Fixtures\EnumModel;
use Tests\Liip\MetadataParser\ModelParser\Model\BaseModel;
use Tests\Liip\MetadataParser\ModelParser\Model\ReflectionAbstractModel;
use Tests\Liip\MetadataParser\ModelParser\Model\WithImports;
use Tests\Liip\MetadataParser\RecursionContextTest;

#[Small]
class PhpTypeParserTest extends TestCase
{
    private PhpTypeParser $parser;

    protected function setUp(): void
    {
        $this->parser = new PhpTypeParser();
    }

    public static function providePropertyTypeCases(): iterable
    {
        $reflClass = new \ReflectionClass(ClassWithPhpDocs::class);

        yield [$reflClass->getProperty('mixed'), 'mixed', true];
        yield [$reflClass->getProperty('object'), 'mixed', false];
        yield [$reflClass->getProperty('array'), 'array', false];
        yield [$reflClass->getProperty('string'), 'string'];
        yield [$reflClass->getProperty('boolean'), 'bool'];
        yield [$reflClass->getProperty('integer'), 'int'];
        yield [$reflClass->getProperty('double'), 'float'];
        yield [$reflClass->getProperty('intOrNull'), 'int|null'];
        yield [$reflClass->getProperty('stdClassOrNull'), 'null|stdClass'];
        yield [$reflClass->getProperty('dateTime'), 'DateTime'];
        yield [$reflClass->getProperty('dateTimeImmutable'), 'DateTimeImmutable'];
        yield [$reflClass->getProperty('stringArray'), 'array<int|string, string>'];
        yield [$reflClass->getProperty('stringTripleArray'), 'array<int|string, array<int|string, array<int|string, string>>>'];
        yield [$reflClass->getProperty('stringMap'), 'array<string, string>'];
        yield [$reflClass->getProperty('deepStringMap'), 'array<string, array<string, array<string, string>>>'];
        yield [$reflClass->getProperty('nestedStringArray'), 'array<int|string, array<string, array<int|string, string>>>'];
        yield [$reflClass->getProperty('stdClassArrayOrNull'), 'array<int|string, stdClass>|null'];
        yield [$reflClass->getProperty('stringCollectionOrNull'), 'array<int|string, string>|null'];
        yield [$reflClass->getProperty('stdClassMap'), 'array<string, array<int|string, stdClass>>'];
        yield [$reflClass->getProperty('stringList'), 'list<string>'];
        yield [$reflClass->getProperty('stringMap'), 'array<string, string>'];
        yield [$reflClass->getProperty('intMap'), 'array<string, int>'];
    }

    public static function providePropertyTypeArrayIsCollectionCases(): iterable
    {
        $reflClass = new \ReflectionClass(ClassWithPhpDocs::class);

        yield [$reflClass->getProperty('stringCollection'), 'array<int|string, string>', Collection::class];
        yield [$reflClass->getProperty('stringArrayCollection'), 'array<int|string, string>', ArrayCollection::class];
        yield [$reflClass->getProperty('hashmapCollection'), 'Doctrine\Common\Collections\ArrayCollection<string, int>', ArrayCollection::class];
    }

    #[DataProvider('providePropertyTypeCases')]
    public function testPropertyType(\ReflectionProperty $subject, string $expectedType, ?bool $expectedNullable = null): void
    {
        $type = $this->parser->parseAnnotationType($subject);

        $this->assertSame($expectedType, (string) $type, 'Type should match');
        if (null !== $expectedNullable) {
            $this->assertSame($expectedNullable, $type->isNullable(), 'Nullable flag should match');
        }
    }

    #[DataProvider('providePropertyTypeArrayIsCollectionCases')]
    public function testPropertyTypeArrayIsCollection(\ReflectionProperty $subject, string $expectedType, string $expectedTraversableClass): void
    {
        $type = $this->parser->parseAnnotationType($subject);
        $this->assertInstanceOf(PropertyTypeIterable::class, $type);
        $this->assertTrue($type->isTraversable());
        $this->assertSame($expectedTraversableClass, $type->getTraversableClass());
        $this->assertSame($expectedType, (string) $type, 'Type should match');
    }

    public function testMultiType(): void
    {
        $this->expectException(InvalidTypeException::class);
        $this->expectExceptionMessage('Multiple types are not supported');
        $reflClass = new \ReflectionClass(ClassWithPhpDocs::class);
        $this->parser->parseAnnotationType($reflClass->getProperty('multiType'));
    }

    public function testResourceType(): void
    {
        $this->expectException(InvalidTypeException::class);
        $reflClass = new \ReflectionClass(ClassWithPhpDocs::class);
        $this->parser->parseAnnotationType($reflClass->getProperty('resource'));
    }

    public static function provideNamespaceResolutionCases(): iterable
    {
        $reflClass = new \ReflectionClass(WithImports::class);

        yield [$reflClass->getProperty('sameNamespace'), ReflectionAbstractModel::class];
        yield [$reflClass->getProperty('aliasDifferentNamespace'), RecursionContextTest::class];
        yield [$reflClass->getProperty('aliasSameNamespace'), BaseModel::class];
        yield [$reflClass->getProperty('arrayNested'), 'array<int|string, '.BaseModel::class.'>'];
        yield [$reflClass->getProperty('stringNestedMap'), 'array<string, '.BaseModel::class.'>'];
        yield [$reflClass->getProperty('nestedCollection'), 'array<int|string, '.BaseModel::class.'>'];
    }

    #[DataProvider('provideNamespaceResolutionCases')]
    public function testNamespaceResolution(\ReflectionProperty $subject, string $expectedType): void
    {
        $type = $this->parser->parseAnnotationType($subject);

        $this->assertSame($expectedType, (string) $type, 'Type should match');
    }

    public static function provideReflectionTypeCases(): iterable
    {
        $c = new class {
            private function method1(): string
            {
                return '1';
            }

            private function method2(): ?int
            {
                return 1;
            }

            private function method3(): array
            {
                return [1];
            }
        };
        $reflClass = new \ReflectionClass($c::class);

        yield [
            $reflClass->getMethod('method1')->getReturnType(),
            'string',
        ];

        yield [
            $reflClass->getMethod('method2')->getReturnType(),
            'int|null',
        ];

        yield [
            $reflClass->getMethod('method3')->getReturnType(),
            'list<mixed>',
            false,
        ];
    }

    #[DataProvider('provideReflectionTypeCases')]
    public function testReflectionType(\ReflectionType $reflType, string $expectedType, ?bool $expectedNullable = null): void
    {
        $type = $this->parser->parseReflectionType($reflType);

        $this->assertSame($expectedType, (string) $type, 'Type should match');
        if (null !== $expectedNullable) {
            $this->assertSame($expectedNullable, $type->isNullable(), 'Nullable flag should match');
        }
    }

    public function testEnumReflectionType(): void
    {
        $reflClass = new \ReflectionClass(EnumModel::class);

        $type = $this->parser->parseReflectionType($reflClass->getProperty('direction')->getType());

        $this->assertInstanceOf(PropertyTypeEnum::class, $type);
        $this->assertNull($type->getBackingType());
        $this->assertFalse($type->shouldSerializeAsValue());
        $this->assertNull($type->getSerializationMode());
        $this->assertTrue($type->isNullable());
    }

    public function testBackedEnumReflectionType(): void
    {
        $reflClass = new \ReflectionClass(EnumModel::class);

        $type = $this->parser->parseReflectionType($reflClass->getProperty('suit')->getType());

        $this->assertInstanceOf(PropertyTypeEnum::class, $type);
        $this->assertSame('string', $type->getBackingType());
        $this->assertTrue($type->shouldSerializeAsValue());
        $this->assertNull($type->getSerializationMode());
        $this->assertFalse($type->isNullable());
    }

    public function testEnumAnnotationType(): void
    {
        $reflClass = new \ReflectionClass(ClassWithPhpDocs::class);
        $type = $this->parser->parseAnnotationType($reflClass->getProperty('suitEnum'));

        $this->assertInstanceOf(PropertyTypeEnum::class, $type);
        $this->assertSame('string', $type->getBackingType());
        $this->assertTrue($type->shouldSerializeAsValue());
        $this->assertNull($type->getSerializationMode());
        $this->assertFalse($type->isNullable());
    }

    public function testNullableUnitEnumAnnotationType(): void
    {
        $reflClass = new \ReflectionClass(ClassWithPhpDocs::class);
        $type = $this->parser->parseAnnotationType($reflClass->getProperty('directionEnumOrNull'));

        $this->assertInstanceOf(PropertyTypeEnum::class, $type);
        $this->assertNull($type->getBackingType());
        $this->assertFalse($type->shouldSerializeAsValue());
        $this->assertNull($type->getSerializationMode());
        $this->assertTrue($type->isNullable());
    }
}
