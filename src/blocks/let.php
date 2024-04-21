<?php

namespace Deval;

class LetBlock implements Block
{
    private $assignments;
    private $body;

    public function __construct($assignments, $body)
    {
        $this->assignments = $assignments;
        $this->body = $body;
    }

    public function compile($generator, $preserves)
    {
        // Evalulate or generate code for assignment variables
        $assignments = new Output();
        $backups = array();
        $output = new Output();

        foreach ($this->assignments as $assignment) {
            list($name, $expression) = $assignment;

            // Generate evaluation code for current variable
            $assignments->append_code($generator->emit_symbol($name) . '=' . $expression->generate($generator, $preserves) . ';');

            // Variable must be saved and restored if present in the preserve list
            if (isset($preserves[$name])) {
                $backups[] = $name;
            }

            // Mark variable as available for next assignments
            $preserves[$name] = true;
        }

        // Backup conflicting variables if any
        if (count($backups) > 0) {
            $store = $generator->make_local($preserves);
            $output->append_code($generator->emit_backup($store, $backups));
        }

        // Output assignments and body evaluation
        $output->append($assignments);
        $output->append($this->body->compile($generator, $preserves));

        // Restore backup variables if any
        if (count($backups) > 0) {
            $output->append_code($generator->emit_restore($store, $backups));
        }

        return $output;
    }

    public function get_symbols()
    {
        $provides = array();
        $symbols = array();

        foreach ($this->assignments as $assignment) {
            list($name, $expression) = $assignment;

            // Append symbols required for current assignment without the ones
            // provided by previous assignments
            Generator::merge_symbols($symbols, array_diff_key($expression->get_symbols(), $provides));

            $provides[$name] = true;
        }

        // Append symbols required for current assignment without the ones
        // provided by all assignments
        Generator::merge_symbols($symbols, array_diff_key($this->body->get_symbols(), $provides));

        return $symbols;
    }

    public function inject($invariants)
    {
        $assignments = array();
        $symbols = $this->body->get_symbols();

        foreach ($this->assignments as $assignment) {
            list($name, $expression) = $assignment;

            // Inject both invariants and previous assignments into expression
            $expression = $expression->inject($invariants);

            // Inline expression if constant or used at most once
            if ($expression->try_evaluate($unused) || !isset($symbols[$name]) || $symbols[$name] < 2) {
                $invariants[$name] = $expression;
            }

            // Keep as an assignment otherwise
            else {
                $assignments[] = array($name, $expression);

                unset($invariants[$name]);
            }
        }

        // Inject all invariants and assignments into body
        $body = $this->body->inject($invariants);

        // Command can be bypassed if no assignment survived injection
        if (count($assignments) === 0) {
            return $body;
        }

        return new self($assignments, $body);
    }

    public function resolve($blocks)
    {
        return new self($this->assignments, $this->body->resolve($blocks));
    }

    public function wrap($caller)
    {
        return new self($this->assignments, $this->body->wrap($caller));
    }
}
