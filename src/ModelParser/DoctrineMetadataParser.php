<?php

declare(strict_types=1);

namespace Liip\MetadataParser\ModelParser;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Liip\MetadataParser\Metadata\PropertyTypeClass;
use Liip\MetadataParser\Metadata\PropertyTypeUnknown;
use Liip\MetadataParser\ModelParser\NamingStrategy\PropertyNamingStrategyInterface;
use Liip\MetadataParser\ModelParser\RawMetadata\RawClassMetadata as LiipRawClassMetadata;
use Liip\MetadataParser\TypeParser\JMSTypeParser;

class DoctrineMetadataParser implements ModelParserInterface
{
    /**
     * Map of doctrine 2 field types to JMS\Serializer types, taken from {@link \JMS\Serializer\Metadata\Driver\AbstractDoctrineTypeDriver} (update if needed)
     */
    public const DEFAULT_FIELD_TYPE_MAP = [
        'string' => 'string',
        'ascii_string' => 'string',
        'text' => 'string',
        'blob' => 'string',
        'guid' => 'string',
        'decimal' => 'string',

        'integer' => 'integer',
        'smallint' => 'integer',
        'bigint' => 'integer',

        'datetime' => 'DateTime',
        'datetimetz' => 'DateTime',
        'time' => 'DateTime',
        'date' => 'DateTime',

        'datetime_immutable' => 'DateTimeImmutable',
        'datetimetz_immutable' => 'DateTimeImmutable',
        'time_immutable' => 'DateTimeImmutable',
        'date_immutable' => 'DateTimeImmutable',

        'dateinterval' => 'DateInterval',

        'float' => 'float',

        'boolean' => 'boolean',

        'array' => 'array',
        'json' => 'array',
        'json_array' => 'array',
        'simple_array' => 'array<string>',
    ];

    public function __construct(
        private \Doctrine\Persistence\ManagerRegistry $registry,
        private JMSTypeParser $typeParser = new JMSTypeParser(),
        protected array $fieldMapping = self::DEFAULT_FIELD_TYPE_MAP,
    ) {
    }

    public function withFieldMapping(array $fieldMapping): self
    {
        return new self($this->registry, $this->typeParser, $fieldMapping);
    }

    public function parse(LiipRawClassMetadata $classMetadata, PropertyNamingStrategyInterface $propertyNamingStrategy): void
    {
        $className = $classMetadata->getClassName();
        $doctrineMetadata = $this->tryGetDoctrineClassMetadata($className);
        $reflectionClass = new \ReflectionClass($className);

        if (!$doctrineMetadata) {
            return;
        }

        foreach ($classMetadata->getPropertyCollections() as $propertyCollection) {
            foreach ($propertyCollection->getVariations() as $variation) {
                $propertyType = $variation->getType();
                $propertyName = $variation->getName();

                if (!(
                    $propertyType instanceof PropertyTypeUnknown
                    || ($propertyType instanceof PropertyTypeClass && is_a($propertyType->getClassName(), Collection::class, true))
                )) {
                    continue;
                }

                if ($doctrineMetadata->hasField($propertyName)
                    && ($doctrineFieldType = $doctrineMetadata->getTypeOfField($propertyName))
                    && ($normalized = $this->normalizeFieldType($doctrineFieldType))
                ) {
                    $reflectionProperty = $reflectionClass->getProperty($propertyName);
                    $variation->setType($this->typeParser->parse($normalized, $reflectionProperty));
                } elseif ($doctrineMetadata->hasAssociation($propertyName)) {
                    $otherTypename = $doctrineMetadata->getAssociationTargetClass($propertyName);
                    $otherMetadata = $this->tryGetDoctrineClassMetadata($otherTypename);

                    if (null === $otherMetadata) {
                        continue;
                    }

                    // todo: JMS's DoctrineTypeDriver seems to avoid ODM associations to entity super classes. I'm not too sure whether this affects us too

                    if (!$doctrineMetadata->isSingleValuedAssociation($propertyName)) {
                        $otherTypename = \sprintf('ArrayCollection<%s>', $otherTypename);

                        if ($doctrineMetadata instanceof ClassMetadataInfo) {
                            $associationMapping = $doctrineMetadata->associationMappings[$propertyName];
                            $indexBy = $associationMapping['indexBy'] ?? null;

                            if (null !== $indexBy && $otherMetadata->hasField($associationMapping['indexBy'])) {
                                $typeOfIndexByField = $otherMetadata->getTypeOfField($associationMapping['indexBy']);
                                $otherTypename = \sprintf('ArrayCollection<%s, %s>', $this->normalizeFieldType($typeOfIndexByField), $otherTypename);
                            }
                        }
                    }

                    $reflectionProperty = $reflectionClass->getProperty($propertyName);
                    $variation->setType($this->typeParser->parse($otherTypename, $reflectionProperty));
                }
            }
        }
    }

    protected function tryGetDoctrineClassMetadata(string $className): ?ClassMetadata
    {
        $manager = $this->registry->getManagerForClass($className);

        if (!$manager || $manager->getMetadataFactory()->isTransient($className)) {
            return null;
        }

        return $manager->getClassMetadata($className);
    }

    protected function normalizeFieldType(string $type): ?string
    {
        return $this->fieldMapping[$type] ?? null;
    }
}
