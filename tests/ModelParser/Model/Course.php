<?php

declare(strict_types=1);

namespace Tests\Liip\MetadataParser\ModelParser\Model;

use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\ManyToMany;

#[Entity]
class Course
{
    #[Column(type: 'integer', nullable: true)]
    #[Id]
    public int $id;

    #[Column(type: Types::DATETIME_IMMUTABLE, nullable: false)]
    public \DateTimeInterface $createdAt;
    #[Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    public ?\DateTimeInterface $updatedAt;

    #[Column(type: Types::JSON, nullable: true)]
    public $tags = [];

    #[Column(type: Types::TEXT, nullable: true)]
    public $description;

    #[ManyToMany(targetEntity: Student::class, inversedBy: 'electives')]
    public Collection $students;
}
