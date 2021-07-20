<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-cody for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-cody/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-cody/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\Cody\Printer;

use PhpParser\Node;
use PhpParser\Node\Expr\ArrayItem;
use PhpParser\Node\Stmt;

final class Standard extends \PhpParser\PrettyPrinter\Standard
{
    protected function pSingleQuotedString(string $string)
    {
        $string = parent::pSingleQuotedString($string);
        // workaround for https://github.com/nikic/PHP-Parser/issues/725
        return \str_replace('d\\\\TH', 'd\TH', $string);
    }

    protected function escapeString($string, $quote)
    {
        $string = parent::escapeString($string, $quote);
        // workaround for https://github.com/nikic/PHP-Parser/issues/725
        return \str_replace('d\\\\TH', 'd\TH', $string);
    }

    protected function pCommaSeparated(array $nodes): string
    {
        $count = \count($nodes);

        if (
            $count <= 1
            || ($nodes[0] instanceof ArrayItem === false
                && $nodes[0] instanceof Node\Param === false)
        ) {
            return parent::pCommaSeparated($nodes);
        }
        $this->indent();

        $code = $this->nl;
        $code .= $this->pImplode($nodes, ',' . $this->nl);

        $this->outdent();

        $code .= $this->nl;

        return $code;
    }

    protected function pStmt_Interface(Stmt\Interface_ $node)
    {
        return $this->nl . parent::pStmt_Interface($node);
    }

    protected function pStmt_Class(Stmt\Class_ $node)
    {
        return $this->nl . parent::pStmt_Class($node);
    }

    protected function pStmt_Function(Stmt\Function_ $node)
    {
        return parent::pStmt_Function($node) . $this->nl;
    }

    protected function pStmt_ClassMethod(Stmt\ClassMethod $node)
    {
        $method = parent::pStmt_ClassMethod($node);

        return \substr($method, -1) === ';' ? $method : $method . $this->nl;
    }
}
