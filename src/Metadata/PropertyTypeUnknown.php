<?php

declare(strict_types=1);

namespace Liip\MetadataParser\Metadata;

use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\Type\BuiltinType;
use Symfony\Component\TypeInfo\TypeIdentifier;

/**
 * @extends AbstractPropertyType<BuiltinType<TypeIdentifier::MIXED>, bool>
 */
final class PropertyTypeUnknown extends AbstractPropertyType
{
    public function __construct(bool $nullable)
    {
        parent::__construct(Type::mixed(), $nullable);
    }

    public function merge(PropertyType $other): PropertyType
    {
        if (!$other instanceof self) {
            throw new \UnexpectedValueException(\sprintf('Can\'t merge type %s with %s, they must be the same', self::class, $other::class));
        }

        return new self($this->nullable && $other->isNullable());
    }
}
