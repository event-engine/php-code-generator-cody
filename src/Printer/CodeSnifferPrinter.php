<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-cody for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-cody/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-cody/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\Cody\Printer;

use PhpParser\Node;
use PhpParser\PrettyPrinterAbstract;

final class CodeSnifferPrinter implements PrettyPrinter
{
    private PrettyPrinterAbstract $prettyPrinter;

    /**
     * @var callable
     */
    private $codeSniffer;

    public function __construct(PrettyPrinterAbstract $prettyPrinter, callable $codeSniffer)
    {
        $this->prettyPrinter = $prettyPrinter;
        $this->codeSniffer = $codeSniffer;
    }

    public function prettyPrintFile(Node ...$nodes): string
    {
        $code = $this->prettyPrinter->prettyPrintFile($nodes);

        return ($this->codeSniffer)($code);
    }

    public function applyCodeStyle(string $code): string
    {
        return ($this->codeSniffer)($code);
    }

    public function getPrettyPrinter(): PrettyPrinterAbstract
    {
        return $this->prettyPrinter;
    }
}
