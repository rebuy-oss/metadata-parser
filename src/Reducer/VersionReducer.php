<?php

declare(strict_types=1);

namespace Liip\MetadataParser\Reducer;

/**
 * Select the property based on whether it is included in the specified version.
 */
final readonly class VersionReducer implements PropertyReducerInterface
{
    public function __construct(private string $version)
    {
    }

    public function reduce(string $serializedName, array $properties): array
    {
        $includedProperties = [];
        foreach ($properties as $property) {
            if ($property->getVersionRange()->isIncluded($this->version)) {
                $includedProperties[] = $property;
            }
        }

        return $includedProperties;
    }
}
