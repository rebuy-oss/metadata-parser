<?php

declare(strict_types=1);

namespace Liip\MetadataParser\Metadata;

use Doctrine\Common\Collections\Collection;
use Liip\MetadataParser\Exception\InvalidTypeException;
use Symfony\Component\TypeInfo\Type\ObjectType;

/**
 * @template T of ObjectType
 *
 * @extends AbstractPropertyType<T, bool>
 */
class PropertyTypeClass extends AbstractPropertyType
{
    private ?ClassMetadata $classMetadata = null;

    /**
     * @param T $type
     */
    public function __construct(ObjectType $type, bool $nullable)
    {
        $className = $type->getClassName();
        if (!class_exists($className) && !interface_exists($className)) {
            throw InvalidTypeException::classNotFound($className);
        }

        parent::__construct($type, $nullable);
    }

    public function getClassName(): string
    {
        return $this->typeInfo->getClassName();
    }

    public function getClassMetadata(): ClassMetadata
    {
        if (null === $this->classMetadata) {
            throw new \BadMethodCallException('Internal error, custom class property type is missing the metadata. Looks like the schema builder didn\'t set it, which is a bug');
        }

        return $this->classMetadata;
    }

    /**
     * This method is only to be used by the parsing process and is required to avoid a chicken-and-egg problem.
     *
     * @internal
     */
    public function setClassMetadata(ClassMetadata $classMetadata): void
    {
        $this->classMetadata = $classMetadata;
    }

    public function merge(PropertyType $other): PropertyType
    {
        $nullable = $this->isNullable() && $other->isNullable();

        if ($other instanceof PropertyTypeUnknown) {
            return new self($this->typeInfo, $nullable);
        }

        if (is_a($this->getClassName(), Collection::class, true) && (($other instanceof PropertyTypeIterable) && $other->isTraversable())) {
            return $other->merge($this);
        }
        if (is_a($this->getClassName(), \DateTimeInterface::class, true) && ($other instanceof PropertyTypeDateTime)) {
            return $other->merge($this);
        }
        if (!$other instanceof self) {
            throw new \UnexpectedValueException(\sprintf('Can\'t merge type %s with %s, they must be the same or unknown', self::class, $other::class));
        }
        if ($this->getClassName() !== $other->getClassName()) {
            throw new \UnexpectedValueException(\sprintf('Can\'t merge type %s with %s, they must be equal', self::class, $other::class));
        }

        return new self($this->typeInfo, $nullable);
    }
}
