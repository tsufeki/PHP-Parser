<?php declare(strict_types=1);

namespace PhpLenientParser\Statement;

use PhpLenientParser\ParserStateInterface;
use PhpParser\Node;

abstract class AbstractStatementParser implements StatementParserInterface
{
    public function parseList(ParserStateInterface $parser, int ...$delimiters): array
    {
        $delimiters[] = 0; // EOF.
        $stmts = [];
        while (null !== ($stmt = $this->parse($parser))) {
            $stmts = array_merge($stmts, $stmt);
        }

        $lookAhead = $parser->lookAhead();
        if (in_array($lookAhead->type, $delimiters)) {
            if (!empty($lookAhead->startAttributes['comments'])) {
                $node = new Node\Stmt\Nop();
                $parser->setAttributes($node, $lookAhead, $lookAhead);
                $stmts[] = $node;
            }
        }

        return $stmts;
    }
}