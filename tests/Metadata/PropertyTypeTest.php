<?php

declare(strict_types=1);

namespace Tests\Liip\MetadataParser\Metadata;

use Liip\MetadataParser\Metadata\PropertyType;
use Liip\MetadataParser\Metadata\PropertyTypeClass;
use Liip\MetadataParser\Metadata\PropertyTypeDateTime;
use Liip\MetadataParser\Metadata\PropertyTypeEnum;
use Liip\MetadataParser\Metadata\PropertyTypeIterable;
use Liip\MetadataParser\Metadata\PropertyTypePrimitive;
use Liip\MetadataParser\Metadata\PropertyTypeUnknown;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Symfony\Component\TypeInfo\Type;
use Tests\Liip\MetadataParser\ModelParser\Fixtures\SuitEnum;

#[Small]
class PropertyTypeTest extends TestCase
{
    public static function provideMergeCases(): iterable
    {
        yield [
            new PropertyTypeUnknown(true),
            new PropertyTypeUnknown(false),
            'mixed',
            false,
        ];

        yield [
            new PropertyTypePrimitive(Type::builtin('string'), true),
            new PropertyTypeUnknown(false),
            'string',
            false,
        ];

        yield [
            new PropertyTypeDateTime(Type::object(\DateTime::class), true),
            new PropertyTypeUnknown(false),
            'DateTime',
            false,
        ];

        yield [
            new PropertyTypeClass(Type::object(\stdClass::class), true),
            new PropertyTypeUnknown(false),
            'stdClass',
            false,
        ];

        yield [
            new PropertyTypeEnum(Type::enum(SuitEnum::class), true),
            new PropertyTypeUnknown(false),
            SuitEnum::class,
            false,
        ];

        yield [
            self::list('bool', true),
            new PropertyTypeUnknown(false),
            'list<bool>',
            false,
        ];

        yield [
            new PropertyTypePrimitive(Type::builtin('string'), true),
            new PropertyTypePrimitive(Type::builtin('string'), false),
            'string',
            false,
        ];

        yield [
            new PropertyTypeDateTime(Type::object(\DateTime::class), true),
            new PropertyTypeDateTime(Type::object(\DateTime::class), false),
            'DateTime',
            false,
        ];

        yield [
            new PropertyTypeClass(Type::object(\stdClass::class), true),
            new PropertyTypeClass(Type::object(\stdClass::class), false),
            'stdClass',
            false,
        ];

        yield [
            self::list('bool', true),
            self::list('bool', false),
            'list<bool>',
            false,
        ];

        yield [
            self::list('bool', true),
            self::listOfUnknown(false),
            'list<bool>',
            false,
        ];

        yield [
            self::listOfUnknown(false),
            self::list('bool', true),
            'list<bool>',
            false,
        ];
    }

    #[DataProvider('provideMergeCases')]
    public function testMerge(PropertyType $typeA, PropertyType $typeB, string $expectedType, bool $expectedNullable): void
    {
        $result = $typeA->merge($typeB);

        $this->assertSame($expectedType, (string) $result);
        $this->assertSame($expectedNullable, $result->isNullable(), 'Nullable flag should match');
    }

    /**
     * Special case: array to hashmap is allowed. hashmap to array is not.
     */
    public function testUpgradeToHashmap(): void
    {
        $array = self::list('bool', true);
        $hashmap = self::hashmap('bool', true);

        /** @var PropertyTypeIterable $merged */
        $merged = $array->merge($hashmap);
        $this->assertInstanceOf(PropertyTypeIterable::class, $merged);
        $this->assertTrue($merged->isNullable());
        $this->assertTrue($merged->isHashmap());
        /** @var PropertyTypePrimitive $inner */
        $inner = $merged->getSubType();
        $this->assertInstanceOf(PropertyTypePrimitive::class, $inner);
        $this->assertSame('bool', $inner->getTypeName());

        $this->expectException(\UnexpectedValueException::class);
        $hashmap->merge($array);
    }

    public function testMergeInvalidTypes(): void
    {
        $types = $this->getDifferentTypes();

        foreach ($types as $typeA) {
            foreach ($types as $typeB) {
                if ($typeA === $typeB || $typeB instanceof PropertyTypeUnknown) {
                    continue;
                }

                try {
                    $typeA->merge($typeB);
                    $this->fail(\sprintf('Merge of %s into %s should not be possible', (string) $typeB, (string) $typeA));
                } catch (\UnexpectedValueException $e) {
                    $this->assertStringContainsString('merge', $e->getMessage());
                }
            }
        }
    }

    /**
     * @return PropertyType[]
     */
    private function getDifferentTypes(): array
    {
        return [
            new PropertyTypeUnknown(true),
            new PropertyTypePrimitive(Type::builtin('string'), true),
            new PropertyTypePrimitive(Type::builtin('int'), true),
            new PropertyTypeDateTime(Type::object(\DateTime::class), true),
            new PropertyTypeDateTime(Type::object(\DateTimeImmutable::class), true),
            new PropertyTypeEnum(Type::enum(SuitEnum::class), true),
            new PropertyTypeClass(Type::object(\stdClass::class), true),
            self::list('bool', true),
            self::list('int', true),
            self::hashmap('string', true),
        ];
    }

    private static function list(string $primitive, bool $nullable): PropertyTypeIterable
    {
        $sub = new PropertyTypePrimitive(Type::builtin($primitive), false);
        $type = Type::list($sub->getTypeInfo());

        return new PropertyTypeIterable($type, $nullable, $sub);
    }

    private static function hashmap(string $primitive, bool $nullable): PropertyTypeIterable
    {
        $sub = new PropertyTypePrimitive(Type::builtin($primitive), false);
        $type = Type::array($sub->getTypeInfo(), Type::string());

        return new PropertyTypeIterable($type, $nullable, $sub);
    }

    private static function listOfUnknown(bool $nullable): PropertyTypeIterable
    {
        $sub = new PropertyTypeUnknown(false);
        $type = Type::list($sub->getTypeInfo());

        return new PropertyTypeIterable($type, $nullable, $sub);
    }
}
