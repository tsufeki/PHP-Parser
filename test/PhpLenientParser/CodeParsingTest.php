<?php

namespace PhpLenientParser;

use PhpParser\Parser as BaseParser;
use PhpParser\Error;
use PhpParser\ErrorHandler;
use PhpParser\NodeDumper;
use PhpParser\Comment;

require_once __DIR__ . '/CodeTestAbstract.php';

class CodeParsingTest extends CodeTestAbstract
{
    /**
     * @dataProvider provideTestParse
     */
    public function testParse($name, $code, $expected, $mode) {
        $lexer = new Lexer\Lenient(array('usedAttributes' => array(
            'startLine', 'endLine', 'startFilePos', 'endFilePos', 'comments'
        )));
        $parser5 = new Parser\LenientPhp5($lexer);
        $parser7 = new Parser\LenientPhp7($lexer);

        $output5 = $this->getParseOutput($parser5, $code);
        $output7 = $this->getParseOutput($parser7, $code);

        if ($mode === 'php5') {
            $this->assertSame($expected, $output5, $name);
            $this->assertNotSame($expected, $output7, $name);
        } else if ($mode === 'php7') {
            $this->assertNotSame($expected, $output5, $name);
            $this->assertSame($expected, $output7, $name);
        } else {
            $this->assertSame($expected, $output5, $name);
            $this->assertSame($expected, $output7, $name);
        }
    }

    private function getParseOutput(BaseParser $parser, $code) {
        $errors = new ErrorHandler\Collecting;
        $stmts = $parser->parse($code, $errors);

        $output = '';
        foreach ($errors->getErrors() as $error) {
            $output .= $this->formatErrorMessage($error, $code) . "\n";
        }

        if (null !== $stmts) {
            $dumper = new NodeDumper(['dumpComments' => true]);
            $output .= $dumper->dump($stmts);
        }

        return canonicalize($output);
    }

    public function provideTestParse() {
        return $this->getTests(__DIR__ . '/../code/parser', 'test');
    }

    private function formatErrorMessage(Error $e, $code) {
        if ($e->hasColumnInfo()) {
            return $e->getMessageWithColumnInfo($code);
        } else {
            return $e->getMessage();
        }
    }
}
