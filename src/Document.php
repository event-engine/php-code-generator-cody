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
use EventEngine\CodeGenerator\EventEngineAst\Metadata\HasTypeSet;
use EventEngine\CodeGenerator\EventEngineAst\Metadata\InspectioJson;
use EventEngine\CodeGenerator\EventEngineAst\Metadata\MetadataFactory;
use EventEngine\InspectioCody\Board\BaseHook;
use EventEngine\InspectioCody\Http\Message\Response;
use EventEngine\InspectioGraph\VertexType;
use EventEngine\InspectioGraphCody;
use OpenCodeModeling\CodeAst\Builder\FileCollection;
use OpenCodeModeling\JsonSchemaToPhp\Type\TypeSet;
use Psr\Http\Message\ResponseInterface;

final class Document extends BaseHook
{
    private string $successDetails;

    private MetadataFactory $metadataFactory;

    private Naming $config;
    private EventEngineAst\ValueObject $valueObject;

    public function __construct(Naming $config)
    {
        parent::__construct();
        $this->metadataFactory = new MetadataFactory(new InspectioJson\MetadataFactory());
        $this->config = $config;
        $this->valueObject = new EventEngineAst\ValueObject($this->config);
    }

    public function __invoke(InspectioGraphCody\Node $document, Context $ctx): ResponseInterface
    {
        $this->successDetails = "Checklist\n\n";

        $fileCollection = FileCollection::emptyList();

        $this->successDetails = "Checklist\n\n";

        $analyzer = new InspectioGraphCody\EventSourcingAnalyzer(
            $document,
            $this->config->config()->getFilterConstName(),
            $this->metadataFactory
        );

        $this->valueObject->generate($analyzer, $fileCollection);

        $files = $this->config->config()->getObjectGenerator()->generateFiles($fileCollection, $ctx->printer->codeStyle());

        foreach ($files as $file) {
            $this->successDetails .= "✔️ File {$file['filename']} updated\n";
            $this->writeFile($file['code'], $file['filename']);
        }

        return Response::fromCody(
            "Wasn't easy, but document {$document->name()} should work now!",
            ['%c' . $this->successDetails, 'color: #73dd8e;font-weight: bold']
        );
    }

    private function getMetadata(VertexType $vertexType): ?TypeSet
    {
        $metadataInstance = $vertexType->metadataInstance();

        return $metadataInstance instanceof HasTypeSet ? $metadataInstance->typeSet() : null;
    }
}
