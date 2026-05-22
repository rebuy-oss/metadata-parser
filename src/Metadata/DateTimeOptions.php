<?php

declare(strict_types=1);

namespace Liip\MetadataParser\Metadata;

/**
 * Options as provided in the JMSSerializer DateTime / DateTimeImmutable type attributes.
 */
final class DateTimeOptions implements \JsonSerializable
{
    /**
     * @param list<string>|null $deserializeFormats Use if different formats should be used for parsing dates than for generating dates.
     */
    public function __construct(
        private ?string $format,
        private ?string $zone,
        private ?array $deserializeFormats,
    ) {
    }

    public function getFormat(): ?string
    {
        return $this->format;
    }

    public function getZone(): ?string
    {
        return $this->zone;
    }

    /**
     * @return list<string>|null
     */
    public function getDeserializeFormats(): ?array
    {
        return $this->deserializeFormats;
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return array_filter([
            'format' => $this->format,
            'zone' => $this->zone,
            'deserialize_formats' => $this->deserializeFormats,
        ]);
    }
}
