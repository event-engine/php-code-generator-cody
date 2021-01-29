<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-cody for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-cody/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-cody/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\Cody;

use EventEngine\CodeGenerator\Cody\Metadata\MetadataFactory;
use EventEngine\CodeGenerator\EventEngineAst\AggregateBehaviourFactory;
use EventEngine\CodeGenerator\EventEngineAst\AggregateDescriptionFactory;
use EventEngine\CodeGenerator\EventEngineAst\AggregateStateFactory;
use EventEngine\CodeGenerator\EventEngineAst\DescriptionFileMethodFactory;
use EventEngine\CodeGenerator\EventEngineAst\EmptyClassFactory;
use EventEngine\CodeGenerator\EventEngineAst\Metadata\InspectioJson;
use EventEngine\InspectioCody\Board\BaseHook;
use EventEngine\InspectioCody\Http\Message\Response;
use EventEngine\InspectioGraphCody;
use OpenCodeModeling\CodeAst\Package\Psr4Info;
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
    private $apiFilename;

    /**
     * @var string
     */
    private $aggregatePath;

    /**
     * @var MetadataFactory
     */
    private $metadataFactory;

    /**
     * @var AggregateStateFactory
     */
    private $aggregateStateFactory;

    /**
     * @var AggregateBehaviourFactory
     */
    private $aggregateBehaviourFactory;

    public function __construct()
    {
        parent::__construct();
        $this->metadataFactory = new MetadataFactory(new InspectioJson\MetadataFactory());
    }

    public function __invoke(InspectioGraphCody\Node $aggregate, Context $ctx): ResponseInterface
    {
        $this->successDetails = "Checklist\n\n";
        $this->apiFilename = $ctx->srcFolder . '/Domain/Api/Aggregate.php';
        $this->aggregatePath = $ctx->srcFolder . '/Domain/Model';

        $this->initFactories($ctx);

        $analyzer = new InspectioGraphCody\EventSourcingAnalyzer($aggregate, $ctx->filterConstName, $this->metadataFactory);

        // description code generation
        $code = $this->generateApiDescriptionClass($ctx);
        $code = $this->generateApiDescriptionFileMethod($code);
        $this->generateApiDescription($aggregate, $ctx, $analyzer, $code);

        // aggregate state code generation
        $codeList = $this->generateAggregateStateClass($analyzer);
        $codeList = $this->generateAggregateStateModifyMethod($analyzer, $codeList);
        $this->generateAggregateStateImmutableRecordOverride($analyzer, $codeList);

        // aggregate behaviour code generation
        $codeList = $this->generateAggregateBehaviourClass($analyzer, $ctx);
        $codeList = $this->generateAggregateBehaviourCommandMethod($analyzer, $codeList);
        $this->generateAggregateBehaviourEventMethod($analyzer, $codeList);

        return Response::fromCody(
            "Wasn't easy, but aggregate {$aggregate->name()} should work now!",
            ['%c' . $this->successDetails, 'color: #73dd8e;font-weight: bold']
        );
    }

    private function generateApiDescriptionClass($ctx): string
    {
        $factory = EmptyClassFactory::withDefaultConfig();
        $factory->config()->getClassInfoList()->addClassInfo(
            new Psr4Info(
                $ctx->srcFolder,
                $ctx->appNamespace,
                $ctx->filterDirectoryToNamespace,
                $ctx->filterNamespaceToDirectory
            )
        );

        $code = $factory->component()($this->apiFilename);

        $this->successDetails .= "✔️ Aggregate description file {$this->apiFilename} updated\n";

        return $code;
    }

    private function generateApiDescriptionFileMethod(string $code): string
    {
        $factory = DescriptionFileMethodFactory::withDefaultConfig();

        return $factory->component()($code);
    }

    private function generateApiDescription(
        InspectioGraphCody\Node $aggregate,
        $ctx,
        InspectioGraphCody\EventSourcingAnalyzer $analyzer,
        string $code
    ): string {
        $factory = AggregateDescriptionFactory::withDefaultConfig();
        $factory->config()->getClassInfoList()->addClassInfo(
            new Psr4Info(
                $ctx->srcFolder,
                $ctx->appNamespace,
                $ctx->filterDirectoryToNamespace,
                $ctx->filterNamespaceToDirectory
            )
        );

        $updatedCode = $factory->component()($analyzer, $code, $this->aggregatePath);

        if ($updatedCode !== $code) {
            $this->writeFile($updatedCode, $this->apiFilename);
            $this->successDetails .= "✔️ Aggregate description for '{$aggregate->name()}' added\n";
        } else {
            $this->successDetails .= "⬤️ Skipped: Aggregate description for '{$aggregate->name()}' added\n";
        }

        return $updatedCode;
    }

    private function generateAggregateStateClass(InspectioGraphCody\EventSourcingAnalyzer $analyzer): array
    {
        $updateCodeList = $this->aggregateStateFactory->componentFile()($analyzer, $this->aggregatePath);

        if (\count($updateCodeList) > 0) {
            $key = \key($updateCodeList);
        } else {
            return [];
        }

        $this->successDetails .= "✔️ Aggregate state file {$updateCodeList[$key]['filename']} updated\n";

        return $updateCodeList;
    }

    private function generateAggregateStateModifyMethod(InspectioGraphCody\EventSourcingAnalyzer $analyzer, array $codeList): array
    {
        $updateCodeList = $this->aggregateStateFactory->componentModifyMethod()($analyzer, $codeList);

        if (\count($updateCodeList) > 0) {
            $key = \key($updateCodeList);
        } else {
            return [];
        }

        if ($updateCodeList[$key]['code'] !== $codeList[$key]['code']) {
            $this->writeFiles($updateCodeList);
            $this->successDetails .= "✔️ Aggregate state modify method to {$updateCodeList[$key]['filename']} added\n";
        } else {
            $this->successDetails .= "⬤ Skipped: Aggregate state modify method to {$updateCodeList[$key]['filename']} added\n";
        }

        return $updateCodeList;
    }

    private function generateAggregateStateImmutableRecordOverride(InspectioGraphCody\EventSourcingAnalyzer $analyzer, array $codeList): array
    {
        $updateCodeList = $this->aggregateStateFactory->componentDescriptionImmutableRecordOverride()($analyzer, $codeList);

        if (\count($updateCodeList) > 0) {
            $key = \key($updateCodeList);
        } else {
            return [];
        }

        if ($updateCodeList[$key]['code'] !== $codeList[$key]['code']) {
            $this->writeFiles($updateCodeList);
            $this->successDetails .= "✔️ Aggregate state immutable record override to {$updateCodeList[$key]['filename']} added\n";
        } else {
            $this->successDetails .= "⬤️ Skipped: Aggregate state immutable record override to {$updateCodeList[$key]['filename']} added\n";
        }

        return $updateCodeList;
    }

    private function generateAggregateBehaviourClass(InspectioGraphCody\EventSourcingAnalyzer $analyzer, Context $ctx): array
    {
        $updateCodeList = $this->aggregateBehaviourFactory->componentFile()(
            $analyzer,
            $this->aggregatePath,
            $this->aggregatePath,
            $ctx->srcFolder . '/Domain/Api/Event.php'
        );

        if (\count($updateCodeList) > 0) {
            $key = \key($updateCodeList);
        } else {
            return [];
        }

        $this->successDetails .= "✔️ Aggregate behaviour file {$updateCodeList[$key]['filename']} updated\n";

        return $updateCodeList;
    }

    private function generateAggregateBehaviourCommandMethod(InspectioGraphCody\EventSourcingAnalyzer $analyzer, array $codeList): array
    {
        $updateCodeList = $this->aggregateBehaviourFactory->componentCommandMethod()($analyzer, $codeList);

        if (\count($updateCodeList) > 0) {
            $key = \key($updateCodeList);
        } else {
            return [];
        }

        if ($updateCodeList[$key]['code'] !== $codeList[$key]['code']) {
            $this->writeFiles($updateCodeList);
            $this->successDetails .= "✔️ Aggregate behaviour command method to {$updateCodeList[$key]['filename']} added\n";
        } else {
            $this->successDetails .= "⬤ Skipped: Aggregate behaviour command method to {$updateCodeList[$key]['filename']} added\n";
        }

        return $updateCodeList;
    }

    private function generateAggregateBehaviourEventMethod(InspectioGraphCody\EventSourcingAnalyzer $analyzer, array $codeList): array
    {
        $updateCodeList = $this->aggregateBehaviourFactory->componentEventMethod()($analyzer, $codeList);

        if (\count($updateCodeList) > 0) {
            $key = \key($updateCodeList);
        } else {
            return [];
        }

        if ($updateCodeList[$key]['code'] !== $codeList[$key]['code']) {
            $this->writeFiles($updateCodeList);
            $this->successDetails .= "✔️ Aggregate behaviour event method to {$updateCodeList[$key]['filename']} added\n";
        } else {
            $this->successDetails .= "⬤ Skipped: Aggregate behaviour event method to {$updateCodeList[$key]['filename']} added\n";
        }

        return $updateCodeList;
    }

    private function initFactories($ctx): void
    {
        $this->aggregateStateFactory = AggregateStateFactory::withDefaultConfig();

        $this->aggregateStateFactory->config()->getClassInfoList()->addClassInfo(
            new Psr4Info(
                $ctx->srcFolder,
                $ctx->appNamespace,
                $ctx->filterDirectoryToNamespace,
                $ctx->filterNamespaceToDirectory
            )
        );

        $this->aggregateBehaviourFactory = AggregateBehaviourFactory::withDefaultConfig(
            $this->aggregateStateFactory->config()
        );

        $this->aggregateBehaviourFactory->config()->getClassInfoList()->addClassInfo(
            new Psr4Info(
                $ctx->srcFolder,
                $ctx->appNamespace,
                $ctx->filterDirectoryToNamespace,
                $ctx->filterNamespaceToDirectory
            )
        );
    }
}

return new Aggregate();
