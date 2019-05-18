<?php

/**
 * Used by Twig_Extensions_Node_Trans to get a variable's content (handles filters)
 */
class Twig_Extensions_Node_Get extends Twig_Node
{
    public function __construct($name, Twig_Node_Expression $expr, $lineno, $tag = null)
    {
        parent::__construct(array('expr' => $expr), array('name' => $name), $lineno, $tag);
    }

    public function compile(Twig_Compiler $compiler)
    {
        $compiler
            ->addDebugInfo($this)
            ->write('')
            ->subcompile($this->getNode('expr'));
        ;
    }
}