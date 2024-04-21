<?php

namespace Deval;

class ConstantExpression implements Expression
{
    private $value;

    public function __construct($value)
    {
        $this->value = $value;
    }

    public function __toString()
    {
        return var_export($this->value, true);
    }

    public function generate($generator, $preserves)
    {
        return $generator->emit_value($this->value);
    }

    public function get_symbols()
    {
        return array();
    }

    public function inject($invariants)
    {
        return $this;
    }

    public function try_enumerate(&$elements)
    {
        $elements = array();

        if (!is_array($this->value) && !($this->value instanceof \Traversable)) {
            throw new CompileException('"' . var_export($this->value, true) . '" is not iterable');
        }

        foreach ($this->value as $key => $value) {
            $elements[$key] = new self($value);
        }

        return true;
    }

    public function try_evaluate(&$value)
    {
        $value = $this->value;

        return true;
    }
}
