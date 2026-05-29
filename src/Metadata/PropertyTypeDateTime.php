<?php

declare(strict_types=1);

namespace Liip\MetadataParser\Metadata;

use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\Type\ObjectType;

/**
 * @extends PropertyTypeClass<ObjectType<\DateTimeInterface>>
 */
final class PropertyTypeDateTime extends PropertyTypeClass
{
    private const array DATE_TIME_TYPES = [
        \DateTime::class,
        \DateTimeInterface::class,
        \DateTimeImmutable::class,
    ];

    /**
     * @param ObjectType<\DateTimeInterface> $type
     */
    public function __construct(
        ObjectType $type,
        bool $nullable,
        private readonly ?DateTimeOptions $dateTimeOptions = null,
    ) {
        if (!self::isTypeDateTime($type->getClassName())) {
            throw new \UnexpectedValueException(\sprintf('Given type "%s" is not date time class or interface', $type->getClassName()));
        }

        parent::__construct($type, $nullable);
    }

    public function isImmutable(): bool
    {
        return \DateTimeImmutable::class === $this->getClassName();
    }

    public function getFormat(): ?string
    {
        return $this->dateTimeOptions?->getFormat();
    }

    public function getZone(): ?string
    {
        return $this->dateTimeOptions?->getZone();
    }

    /**
     * @return list<string>|null
     */
    public function getDeserializeFormats(): ?array
    {
        return $this->dateTimeOptions?->getDeserializeFormats();
    }

    public function merge(PropertyType $other): PropertyType
    {
        $nullable = $this->isNullable() && $other->isNullable();

        if ($other instanceof PropertyTypeUnknown) {
            return new self($this->typeInfo, $nullable, $this->dateTimeOptions);
        }
        if (PropertyTypeClass::class === $other::class && is_a($other->getClassName(), \DateTimeInterface::class, true)) {
            return new self(Type::object($other->getClassName()), $nullable, $this->dateTimeOptions);
        }
        if (!$other instanceof self) {
            throw new \UnexpectedValueException("Can't merge type '{$this}' with '{$other}', they must be the same or unknown");
        }
        if ($this->isImmutable() !== $other->isImmutable()) {
            throw new \UnexpectedValueException("Can't merge type '{$this}' with '{$other}', they must be equal");
        }

        $options = $this->dateTimeOptions ?: $other->dateTimeOptions;

        return new self($this->typeInfo, $nullable, $options);
    }

    /**
     * @phpstan-assert-if-true class-string<\DateTimeInterface> $typeName
     */
    public static function isTypeDateTime(string $typeName): bool
    {
        return \in_array($typeName, self::DATE_TIME_TYPES, true);
    }
}
