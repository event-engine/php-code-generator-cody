<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-cody for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-cody/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-cody/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\Cody;

use EventEngine\InspectioCody\Board\BaseHook;
use EventEngine\InspectioCody\Http\Message\Response;
use EventEngine\InspectioGraphCody;
use Psr\Http\Message\ResponseInterface;

final class Sync extends BaseHook
{
    public function __invoke(InspectioGraphCody\Node $node, Context $ctx): ResponseInterface
    {
        $ctx->analyzer->analyse($node);

        return Response::empty();
    }
}
