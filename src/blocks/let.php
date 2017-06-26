<?php

namespace Deval;

class LetBlock implements Block
{
	public function __construct ($assignments, $body)
	{
		$this->assignments = $assignments;
		$this->body = $body;
	}

	public function compile ($generator, &$volatiles)
	{
		// Evalulate or generate code for assignment variables
		$assignments = new Output ();
		$expressions = array ();
		$provides = array ();

		foreach ($this->assignments as $assignment)
		{
			list ($name, $value) = $assignment;

			// Inject expressions computed from previous assignments
			$value = $value->inject ($expressions);

			// Append to expressions if assignment should be evaluated
			if (true /* FIXME: some smart heuristic */)
				$expressions[$name] = $value;

			// Or generate dynamic assignment otherwise
			else
			{
				// Generate evaluation code for current variable
				$requires = array ();
				$assignments->append_code (Generator::emit_symbol ($name) . '=' . $value->generate ($generator, $requires) . ';');

				// Append required volatiles but the ones provided by previous assignments
				$volatiles += array_diff_key ($requires, $provides);

				// Mark variable as available for next assignments
				$provides[$name] = true;
			}
		}

		// Backup provided variables and assign them new values
		$output = new Output ();
		$output->append_code (Generator::emit_scope_push (array_keys ($provides)));
		$output->append ($assignments);

		// Generate body evaluation and restore variables
		$body = $this->body->inject ($expressions);

		$requires = array ();
		$output->append ($body->compile ($generator, $requires));
		$output->append_code (Generator::emit_scope_pop (array_keys ($provides)));

		// Append required volatiles but the ones provided by all assignments
		$volatiles += array_diff_key ($requires, $provides);

		return $output;
	}

	public function inject ($expressions)
	{
		$assignments = array ();

		foreach ($this->assignments as $assignment)
		{
			list ($name, $value) = $assignment;

			$assignments[] = array ($name, $value->inject ($expressions));

			unset ($expressions[$name]);
		}

		return new self ($assignments, $this->body->inject ($expressions));
	}

	public function is_void ()
	{
		return $this->body->is_void ();
	}

	public function resolve ($blocks)
	{
		return new self ($this->assignments, $this->body->resolve ($blocks));
	}

	public function wrap ($value)
	{
		return new self ($this->assignments, $this->body->wrap ($value));
	}
}

?>
