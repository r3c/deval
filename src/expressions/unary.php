<?php

namespace Deval;

class UnaryExpression implements Expression
{
    public function __construct($operator, $operand)
    {
        static $callbacks;

        if (!isset($callbacks)) {
            $callbacks = array(
                '!'	=> function ($value) {
                    return !$value;
                },
                '+'	=> function ($value) {
                    return $value;
                },
                '-'	=> function ($value) {
                    return -$value;
                },
                '~'	=> function ($value) {
                    return ~$value;
                }
            );
        }

        if (!isset($callbacks[$operator])) {
            throw new \Exception('undefined unary operator');
        }

        $this->callback = $callbacks[$operator];
        $this->operand = $operand;
        $this->operator = $operator;
    }

    public function __toString()
    {
        return $this->operator . $this->operand;
    }

    public function generate($generator, $preserves)
    {
        return $this->operator . $this->operand->generate($generator, $preserves);
    }

    public function get_symbols()
    {
        return $this->operand->get_symbols();
    }

    public function inject($invariants)
    {
        $operand = $this->operand->inject($invariants);

        if (!$operand->try_evaluate($value)) {
            return new self($this->operator, $operand);
        }

        $callback = $this->callback;

        return new ConstantExpression($callback($value));
    }

    public function try_enumerate(&$elements)
    {
        return false;
    }

    public function try_evaluate(&$value)
    {
        return false;
    }
}
