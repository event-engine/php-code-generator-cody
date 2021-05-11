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
use EventEngine\CodeGenerator\EventEngineAst\Metadata\InspectioJson;
use EventEngine\CodeGenerator\EventEngineAst\Metadata\MetadataFactory;
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

    /**
     * @var string
     */
    private $apiAggregateFilename;

    /**
     * @var string
     */
    private $apiEventFilename;

    /**
     * @var string
     */
    private $aggregatePath;

    /**
     * @var MetadataFactory
     */
    private $metadataFactory;

    private Naming $config;
    private EventEngineAst\Aggregate $aggregate;
    private EventEngineAst\AggregateStateImmutableRecordOverride $immutableRecordOverride;

    public function __construct(Naming $config)
    {
        parent::__construct();
        $this->metadataFactory = new MetadataFactory(new InspectioJson\MetadataFactory());
        $this->config = $config;
        $this->aggregate = new EventEngineAst\Aggregate($this->config);
        $this->immutableRecordOverride = new EventEngineAst\AggregateStateImmutableRecordOverride($this->config);
    }

    public function __invoke(InspectioGraphCody\Node $aggregate, Context $ctx): ResponseInterface
    {
        $fileCollection = FileCollection::emptyList();
        $this->successDetails = "Checklist\n\n";
        $this->apiAggregateFilename = $ctx->srcFolder . '/Domain/Api/Aggregate.php';
        $this->apiEventFilename = $ctx->srcFolder . '/Domain/Api/Event.php';
        $this->aggregatePath = $ctx->srcFolder . '/Domain/Model';

        $analyzer = new InspectioGraphCody\EventSourcingAnalyzer(
            $aggregate,
            $this->config->config()->getFilterConstName(),
            $this->metadataFactory
        );

        // description code generation
        $this->aggregate->generateApiDescription($analyzer, $fileCollection, $this->apiAggregateFilename);
        $files = $this->config->config()->getObjectGenerator()->generateFiles($fileCollection, $ctx->printer->codeStyle());
        $this->writeFiles($files);

        $this->successDetails .= "✔️ Aggregate description file {$this->apiAggregateFilename} updated\n";

        // aggregate state code generation
        $fileCollection = FileCollection::emptyList();
        $this->aggregate->generateAggregateStateFile($analyzer, $fileCollection);

        // pass through arbitrary data, needed for testing if you don't use metadata
        // $this->immutableRecordOverride->generateImmutableRecordOverride($fileCollection);

        $files = $this->config->config()->getObjectGenerator()->generateFiles($fileCollection, $ctx->printer->codeStyle());
        $this->writeFiles($files);

        $this->successDetails .= "✔️ Aggregate state file updated\n";

        // aggregate behaviour code generation
        $fileCollection = FileCollection::emptyList();
        $this->aggregate->generateAggregateFile($analyzer, $fileCollection, $this->apiEventFilename);

        $files = $this->config->config()->getObjectGenerator()->generateFiles($fileCollection, $ctx->printer->codeStyle());
        $this->writeFiles($files);

        $this->successDetails .= "✔️ Aggregate behaviour file updated\n";

        return Response::fromCody(
            "Wasn't easy, but aggregate {$aggregate->name()} should work now!",
            ['%c' . $this->successDetails, 'color: #73dd8e;font-weight: bold']
        );
    }
}
