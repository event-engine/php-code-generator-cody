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
use EventEngine\InspectioCody\Board\Exception\CodyQuestion;
use EventEngine\InspectioCody\General\Question;
use EventEngine\InspectioCody\Http\Message\Response;
use EventEngine\InspectioGraphCody;
use OpenCodeModeling\CodeAst\Builder\FileCollection;
use Psr\Http\Message\ResponseInterface;

final class Command extends BaseHook
{
    private string $successDetails;
    private string $apiFilename;

    private MetadataFactory $metadataFactory;

    private Naming $config;
    private EventEngineAst\Command $command;

    public function __construct(Naming $config)
    {
        parent::__construct();
        $this->metadataFactory = new MetadataFactory(new InspectioJson\MetadataFactory());
        $this->config = $config;
        $this->command = new EventEngineAst\Command($this->config);
    }

    public function __invoke(InspectioGraphCody\Node $command, Context $ctx): ResponseInterface
    {
        $fileCollection = FileCollection::emptyList();

        $this->successDetails = "Checklist\n\n";
        $this->apiFilename = $ctx->srcFolder . '/Domain/Api/Command.php';

        $analyzer = new InspectioGraphCody\EventSourcingAnalyzer(
            $command,
            $this->config->config()->getFilterConstName(),
            $this->metadataFactory
        );

        $jsonSchemaFile = null;

        $schemas = $this->generateJsonSchema($analyzer, $ctx);

        if (! empty($schemas)) {
            $key = \key($schemas);
            $jsonSchemaFile = \ltrim(\str_replace($this->config->config()->getBasePath(), '', $schemas[$key]['filename']), '/');
        }

        $this->command->generateApiDescription($analyzer, $fileCollection, $this->apiFilename, $jsonSchemaFile);
        $this->command->generateCommandFile($analyzer, $fileCollection);

        $files = $this->config->config()->getObjectGenerator()->generateFiles($fileCollection, $ctx->printer->codeStyle());
        $this->writeFiles($files);

        $this->successDetails .= "✔️ Command description file {$this->apiFilename} updated\n";

        return Response::fromCody(
            "Wasn't easy, but command {$command->name()} should work now!",
            ['%c' . $this->successDetails, 'color: #73dd8e;font-weight: bold']
        );
    }

    private function generateJsonSchema(InspectioGraphCody\EventSourcingAnalyzer $analyzer, Context $ctx): array
    {
        $schemas = $this->command->generateJsonSchemaFiles($analyzer, $ctx->srcFolder . '/Domain/Api/_schema');

        if (! empty($schemas)) {
            $key = \key($schemas);

            if (\file_exists($schemas[$key]['filename'])) {
                throw CodyQuestion::withQuestionResponse(
                    Response::question(
                        \sprintf('Should I overwrite the file "%s"?', $schemas[$key]['filename']),
                        function (string $answer) use ($schemas) {
                            if (Question::isAnswerYes($answer)) {
                                $this->writeFiles($schemas);
                                $msg = "✔️ Command schema file written\n";
                            } else {
                                $msg = "⬤ Skipped: Command schema file written\n";
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
            $this->successDetails .= "✔️ Command schema file written\n";
        }

        return $schemas;
    }
}
