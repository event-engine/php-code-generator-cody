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

final class Document extends BaseHook
{
    private string $successDetails;

    private Naming $config;
    private EventEngineAst\ValueObject $valueObject;

    public function __construct(Naming $config)
    {
        parent::__construct();
        $this->config = $config;
        $this->valueObject = new EventEngineAst\ValueObject($this->config);
    }

    public function __invoke(InspectioGraphCody\Node $document, Context $ctx): ResponseInterface
    {
        $timeStart = $ctx->microtimeFloat();

        $fileCollection = FileCollection::emptyList();
        $this->successDetails = "Checklist\n\n";

        $connection = $ctx->analyzer->analyse($document);

        $this->valueObject->generate($connection, $ctx->analyzer, $fileCollection);

        $files = $this->config->config()->getObjectGenerator()->generateFiles($fileCollection, $ctx->printer->codeStyle());

        foreach ($files as $file) {
            $this->successDetails .= "✔️ File {$file['filename']} updated\n";
            $this->writeFile($file['code'], $file['filename']);
        }

        $this->successDetails .= $ctx->analyzerStats($ctx->microtimeFloat() - $timeStart);

        return Response::fromCody(
            "Wasn't easy, but document {$document->name()} should work now!",
            ['%c' . $this->successDetails, 'color: #73dd8e;font-weight: bold']
        );
    }
}
