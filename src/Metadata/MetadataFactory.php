<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-cody for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-cody/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-cody/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\Cody\Metadata;

use EventEngine\CodeGenerator\EventEngineAst\Metadata\InspectioJson;
use EventEngine\InspectioGraph\Metadata\Metadata;
use EventEngine\InspectioGraphCody\Node;

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
