<?php declare(strict_types=1);

namespace PhpLenientParser\Statement;

use PhpLenientParser\Expression\Name;
use PhpLenientParser\ParserStateInterface;
use PhpParser\Node;
use PhpParser\Parser\Tokens;

class Namespace_ implements StatementInterface
{
    /**
     * @var Name
     */
    private $nameParser;

    /**
     * @var StatementParserInterface
     */
    private $innerStatementsParser;

    public function __construct(Name $nameParser, StatementParserInterface $innerStatementsParser)
    {
        $this->nameParser = $nameParser;
        $this->innerStatementsParser = $innerStatementsParser;
    }

    public function parse(ParserStateInterface $parser)
    {
        if (!in_array($parser->lookAhead(1)->type, [ord('{'), Tokens::T_STRING])) {
            return null;
        }

        $token = $parser->eat();
        $name = $this->nameParser->parse($parser);

        $stmts = [];
        if ($parser->eatIf(ord('{')) !== null) {
            $kind = Node\Stmt\Namespace_::KIND_BRACED;
            $stmts = $this->innerStatementsParser->parseList($parser, ord('}'));
            $parser->assert(ord('}'));
            $last = $parser->last();
        } else {
            $kind = Node\Stmt\Namespace_::KIND_SEMICOLON;
            $parser->assert(ord(';'));
            $stmts = $this->innerStatementsParser->parseList($parser, Tokens::T_NAMESPACE);
            $last = $stmts[count($stmts) - 1] ?? $parser->last();
        }

        if ($name !== null && $name->isSpecialClassName()) {
            $parser->addError("Cannot use '$name' as namespace name", $name->getAttributes());
        }

        return new Node\Stmt\Namespace_($name, $stmts, $parser->getAttributes($token, $last, ['kind' => $kind]));
    }

    public function getToken(): ?int
    {
        return Tokens::T_NAMESPACE;
    }
}
