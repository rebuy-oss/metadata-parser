<?php

declare(strict_types=1);

namespace Tests\Liip\MetadataParser\ModelParser\Model;

use JMS\Serializer\Annotation as JMS;

abstract class SerializationEventsParent
{
    #[JMS\PostDeserialize]
    public function parentPostDeserialize(): void
    {
    }

    #[JMS\PreSerialize]
    public function parentPreSerialize(): void
    {
    }

    #[JMS\PostSerialize]
    public function parentPostSerialize(): void
    {
    }
}
