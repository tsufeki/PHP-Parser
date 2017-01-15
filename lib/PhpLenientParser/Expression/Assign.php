<?php

namespace PhpLenientParser\Expression;

use PhpLenientParser\ParserStateInterface;
use PhpParser\Node;
use PhpParser\Node\Expr;

class Assign extends AbstractOperator implements InfixInterface
{
    /**
     * @var int
     */
    private $refToken;

    /**
     * @var string
     */
    private $refNodeClass;

    /**
     * @param int $token
     * @param int $refToken
     * @param int $precedence
     */
    public function __construct($token, $refToken, $precedence)
    {
        parent::__construct($token, $precedence, Expr\Assign::class);
        $this->refToken = $refToken;
        $this->refNodeClass = Expr\AssignRef::class;
    }

    public function parse(ParserStateInterface $parser, Node $left)
    {
        $token = $parser->eat();
        $isRef = $parser->eat($this->refToken) !== null;
        $right = $parser->getExpressionParser()->parse($parser, $this->getPrecedence() - 1);
        if ($right === null) {
            $right = $parser->getExpressionParser()->makeErrorNode($token);
        }

        $class = $isRef ? $this->refNodeClass : $this->getNodeClass();
        return $parser->setAttributes(new $class($left, $right), $left, $right);
    }
}