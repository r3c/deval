<?php

namespace Deval;

class LetBlock implements Block
{
	public function __construct ($assignments, $body)
	{
		$this->assignments = $assignments;
		$this->body = $body;
	}

	public function compile ($generator, $preserves)
	{
		// Evalulate or generate code for assignment variables
		$assignments = new Output ();
		$provides = array ();

		foreach ($this->assignments as $assignment)
		{
			list ($name, $expression) = $assignment;

			// Generate evaluation code for current variable
			$assignments->append_code (Generator::emit_symbol ($name) . '=' . $expression->generate ($generator, $preserves) . ';');

			// Mark variable as available for next assignments
			$preserves[$name] = true;
			$provides[$name] = true;
		}

		$backup = $generator->make_local ($preserves);
		$names = array_keys ($provides); // FIXME: backup symbols previously existing in preserve only

		// Backup provided variables and assign them new values
		$output = new Output ();
		$output->append_code (Generator::emit_backup ($backup, $names));
		$output->append ($assignments);

		// Generate body evaluation and restore variables
		$output->append ($this->body->compile ($generator, $preserves));
		$output->append_code (Generator::emit_restore ($backup, $names));

		return $output;
	}

	public function get_symbols ()
	{
		$provides = array ();
		$symbols = array ();

		foreach ($this->assignments as $assignment)
		{
			list ($name, $expression) = $assignment;

			// Append symbols required for current assignment without the ones
			// provided by previous assignments
			Generator::merge_symbols ($symbols, array_diff_key ($expression->get_symbols (), $provides));

			$provides[$name] = true;
		}

		// Append symbols required for current assignment without the ones
		// provided by all assignments
		Generator::merge_symbols ($symbols, array_diff_key ($this->body->get_symbols (), $provides));

		return $symbols;
	}

	public function inject ($invariants)
	{
		$assignments = array ();
		$symbols = $this->body->get_symbols ();

		foreach ($this->assignments as $assignment)
		{
			list ($name, $expression) = $assignment;

			// Inject both invariants and previous assignments into expression
			$expression = $expression->inject ($invariants);

			// Inline expression if constant or used at most once
			if ($expression->try_evaluate ($unused) || !isset ($symbols[$name]) || $symbols[$name] < 2)
				$invariants[$name] = $expression;

			// Keep as an assignment otherwise
			else
				$assignments[] = array ($name, $expression);
		}

		// Inject all invariants and assignments into body
		$body = $this->body->inject ($invariants);

		// Command can be bypassed if no assignment survived injection
		if (count ($assignments) === 0)
			return $body;

		return new self ($assignments, $body);
	}

	public function resolve ($blocks)
	{
		return new self ($this->assignments, $this->body->resolve ($blocks));
	}

	public function wrap ($caller)
	{
		return new self ($this->assignments, $this->body->wrap ($caller));
	}
}

?>
