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

interface PrettyPrinter
{
    public function prettyPrintFile(Node ...$nodes): string;

    public function applyCodeStyle(string $code): string;

    public function codeStyle(): callable;

    public function getPrettyPrinter(): PrettyPrinterAbstract;
}
