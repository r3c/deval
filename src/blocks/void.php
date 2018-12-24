<?php

namespace Deval;

class VoidBlock implements Block
{
    public function compile($generator, $preserves)
    {
        return new Output();
    }

    public function get_symbols()
    {
        return array();
    }

    public function inject($invariants)
    {
        return $this;
    }

    public function resolve($blocks)
    {
        return $this;
    }

    public function wrap($caller)
    {
        return $this;
    }
}
