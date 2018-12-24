<?php

namespace Deval;

class LabelBlock implements Block
{
    public function __construct($name)
    {
        $this->name = $name;
    }

    public function compile($generator, $preserves)
    {
        throw new \Exception('cannot compile label block');
    }

    public function get_symbols()
    {
        return array();
    }

    public function inject($invariants)
    {
        throw new \Exception('cannot inject label block');
    }

    public function resolve($blocks)
    {
        if (isset($blocks[$this->name])) {
            return $blocks[$this->name];
        }

        return new VoidBlock();
    }

    public function wrap($caller)
    {
        return $this;
    }
}
