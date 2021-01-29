<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-cody for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-cody/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-cody/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\Cody;

use EventEngine\CodeGenerator\Cody\Metadata\MetadataFactory;
use EventEngine\CodeGenerator\EventEngineAst\CommandDescriptionFactory;
use EventEngine\CodeGenerator\EventEngineAst\DescriptionFileMethodFactory;
use EventEngine\CodeGenerator\EventEngineAst\EmptyClassFactory;
use EventEngine\CodeGenerator\EventEngineAst\Metadata\InspectioJson;
use EventEngine\CodeGenerator\EventEngineAst\ValueObjectFactory;
use EventEngine\InspectioCody\Board\BaseHook;
use EventEngine\InspectioCody\Board\Exception\CodyQuestion;
use EventEngine\InspectioCody\General\Question;
use EventEngine\InspectioCody\Http\Message\Response;
use EventEngine\InspectioGraphCody;
use Psr\Http\Message\ResponseInterface;

final class Command extends BaseHook
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
     * @var MetadataFactory
     */
    private $metadataFactory;

    public function __construct()
    {
        parent::__construct();
        $this->metadataFactory = new MetadataFactory(new InspectioJson\MetadataFactory());
    }

    public function __invoke(InspectioGraphCody\Node $command, Context $ctx): ResponseInterface
    {
        $this->successDetails = "Checklist\n\n";
        $this->apiFilename = $ctx->srcFolder . '/Domain/Api/Command.php';

        $analyzer = new InspectioGraphCody\EventSourcingAnalyzer($command, $ctx->filterConstName, $this->metadataFactory);

        // command description code generation
        $code = $this->generateApiDescriptionClass($ctx);
        $code = $this->generateApiDescriptionFileMethod($code);
        $schemas = $this->generateJsonSchema($ctx, $analyzer);
        $this->generateApiDescription($command, $ctx, $analyzer, $code, $schemas);

        return Response::fromCody(
            "Wasn't easy, but command {$command->name()} should work now!",
            ['%c' . $this->successDetails, 'color: #73dd8e;font-weight: bold']
        );
    }

    private function generateApiDescriptionClass(Context $ctx): string
    {
        $factory = EmptyClassFactory::withDefaultConfig();
        $factory->config()->setClassInfoList($ctx->classInfoList);

        $code = $factory->component()($this->apiFilename);

        $this->successDetails .= "✔️ Command description file {$this->apiFilename} updated\n";

        return $code;
    }

    private function generateApiDescriptionFileMethod(string $code): string
    {
        $factory = DescriptionFileMethodFactory::withDefaultConfig();

        return $factory->component()($code);
    }

    private function generateJsonSchema(Context $ctx, InspectioGraphCody\EventSourcingAnalyzer $analyzer): array
    {
        $factory = CommandDescriptionFactory::withDefaultConfig();

        $schemas = $factory->componentMetadataSchema()(
            $analyzer,
            $ctx->srcFolder . '/Domain/Api/_schema'
        );

        if (! empty($schemas)) {
            $key = \key($schemas);

            $voFactory = ValueObjectFactory::withDefaultConfig();
            $voFactory->config()->setClassInfoList($ctx->classInfoList);

            foreach ($analyzer->commandMap() as $command) {
                $files = $voFactory->componentFile()->generateValueObjectsFromMetadata(
                    $command,
                    $ctx->sharedValueObjectFolder
                );
                $this->writeFiles($voFactory->objectGenerator(true)->generateFiles($files));
            }

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

    private function generateApiDescription(
        InspectioGraphCody\Node $command,
        $ctx,
        InspectioGraphCody\EventSourcingAnalyzer $analyzer,
        string $code,
        array $schemas
    ): string {
        $factory = CommandDescriptionFactory::withDefaultConfig();

        $updatedCode = $factory->component()($analyzer, $code, $schemas);

        if ($updatedCode !== $code) {
            $this->writeFile($updatedCode, $this->apiFilename);
            $this->successDetails .= "✔️ Command description for '{$command->name()}' added\n";
        } else {
            $this->successDetails .= "⬤ Skipped: Command description for '{$command->name()}' added\n";
        }

        return $updatedCode;
    }
}

return new Command();
