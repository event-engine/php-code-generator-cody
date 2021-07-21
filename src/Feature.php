<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-cody for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-cody/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-cody/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\Cody;

use EventEngine\CodeGenerator\EventEngineAst\Config\Naming;
use EventEngine\InspectioCody\Board\BaseHook;
use EventEngine\InspectioCody\Http\Message\Response;
use EventEngine\InspectioGraphCody;
use Psr\Http\Message\ResponseInterface;

final class Feature extends BaseHook
{
    /**
     * @var string
     */
    private $successDetails;

    private Naming $config;

    public function __construct(Naming $config)
    {
        parent::__construct();
        $this->config = $config;
    }

    public function __invoke(InspectioGraphCody\Node $feature, Context $ctx): ResponseInterface
    {
        $timeStart = $ctx->microtimeFloat();

        $connection = $ctx->analyzer->analyse($feature);

        $this->successDetails = $ctx->analyzerStats($ctx->microtimeFloat() - $timeStart);

        return Response::fromCody(
            "Wasn't easy, but feature {$feature->name()} was analyzed!",
            ['%c' . $this->successDetails, 'color: #73dd8e;font-weight: bold']
        );
    }
}
