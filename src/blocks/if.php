<?php

namespace Deval;

class IfBlock implements Block
{
    public function __construct($branches, $fallback)
    {
        $this->branches = $branches;
        $this->fallback = $fallback;
    }

    public function compile($generator, $preserves)
    {
        $keyword = 'if';
        $output = new Output();

        foreach ($this->branches as $branch) {
            list($condition, $body) = $branch;

            $output->append_code($keyword . '(' . $condition->generate($generator, $preserves) . ')');
            $output->append_code('{');
            $output->append($body->compile($generator, $preserves));
            $output->append_code('}');

            $keyword = 'else if';
        }

        $fallback = $this->fallback->compile($generator, $preserves);

        if ($fallback->has_data()) {
            $output->append_code('else');
            $output->append_code('{');
            $output->append($fallback);
            $output->append_code('}');
        }

        return $output;
    }

    public function get_symbols()
    {
        $symbols = $this->fallback->get_symbols();

        foreach ($this->branches as $branch) {
            Generator::merge_symbols($symbols, $branch[0]->get_symbols());
            Generator::merge_symbols($symbols, $branch[1]->get_symbols());
        }

        return $symbols;
    }

    public function inject($invariants)
    {
        $branches = array();
        $fallback = null;

        foreach ($this->branches as $branch) {
            list($condition, $body) = $branch;

            // Conditions can be statically evaluated if previous ones were too
            $condition = $condition->inject($invariants);

            if ($condition->try_evaluate($value)) {
                // If evaluation result is true, use body as fallback don't process following branches
                if ($value) {
                    $fallback = $body->inject($invariants);

                    break;
                }

                // Otherwise skip current branch from injected ones
                continue;
            }

            // First non-static condition requires branches reconstruction
            $branches[] = array($condition, $body->inject($invariants));
        }

        // Inject fallback unless a branch has been used as replacement fallback already
        if ($fallback === null) {
            $fallback = $this->fallback->inject($invariants);
        }

        // Emit fallback directly if no conditional branch remains
        if (count($branches) === 0) {
            return $fallback;
        }

        // Otherwise rebuild command with surviving branches and fallback
        return new self($branches, $fallback);
    }

    public function resolve($blocks)
    {
        $branches = array();

        foreach ($this->branches as $branch) {
            $branches[] = array($branch[0], $branch[1]->resolve($blocks));
        }

        return new self($branches, $this->fallback->resolve($blocks));
    }

    public function wrap($caller)
    {
        $branches = array();

        foreach ($this->branches as $branch) {
            $branches[] = array($branch[0], $branch[1]->wrap($caller));
        }

        return new self($branches, $this->fallback->wrap($caller));
    }
}
