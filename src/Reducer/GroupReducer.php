<?php

declare(strict_types=1);

namespace Liip\MetadataParser\Reducer;

use Liip\MetadataParser\ModelParser\RawMetadata\PropertyVariationMetadata;

/**
 * Select the property based on whether it is in any of the specified groups.
 */
final readonly class GroupReducer implements PropertyReducerInterface
{
    /**
     * @param string[] $groups
     */
    public function __construct(private array $groups)
    {
    }

    public function reduce(string $serializedName, array $properties): array
    {
        $includedProperties = [];
        foreach ($properties as $property) {
            if ($this->includeProperty($property)) {
                $includedProperties[] = $property;
            }
        }

        return $includedProperties;
    }

    private function includeProperty(PropertyVariationMetadata $property): bool
    {
        return 0 === \count($this->groups) || 0 < \count(array_intersect($property->getGroups(), $this->groups));
    }
}
