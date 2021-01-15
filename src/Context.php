<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-cody for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-cody/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-cody/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\Cody;

use EventEngine\CodeGenerator\Cody\Printer\CodeSnifferPrinter;
use EventEngine\CodeGenerator\Cody\Printer\PrettyPrinter;
use OpenCodeModeling\Filter\FilterFactory;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;

final class Context
{
    public string $appNamespace;
    public string $serviceName;
    public string $srcFolder;
    public string $sharedValueObjectFolder;

    /**
     * @var callable
     */
    public $filterClassName;

    /**
     * @var callable
     */
    public $filterPropertyName;

    /**
     * @var callable
     */
    public $filterMethodName;

    /**
     * @var callable
     */
    public $filterConstName;

    /**
     * @var callable
     */
    public $filterConstValue;

    /**
     * @var callable
     */
    public $filterDirectoryToNamespace;

    /**
     * @var callable
     */
    public $filterNamespaceToDirectory;

    public Parser $parser;
    public PrettyPrinter $printer;

    public function __construct(
        string $appNamespace,
        string $serviceName,
        string $srcFolder,
        string $sharedValueObjectFolder
    ) {
        $this->appNamespace = $appNamespace;
        $this->serviceName = $serviceName;
        $this->srcFolder = $srcFolder;
        $this->sharedValueObjectFolder = $sharedValueObjectFolder;
        $this->filterClassName = FilterFactory::classNameFilter();
        $this->filterPropertyName = FilterFactory::propertyNameFilter();
        $this->filterMethodName = FilterFactory::methodNameFilter();
        $this->filterConstName = FilterFactory::constantNameFilter();
        $this->filterConstValue = FilterFactory::constantValueFilter();
        $this->filterDirectoryToNamespace = FilterFactory::directoryToNamespaceFilter();
        $this->filterNamespaceToDirectory = FilterFactory::namespaceToDirectoryFilter();
        $this->parser = (new ParserFactory())->create(ParserFactory::ONLY_PHP7);
        $this->printer = new CodeSnifferPrinter(
            new Standard(['shortArraySyntax' => true]),
            // you can use also \OpenCodeModeling\CodeGenerator\Transformator\PhpCodeSniffer
            function (string $code) {
                return $code;
            }
        );
    }
}
