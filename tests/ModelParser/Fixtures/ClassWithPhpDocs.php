<?php

declare(strict_types=1);

namespace Tests\Liip\MetadataParser\ModelParser\Fixtures;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

class ClassWithPhpDocs
{
    private mixed $mixed;

    /** @var object */
    private $object;

    /** @var array */
    private $array;

    /** @var string */
    private $string;

    /** @var bool */
    private $boolean;

    /** @var int */
    private $integer;

    /** @var float */
    private $double;

    /** @var int|null */
    private $intOrNull;

    /** @var \stdClass|null */
    private $stdClassOrNull;

    /** @var \DateTime */
    private $dateTime;

    /** @var \DateTimeImmutable */
    private $dateTimeImmutable;

    /** @var string[] */
    private $stringArray;

    /** @var string[][][] */
    private $stringTripleArray;

    /** @var array<string, string> */
    private $stringMap;

    /** @var array<string, array<string, array<string, string>>> */
    private $deepStringMap;

    /** @var array<array<string, string[]>> */
    private $nestedStringArray;

    /** @var \stdClass[]|null */
    private $stdClassArrayOrNull;

    /** @var string[]|Collection|null */
    private $stringCollectionOrNull;

    /** @var array<string, \stdClass[]> */
    private $stdClassMap;

    /** @var list<string> */
    private $stringList;

    /** @var array<string, int> */
    private $intMap;

    /** @var string[]|Collection */
    private $stringCollection;

    /** @var string[]|ArrayCollection */
    private $stringArrayCollection;

    /** @var string|int */
    private $multiType;

    /** @var resource */
    private $resource;

    /** @var SuitEnum */
    private $suitEnum;

    /** @var DirectionEnum|null */
    private $directionEnumOrNull;
}
