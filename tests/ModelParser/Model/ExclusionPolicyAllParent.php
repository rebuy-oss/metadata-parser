<?php

declare(strict_types=1);

namespace Tests\Liip\MetadataParser\ModelParser\Model;

use JMS\Serializer\Annotation as JMS;

#[JMS\ExclusionPolicy(policy: 'ALL')]
abstract class ExclusionPolicyAllParent
{
    #[JMS\Expose]
    protected $parentExposed;

    protected $parentHidden;
}
