<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-cody for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-cody/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-cody/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\Cody;

use EventEngine\CodeGenerator\Cody\Metadata\MetadataFactory;
use EventEngine\CodeGenerator\EventEngineAst;
use EventEngine\CodeGenerator\EventEngineAst\Metadata\HasTypeSet;
use EventEngine\CodeGenerator\EventEngineAst\Metadata\InspectioJson;
use EventEngine\InspectioCody\Board\BaseHook;
use EventEngine\InspectioCody\Http\Message\Response;
use EventEngine\InspectioGraph\DocumentType;
use EventEngine\InspectioGraph\VertexType;
use EventEngine\InspectioGraphCody;
use OpenCodeModeling\JsonSchemaToPhp\Type\TypeSet;
use Psr\Http\Message\ResponseInterface;

final class Document extends BaseHook
{
    private string $successDetails;

    private MetadataFactory $metadataFactory;

    private EventEngineAst\Config\Aggregate $config;

    public function __construct(EventEngineAst\Config\Aggregate $config)
    {
        parent::__construct();
        $this->metadataFactory = new MetadataFactory(new InspectioJson\MetadataFactory());
        $this->config = $config;
    }

    public function __invoke(InspectioGraphCody\Node $document, Context $ctx): ResponseInterface
    {
        $this->successDetails = "Checklist\n\n";

        $analyzer = new InspectioGraphCody\EventSourcingAnalyzer($document, $ctx->filterConstName, $this->metadataFactory);

        /** @var DocumentType $documentType */
        foreach ($analyzer->documentMap() as $documentType) {
            $fileCollection = $this->config->getObjectGenerator()->generateImmutableRecord(
                $documentType->name(),
                $this->config->determineValueObjectPath($documentType, $analyzer),
                $this->config->determineValueObjectPath($documentType, $analyzer),
                $this->getMetadata($documentType)
            );
            $files = $this->config->getObjectGenerator()->generateFiles($fileCollection, $ctx->printer->codeStyle());
            $this->writeFiles($files);
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
