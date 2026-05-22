<?php

declare(strict_types=1);

namespace Tests\Liip\MetadataParser\Reducer;

use Liip\MetadataParser\ModelParser\RawMetadata\PropertyVariationMetadata;
use Liip\MetadataParser\Reducer\TakeBestReducer;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

#[Small]
class TakeBestReducerTest extends TestCase
{
    public function testReduce(): void
    {
        $properties = [
            new PropertyVariationMetadata('other', false, true),
            new PropertyVariationMetadata('property', false, true),
        ];

        $reducedProperties = new TakeBestReducer()->reduce('property', $properties);
        $this->assertProperties(['property', 'other'], $reducedProperties);
    }

    public function testReduceWithDifferentNames(): void
    {
        $properties = [
            new PropertyVariationMetadata('other', false, true),
            new PropertyVariationMetadata('other2', false, true),
        ];

        $reducedProperties = new TakeBestReducer()->reduce('property', $properties);
        $this->assertProperties(['other', 'other2'], $reducedProperties);
    }

    /**
     * @param string[]                    $propertyNames
     * @param PropertyVariationMetadata[] $properties
     */
    private function assertProperties(array $propertyNames, iterable $properties): void
    {
        $names = [];
        foreach ($properties as $property) {
            $names[] = $property->getName();
        }

        $this->assertSame($propertyNames, $names, 'Properties should match');
    }
}
