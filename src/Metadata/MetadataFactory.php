<?php

declare(strict_types=1);

namespace EventEngine\CodeGenerator\Cody\Metadata;

use EventEngine\InspectioGraph\Metadata\Metadata;
use EventEngine\InspectioGraphCody\Node;
use EventEngine\CodeGenerator\EventEngineAst\Metadata\InspectioJson;

final class MetadataFactory
{
    private InspectioJson\MetadataFactory $jsonFactory;

    public function __construct(InspectioJson\MetadataFactory $jsonFactory)
    {
        $this->jsonFactory = $jsonFactory;
    }

    public function __invoke(Node $node): Metadata
    {
        return ($this->jsonFactory)($node->metadata() ?? '{}', $node->type());
    }
}
