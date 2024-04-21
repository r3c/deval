<?php

namespace Deval;

class ConcatBlock implements Block
{
    private $blocks;

    public function __construct($blocks)
    {
        $this->blocks = $blocks;
    }

    public function compile($generator, $preserves)
    {
        $output = new Output();

        foreach ($this->blocks as $block) {
            $output->append($block->compile($generator, $preserves));
        }

        return $output;
    }

    public function get_symbols()
    {
        $symbols = array();

        foreach ($this->blocks as $block) {
            Generator::merge_symbols($symbols, $block->get_symbols());
        }

        return $symbols;
    }

    public function inject($invariants)
    {
        return $this->map(function ($block) use (&$invariants) {
            return $block->inject($invariants);
        });
    }

    public function resolve($blocks)
    {
        return $this->map(function ($block) use (&$blocks) {
            return $block->resolve($blocks);
        });
    }

    public function wrap($caller)
    {
        return $this->map(function ($block) use (&$caller) {
            return $block->wrap($caller);
        });
    }

    private function map($apply)
    {
        $blocks = array();

        foreach ($this->blocks as $block) {
            $blocks[] = $apply($block);
        }

        return new self($blocks);
    }
}
