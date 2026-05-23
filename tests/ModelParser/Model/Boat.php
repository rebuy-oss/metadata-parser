<?php

declare(strict_types=1);

namespace Tests\Liip\MetadataParser\ModelParser\Model;

use JMS\Serializer\Annotation as JMS;

#[JMS\Discriminator(field: 'type', map: ['ferry' => Ferry::class, 'cabinCruiser' => CabinCruiser::class])]
abstract class Boat extends Vehicle
{
}
