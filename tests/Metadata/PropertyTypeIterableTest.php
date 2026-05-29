<?php

declare(strict_types=1);

namespace Tests\Liip\MetadataParser\Metadata;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Liip\MetadataParser\Metadata\PropertyType;
use Liip\MetadataParser\Metadata\PropertyTypeClass;
use Liip\MetadataParser\Metadata\PropertyTypeIterable;
use Liip\MetadataParser\Metadata\PropertyTypePrimitive;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Symfony\Component\TypeInfo\Type;

#[Small]
class PropertyTypeIterableTest extends TestCase
{
    public function testNestedArrayLeaf(): void
    {
        $inner = self::list(new PropertyTypePrimitive(Type::builtin('int'), false));
        $propertyType = self::list($inner);

        $this->assertInstanceOf(PropertyTypeIterable::class, $propertyType->getSubType());
        $this->assertInstanceOf(PropertyTypePrimitive::class, $propertyType->getSubType()->getSubType());
        $this->assertInstanceOf(PropertyTypePrimitive::class, $propertyType->getLeafType());
    }

    public function testDefaultListIsNotCollection(): void
    {
        $subType = new PropertyTypePrimitive(Type::builtin('int'), false);
        $list = self::list($subType);

        $this->assertFalse($list->isTraversable());
    }

    public function testConcreteCollectionClassIsKept(): void
    {
        $subType = new PropertyTypePrimitive(Type::builtin('int'), false);
        $collection = self::list($subType, ArrayCollection::class);

        $this->assertTrue($collection->isTraversable());
        $this->assertSame(ArrayCollection::class, $collection->getTraversableClass());
    }

    public function testMergeListWithClassCollection(): void
    {
        $subType = new PropertyTypePrimitive(Type::builtin('int'), false);
        $list = self::list($subType);

        $this->expectException(\UnexpectedValueException::class);
        $list->merge(new PropertyTypeClass(Type::object(Collection::class), true));
    }

    public function testMergeInterfaceCollectionListWithClassCollection(): void
    {
        $subType = new PropertyTypePrimitive(Type::builtin('int'), false);
        $typeHintedCollection = new PropertyTypeClass(Type::object(Collection::class), true);
        $defaultCollection = self::list($subType, Collection::class);

        $result = $defaultCollection->merge($typeHintedCollection);
        $this->assertInstanceOf(PropertyTypeIterable::class, $result);
        /* @var PropertyTypeIterable $result */
        $this->assertTrue($result->isTraversable());
        $this->assertSame((string) $defaultCollection->getSubType(), (string) $result->getSubType());
        $this->assertSame(Collection::class, $result->getTraversableClass());
    }

    public function testMergeClassInterfaceCollectionWithConcreteCollectionList(): void
    {
        $subType = new PropertyTypePrimitive(Type::builtin('int'), false);
        $typeHintedCollection = new PropertyTypeClass(Type::object(Collection::class), true);
        $explicitCollection = self::list($subType, ArrayCollection::class);

        $result = $typeHintedCollection->merge($explicitCollection);
        $this->assertInstanceOf(PropertyTypeIterable::class, $result);
        /* @var PropertyTypeIterable $result */
        $this->assertTrue($result->isTraversable());
        $this->assertSame((string) $explicitCollection->getSubType(), (string) $result->getSubType());
        $this->assertSame(ArrayCollection::class, $result->getTraversableClass());
    }

    public function testMergeConcreteCollectionListWithClassInterfaceCollection(): void
    {
        $subType = new PropertyTypePrimitive(Type::builtin('int'), false);
        $typeHintedCollection = new PropertyTypeClass(Type::object(Collection::class), true);
        $explicitCollection = self::list($subType, ArrayCollection::class);

        $result = $explicitCollection->merge($typeHintedCollection);
        $this->assertInstanceOf(PropertyTypeIterable::class, $result);
        /* @var PropertyTypeIterable $result */
        $this->assertTrue($result->isTraversable());
        $this->assertSame((string) $explicitCollection->getSubType(), (string) $result->getSubType());
        $this->assertSame(ArrayCollection::class, $result->getTraversableClass());
    }

    public function testMergeConcreteCollectionListWithInterfaceCollectionList(): void
    {
        $subType = new PropertyTypePrimitive(Type::builtin('int'), false);
        $defaultCollection = self::list($subType, Collection::class);
        $explicitCollection = self::list($subType, ArrayCollection::class);

        $result = $explicitCollection->merge($defaultCollection);
        $this->assertInstanceOf(PropertyTypeIterable::class, $result);
        /* @var PropertyTypeIterable $result */
        $this->assertTrue($result->isTraversable());
        $this->assertSame((string) $explicitCollection->getSubType(), (string) $result->getSubType());
        $this->assertSame(ArrayCollection::class, $result->getTraversableClass());
    }

    public function testMergeDefaultCollectionListWithConcreteCollectionList(): void
    {
        $subType = new PropertyTypePrimitive(Type::builtin('int'), false);
        $defaultCollection = self::list($subType, Collection::class);
        $explicitCollection = self::list($subType, ArrayCollection::class);

        $result = $defaultCollection->merge($explicitCollection);
        $this->assertInstanceOf(PropertyTypeIterable::class, $result);
        /* @var PropertyTypeIterable $result */
        $this->assertTrue($result->isTraversable());
        $this->assertSame((string) $explicitCollection->getSubType(), (string) $result->getSubType());
        $this->assertSame(ArrayCollection::class, $result->getTraversableClass());
    }

    public function testMergeListAndInterfaceCollectionList(): void
    {
        $subType = new PropertyTypePrimitive(Type::builtin('int'), false);
        $list = self::list($subType);
        $collection = self::list($subType, Collection::class);

        foreach ([$list->merge($collection), $collection->merge($list)] as $result) {
            $this->assertInstanceOf(PropertyTypeIterable::class, $result);
            /* @var PropertyTypeIterable $result */
            $this->assertTrue($result->isTraversable());
            $this->assertSame(Collection::class, $result->getTraversableClass());
            $this->assertSame((string) $list->getSubType(), (string) $result->getSubType());
        }
    }

    public function testMergeListWithConcreteCollectionList(): void
    {
        $subType = new PropertyTypePrimitive(Type::builtin('int'), false);
        $list = self::list($subType);
        $collection = self::list($subType, ArrayCollection::class);

        foreach ([$list->merge($collection), $collection->merge($list)] as $result) {
            $this->assertInstanceOf(PropertyTypeIterable::class, $result);
            /* @var PropertyTypeIterable $result */
            $this->assertTrue($result->isTraversable());
            $this->assertSame(ArrayCollection::class, $result->getTraversableClass());
            $this->assertSame((string) $list->getSubType(), (string) $result->getSubType());
        }
    }

    private static function list(PropertyType $subType, ?string $traversableClass = null): PropertyTypeIterable
    {
        return new PropertyTypeIterable(
            Type::list($subType->getTypeInfo()),
            false,
            $subType,
            $traversableClass,
        );
    }
}
