<?php

namespace Deval;

class PlainBlock implements Block
{
    private $text;

    public function __construct($text)
    {
        $this->text = $text;
    }

    public function compile($generator, $preserves)
    {
        return (new Output())->append_text($generator->make_plain($this->text));
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
