<?php

namespace Deval;

class EchoBlock implements Block
{
    private $expression;

    public function __construct($expression)
    {
        $this->expression = $expression;
    }

    public function compile($generator, $preserves)
    {
        $output = new Output();

        if ($this->expression->try_evaluate($value)) {
            if ($value !== null && !is_scalar($value) && (!is_object($value) || !method_exists($value, '__toString'))) {
                throw new CompileException('"' . var_export($value, true) . '" cannot be converted to string');
            }

            $output->append_text($generator->make_plain((string)$value));
        } else {
            $output->append_code('echo ' . $this->expression->generate($generator, $preserves) . ';');
        }

        return $output;
    }

    public function get_symbols()
    {
        return $this->expression->get_symbols();
    }

    public function inject($invariants)
    {
        return new self($this->expression->inject($invariants));
    }

    public function resolve($blocks)
    {
        return $this;
    }

    public function wrap($caller)
    {
        return new self(new InvokeExpression($caller, array($this->expression)));
    }
}
