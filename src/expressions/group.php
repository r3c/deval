<?php

namespace Deval;

class GroupExpression implements Expression
{
    private $expression;

    public function __construct($expression)
    {
        $this->expression = $expression;
    }

    public function __toString()
    {
        return '(' . $this->expression . ')';
    }

    public function generate($generator, $preserves)
    {
        return '(' . $this->expression->generate($generator, $preserves) . ')';
    }

    public function get_symbols()
    {
        return $this->expression->get_symbols();
    }

    public function inject($invariants)
    {
        return new self($this->expression->inject($invariants));
    }

    public function try_enumerate(&$elements)
    {
        return $this->expression->try_enumerate($elements);
    }

    public function try_evaluate(&$value)
    {
        return $this->expression->try_evaluate($value);
    }
}
