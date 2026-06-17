<?php

declare(strict_types=1);

namespace Tests\Liip\MetadataParser\ModelParser\Model;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\ManyToOne;

#[Entity]
class Student
{
    #[Column(type: 'integer', nullable: true)]
    #[Id]
    public int $id;

    #[Column(type: Types::STRING, nullable: true)]
    public $firstName;

    #[Column(type: Types::ASCII_STRING, nullable: true)]
    public ?string $lastName = null;

    #[ManyToMany(targetEntity: Course::class, inversedBy: null)]
    public $electives;

    #[ManyToOne(targetEntity: Course::class, inversedBy: null)]
    public $mainCourse;
}
