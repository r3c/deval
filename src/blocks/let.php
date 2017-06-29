<?php

namespace Deval;

class LetBlock implements Block
{
	public function __construct ($assignments, $body)
	{
		$this->assignments = $assignments;
		$this->body = $body;
	}

	public function compile ($generator, $expressions, &$variables)
	{
		// Evalulate or generate code for assignment variables
		$assignments = new Output ();
		$provides = array ();

		foreach ($this->assignments as $assignment)
		{
			list ($name, $expression) = $assignment;

			// Inject expressions computed from previous assignments
			$expression = $expression->inject ($expressions);

			// Inline expression if used once or zero times
			if ($this->body->count_symbol ($name) < 2)
				$expressions[$name] = $expression;

			// Generate assignment code otherwise
			else
			{
				// Generate evaluation code for current variable
				$requires = array ();
				$assignments->append_code (Generator::emit_symbol ($name) . '=' . $expression->generate ($generator, $requires) . ';');

				// Append required variables but the ones provided by previous assignments
				$variables += array_diff_key ($requires, $provides);

				// Mark variable as available for next assignments
				$provides[$name] = true;
			}
		}

		// Backup provided variables and assign them new values
		$output = new Output ();
		$output->append_code (Generator::emit_scope_push (array_keys ($provides)));
		$output->append ($assignments);

		// Generate body evaluation and restore variables
		$requires = array ();

		$output->append ($this->body->compile ($generator, $expressions, $requires));
		$output->append_code (Generator::emit_scope_pop (array_keys ($provides)));

		// Append required variables but the ones provided by all assignments
		$variables += array_diff_key ($requires, $provides);

		return $output;
	}

	public function count_symbol ($name)
	{
		$count = $this->body->count_symbol ($name);

		foreach ($this->assignments as $assignment)
			$count += $assignment[1]->count_symbol ($name);

		return $count;
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
