<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-cody for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-cody/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-cody/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\Cody;

use EventEngine\CodeGenerator\EventEngineAst\DescriptionFileMethodFactory;
use EventEngine\CodeGenerator\EventEngineAst\EmptyClassFactory;
use EventEngine\CodeGenerator\EventEngineAst\EventDescriptionFactory;
use EventEngine\InspectioCody\Board\BaseHook;
use EventEngine\InspectioCody\Board\Exception\CodyQuestion;
use EventEngine\InspectioCody\General\Question;
use EventEngine\InspectioCody\Http\Message\Response;
use EventEngine\InspectioGraphCody;
use OpenCodeModeling\CodeAst\Package\Psr4Info;
use Psr\Http\Message\ResponseInterface;

final class Event extends BaseHook
{
    /**
     * @var string
     */
    private $successDetails;

    /**
     * @var string
     */
    private $apiFilename;

    /**
     * @var InspectioGraphCody\Metadata\NodeJsonMetadataFactory
     */
    private $metadataFactory;

    public function __construct()
    {
        parent::__construct();
        $this->metadataFactory = new InspectioGraphCody\Metadata\NodeJsonMetadataFactory();
    }

    public function __invoke(InspectioGraphCody\Node $event, Context $ctx): ResponseInterface
    {
        $this->successDetails = "Checklist\n\n";
        $this->apiFilename = $ctx->srcFolder . '/Domain/Api/Event.php';

        $analyzer = new InspectioGraphCody\EventSourcingAnalyzer($event, $ctx->filterConstName, $this->metadataFactory);

        // event description code generation
        $code = $this->generateApiDescriptionClass($ctx);
        $code = $this->generateApiDescriptionFileMethod($code);
        $schemas = $this->generateJsonSchema($ctx, $analyzer);
        $this->generateApiDescription($event, $ctx, $analyzer, $code, $schemas);

        return Response::fromCody(
            "Wasn't easy, but event {$event->name()} should work now!",
            ['%c' . $this->successDetails, 'color: #73dd8e;font-weight: bold']
        );
    }

    private function generateApiDescriptionClass($ctx): string
    {
        $factory = EmptyClassFactory::withDefaultConfig($ctx->filterDirectoryToNamespace);
        $factory->config()->getClassInfoList()->addClassInfo(
            new Psr4Info(
                $ctx->srcFolder,
                $ctx->appNamespace,
                $ctx->filterDirectoryToNamespace,
                $ctx->filterNamespaceToDirectory
            )
        );

        $code = $factory->component()($this->apiFilename);

        $this->successDetails .= "✔️ Event description file {$this->apiFilename} updated\n";

        return $code;
    }

    private function generateApiDescriptionFileMethod(string $code): string
    {
        $factory = DescriptionFileMethodFactory::withDefaultConfig();

        return $factory->component()($code);
    }

    private function generateJsonSchema($ctx, InspectioGraphCody\EventSourcingAnalyzer $analyzer): array
    {
        $factory = EventDescriptionFactory::withDefaultConfig(
            $ctx->filterConstName,
            $ctx->filterConstValue
        );

        $schemas = $factory->componentMetadataSchema()(
            $analyzer,
            $ctx->srcFolder . '/Domain/Api/_schema'
        );

        if (! empty($schemas)) {
            $key = \key($schemas);

            if (\file_exists($schemas[$key]['filename'])) {
                throw CodyQuestion::withQuestionResponse(
                    Response::question(
                        \sprintf('Should I overwrite the file "%s"?', $schemas[$key]['filename']),
                        function (string $answer) use ($schemas) {
                            if (Question::isAnswerYes($answer)) {
                                $this->writeFiles($schemas);
                                $msg = "✔️ Event schema file written\n";
                            } else {
                                $msg = "⬤ Skipped: Event schema file written\n";
                            }

                            return Response::fromCody(
                                "You're the boss",
                                ['%c' . $msg, 'color: #73dd8e;font-weight: bold']
                            );
                        }
                    )
                );
            }
            $this->writeFiles($schemas);
            $this->successDetails .= "✔️ Event schema file written\n";
        }

        return $schemas;
    }

    private function generateApiDescription(
        InspectioGraphCody\Node $event,
        $ctx,
        InspectioGraphCody\EventSourcingAnalyzer $analyzer,
        string $code,
        array $schemas
    ): string {
        $factory = EventDescriptionFactory::withDefaultConfig(
            $ctx->filterConstName,
            $ctx->filterConstValue
        );

        $updatedCode = $factory->component()($analyzer, $code, $schemas);

        if ($updatedCode !== $code) {
            $this->writeFile($updatedCode, $this->apiFilename);
            $this->successDetails .= "✔️ Event description for '{$event->name()}' added\n";
        } else {
            $this->successDetails .= "⬤ Skipped:️ Event description for '{$event->name()}' added\n";
        }

        return $updatedCode;
    }
}

return new Event();
