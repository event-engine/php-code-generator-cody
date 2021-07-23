<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-cody for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-cody/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-cody/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\Cody;

use EventEngine\CodeGenerator\EventEngineAst;
use EventEngine\CodeGenerator\EventEngineAst\Config\Naming;
use EventEngine\InspectioCody\Board\BaseHook;
use EventEngine\InspectioCody\Http\Message\Response;
use EventEngine\InspectioGraphCody;
use OpenCodeModeling\CodeAst\Builder\FileCollection;
use Psr\Http\Message\ResponseInterface;

final class Aggregate extends BaseHook
{
    /**
     * @var string
     */
    private $successDetails;

    private Naming $config;
    private EventEngineAst\Aggregate $aggregate;
    private EventEngineAst\AggregateStateImmutableRecordOverride $immutableRecordOverride;

    public function __construct(Naming $config)
    {
        parent::__construct();
        $this->config = $config;
        $this->aggregate = new EventEngineAst\Aggregate($this->config);
        $this->immutableRecordOverride = new EventEngineAst\AggregateStateImmutableRecordOverride($this->config);
    }

    public function __invoke(InspectioGraphCody\Node $aggregate, Context $ctx): ResponseInterface
    {
        $timeStart = $ctx->microtimeFloat();

        $fileCollection = FileCollection::emptyList();
        $this->successDetails = "Checklist\n\n";

        $connection = $ctx->analyzer->analyse($aggregate);

        // description code generation
        $this->aggregate->generateApiDescription($connection, $ctx->analyzer, $fileCollection);
        $this->aggregate->generateApiDescriptionClassMap($connection, $ctx->analyzer, $fileCollection);

        // aggregate state code generation
        $this->aggregate->generateAggregateStateFile($connection, $ctx->analyzer, $fileCollection);

        // pass through arbitrary data, needed for testing if you don't use metadata
        // $this->immutableRecordOverride->generateImmutableRecordOverride($fileCollection);

        // aggregate behaviour code generation
        $this->aggregate->generateAggregateFile($connection, $ctx->analyzer, $fileCollection);

        $files = $this->config->config()->getObjectGenerator()->generateFiles($fileCollection, $ctx->printer->codeStyle());

        foreach ($files as $file) {
            $this->successDetails .= "✔️ File {$file['filename']} updated\n";
            $this->writeFile($file['code'], $file['filename']);
        }

        $this->successDetails .= $ctx->analyzerStats($ctx->microtimeFloat() - $timeStart);

        return Response::fromCody(
            "Wasn't easy, but aggregate {$aggregate->name()} should work now!",
            ['%c' . $this->successDetails, 'color: #73dd8e;font-weight: bold']
        );
    }
}
