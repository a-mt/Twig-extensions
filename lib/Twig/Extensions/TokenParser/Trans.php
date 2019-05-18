<?php

/*
 * This file is part of Twig.
 *
 * (c) 2010 Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class Twig_Extensions_TokenParser_Trans extends Twig_TokenParser
{
    protected static $nodes = array('plural', 'context', 'notes', 'endtrans');

    /**
     * {@inheritdoc}
     */
    public function parse(Twig_Token $token)
    {
        $lineno  = $token->getLine();
        $stream  = $this->parser->getStream();
        $count   = null;
        $plural  = null;
        $notes   = null;
        $context = null;

        if (!$stream->test(Twig_Token::BLOCK_END_TYPE)) {
            $body = $this->parser->getExpressionParser()->parseExpression();
        } else {
            $stream->expect(Twig_Token::BLOCK_END_TYPE);
            $body = $this->parser->subparse(array($this, 'decideForFork'));
            $next = $stream->next()->getValue();

            $nodes = self::$nodes;
            while(true) {
                if(!$nodes || $next === 'endtrans') break;

                $head = array_shift($nodes);
                if($next !== $head) {
                    continue;
                }
                if($head == 'plural') {
                    $count = $this->parser->getExpressionParser()->parseExpression();
                }
                $stream->expect(Twig_Token::BLOCK_END_TYPE);
                $$head = $this->parser->subparse(array($this, $nodes ? 'decideForFork' : 'decideForEnd'));

                if($nodes) {
                    $next = $stream->next()->getValue();
                }
            }
        }

        $stream->expect(Twig_Token::BLOCK_END_TYPE);

        $this->checkTransString($body, $lineno);

        return new Twig_Extensions_Node_Trans($body, $plural, $count, $notes, $context, $lineno, $this->getTag());
    }

    public function decideForFork(Twig_Token $token)
    {
        return $token->test(self::$nodes);
    }

    public function decideForEnd(Twig_Token $token)
    {
        return $token->test('endtrans');
    }

    /**
     * {@inheritdoc}
     */
    public function getTag()
    {
        return 'trans';
    }

    protected function checkTransString(Twig_Node $body, $lineno)
    {
        foreach ($body as $i => $node) {
            if (
                $node instanceof Twig_Node_Text
                ||
                ($node instanceof Twig_Node_Print && $node->getNode('expr') instanceof Twig_Node_Expression_Name)
                ||
                ($node instanceof Twig_Node_Print && $node->getNode('expr') instanceof Twig_Node_Expression_Filter)
            ) {
                continue;
            }

            throw new Twig_Error_Syntax(sprintf('The text to be translated with "trans" can only contain references to simple variables'), $lineno);
        }
    }
}

class_alias('Twig_Extensions_TokenParser_Trans', 'Twig\Extensions\TokenParser\TransTokenParser', false);
