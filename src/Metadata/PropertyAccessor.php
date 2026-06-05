<?php

declare(strict_types=1);

namespace Liip\MetadataParser\Metadata;

final readonly class PropertyAccessor implements \JsonSerializable
{
    public function __construct(
        private ?string $getterMethod,
        private ?string $setterMethod,
    ) {
    }

    public static function none(): self
    {
        return new self(null, null);
    }

    public function isDefined(): bool
    {
        return $this->hasGetterMethod() || $this->hasSetterMethod();
    }

    public function hasGetterMethod(): bool
    {
        return null !== $this->getterMethod;
    }

    public function getGetterMethod(): ?string
    {
        return $this->getterMethod;
    }

    public function hasSetterMethod(): bool
    {
        return null !== $this->setterMethod;
    }

    public function getSetterMethod(): ?string
    {
        return $this->setterMethod;
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return array_filter([
            'getter_method' => $this->getterMethod,
            'setter_method' => $this->setterMethod,
        ]);
    }
}
