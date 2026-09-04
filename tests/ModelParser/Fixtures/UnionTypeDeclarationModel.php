<?php

declare(strict_types=1);

namespace Tests\Liip\MetadataParser\ModelParser\Fixtures;

use Tests\Liip\MetadataParser\ModelParser\Model\Course;
use Tests\Liip\MetadataParser\ModelParser\Model\Nested;

class UnionTypeDeclarationModel
{
    protected Course|Nested $property1;

    public int|string|array|false|null $property2;
}
