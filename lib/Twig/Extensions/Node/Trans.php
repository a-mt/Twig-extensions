<?php

/*
 * This file is part of Twig.
 *
 * (c) 2010 Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Represents a trans node.
 *
 * @author Fabien Potencier <fabien.potencier@symfony-project.com>
 */
class Twig_Extensions_Node_Trans extends Twig_Node
{
    public function __construct(Twig_Node $body, Twig_Node $plural = null, Twig_Node_Expression $count = null, Twig_Node $notes = null, Twig_Node $context = null, $lineno, $tag = null)
    {
        $nodes = array('body' => $body);
        if (null !== $count) {
            $nodes['count'] = $count;
        }
        if (null !== $plural) {
            $nodes['plural'] = $plural;
        }
        if (null !== $notes) {
            $nodes['notes'] = $notes;
        }
        if (null !== $context) {
            $nodes['context'] = $context;
        }

        parent::__construct($nodes, array(), $lineno, $tag);
    }

    /**
     * {@inheritdoc}
     */
    public function compile(Twig_Compiler $compiler)
    {
        $compiler->addDebugInfo($this);

        list($msg, $vars) = $this->compileString($this->getNode('body'));

        if ($this->hasNode('plural')) {
            list($msg1, $vars1) = $this->compileString($this->getNode('plural'));

            $vars = array_merge($vars, $vars1);
        }

        $function = $this->getTransFunction($this->hasNode('plural'), $this->hasNode('context'));

        if ($this->hasNode('notes')) {
            $message = trim($this->getNode('notes')->getAttribute('data'));

            // line breaks are not allowed cause we want a single line comment
            $message = str_replace(array("\n", "\r"), ' ', $message);
            $compiler->write("// notes: {$message}\n");
        }

        $compiler
            ->write('echo '. ($vars ? 'strtr(' : '') . $function.'(')
            ->subcompile($msg)
        ;

        if ($this->hasNode('plural')) {
            $compiler
                ->raw(', ')
                ->subcompile($msg1)
                ->raw(', abs(')
                ->subcompile($this->hasNode('count') ? $this->getNode('count') : null)
                ->raw(')')
            ;
        }
        if ($this->hasNode('context')) {
            $compiler
                ->raw(', ')
                ->string(trim($this->getNode('context')->getAttribute('data')));
        }

        if($vars) {
            $compiler->raw(') , array(');

            foreach ($vars as $var) {
                if ('count' === $var->getAttribute('name')) {
                    $compiler
                        ->string('%count%')
                        ->raw(' => abs(')
                        ->subcompile($this->hasNode('count') ? $this->getNode('count') : null)
                        ->raw('), ')
                    ;
                } else {
                    $compiler
                        ->string('%'.$var->getAttribute('name').'%')
                        ->raw(' => ')
                        ->subcompile($var)
                        ->raw(', ')
                    ;
                }
            }
            $compiler->raw(')');
        }

        $compiler->raw(");\n");
    }

    /**
     * @param Twig_Node $body A Twig_Node instance
     *
     * @return array
     */
    protected function compileString(Twig_Node $body)
    {
        if ($body instanceof Twig_Node_Expression_Name || $body instanceof Twig_Node_Expression_Constant || $body instanceof Twig_Node_Expression_TempName) {
            return array($body, array());
        }

        $vars = array();
        if (count($body)) {
            $msg = '';

            foreach ($body as $node) {
                if (get_class($node) === 'Twig_Node' && $node->getNode(0) instanceof Twig_Node_SetTemp) {
                    $node = $node->getNode(1);
                }

                if ($node instanceof Twig_Node_Print) {
                    $n = $node->getNode('expr');
                    while ($n instanceof Twig_Node_Expression_Filter) {
                        $n = $n->getNode('node');
                    }
                    $msg .= sprintf('%%%s%%', $n->getAttribute('name'));
                    $vars[] = new Twig_Extensions_Node_Get($n->getAttribute('name'), $node->getNode('expr'), $n->getTemplateLine());
                } else {
                    $msg .= $node->getAttribute('data');
                }
            }
        } else {
            $msg = $body->getAttribute('data');
        }

        return array(new Twig_Node(array(new Twig_Node_Expression_Constant(trim($msg), $body->getTemplateLine()))), $vars);
    }

    /**
     * @param bool $plural Return plural or singular function to use
     *
     * @return string
     */
    protected function getTransFunction($plural, $context)
    {
        if($context) {
            return $plural ? 'npgettext' : 'pgettext';
        } else {
            return $plural ? 'ngettext' : 'gettext';
        }
    }
}
if (!function_exists('pgettext')) {
    function pgettext($context, $str) {
        $cstr  = $context . "\x04" . $str;
        $trans = gettext($cstr);

        return $trans == $cstr ? $str : $trans;
    }
}
if (!function_exists('npgettext')) {
    function npgettext($context, $str, $str_plural, $count) {
        $cstr  = $context . "\x04" . $str;
        $trans = ngettext($cstr, $str_plural, $count);

        return $trans == $cstr ? $str : $trans;
    }
}

class_alias('Twig_Extensions_Node_Trans', 'Twig\Extensions\Node\TransNode', false);
