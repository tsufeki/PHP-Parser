<?php declare(strict_types=1);

namespace PhpLenientParser\Statement;

use PhpLenientParser\ParserStateInterface;
use PhpParser\Node;
use PhpParser\Parser\Tokens;

class Switch_ implements StatementInterface
{
    public function parse(ParserStateInterface $parser)
    {
        $token = $parser->eat();

        $parser->assert(ord('('));
        $condition = $parser->getExpressionParser()->parseOrError($parser);
        $parser->assert(ord(')'));

        $cases = [];
        if ($parser->eatIf(ord(':')) !== null) {
            $cases = $this->parseCases($parser, Tokens::T_ENDSWITCH);
            $parser->assert(Tokens::T_ENDSWITCH);
            $parser->assert(ord(';'));
        } elseif ($parser->eatIf(ord('{')) !== null) {
            $cases = $this->parseCases($parser, ord('}'));
            $parser->assert(ord('}'));
        }

        return new Node\Stmt\Switch_($condition, $cases, $parser->getAttributes($token, $parser->last()));
    }

    /**
     * @return Node\Stmt\Case_[]
     */
    public function parseCases(ParserStateInterface $parser, int $delimiter): array
    {
        $parser->eatIf(ord(';'));
        $cases = [];
        $delimiters = [$delimiter, Tokens::T_CASE, Tokens::T_DEFAULT];

        while (true) {
            $token = $parser->lookAhead();
            $condition = null;
            if ($token->type === Tokens::T_CASE) {
                $parser->eat();
                $condition = $parser->getExpressionParser()->parseOrError($parser);
            } elseif ($token->type === Tokens::T_DEFAULT) {
                $parser->eat();
            } else {
                break;
            }

            if ($parser->eatIf(ord(':')) === null) {
                $parser->eatIf(ord(';'));
            }

            $stmts = $parser->getStatementParser()->parseList($parser, ...$delimiters);
            if (!$parser->isNext($delimiter)) {
                // Trailing comments go to the next case
                $lastStmt = $stmts[count($stmts) - 1] ?? null;
                if ($lastStmt instanceof Node\Stmt\Nop) {
                    array_pop($stmts);
                }
            }

            $cases[] = new Node\Stmt\Case_($condition, $stmts, $parser->getAttributes($token, $parser->last()));
        }

        return $cases;
    }

    public function getToken(): ?int
    {
        return Tokens::T_SWITCH;
    }
}
