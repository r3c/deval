<?php

namespace Deval;

class DeferExpression implements Expression
{
    private $operand;
    private $moment;

    public function __construct($moment, $operand)
    {
        $this->operand = $operand;
        $this->moment = $moment;
    }

    public function __toString()
    {
        return '(' . ($this->moment ? '+' : '-') . ')' . $this->operand;
    }

    public function generate($generator, $preserves)
    {
        return $this->operand->generate($generator, $preserves);
    }

    public function get_symbols()
    {
        return $this->operand->get_symbols();
    }

    public function inject($invariants)
    {
        return new self($this->moment, $this->operand->inject($invariants));
    }

    public function try_enumerate(&$elements)
    {
        if ($this->moment && !$this->operand->try_enumerate($elements)) {
            throw new CompileException('"' . var_export((string)$this->operand, true) . '" must evaluate to a constant');
        }

        return $this->moment;
    }

    public function try_evaluate(&$value)
    {
        if ($this->moment && !$this->operand->try_evaluate($value)) {
            throw new CompileException('"' . var_export((string)$this->operand, true) . '" must evaluate to a constant');
        }

        return $this->moment;
    }
}
