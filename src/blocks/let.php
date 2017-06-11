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
		$output = new Output ();
		$output->append_code ('{', true);

		$names = array ();

		foreach ($this->assignments as $assignment)
		{
			list ($name, $value) = $assignment;

			// Generate evaluation code for current variable
			$volatiles_inner = array ();

			$output->append_code (Generator::emit_symbol ($name) . '=' . $value->generate ($generator, $volatiles_inner) . ';');

			// Append required volatiles but the ones provided by previous assignments
			$volatiles += array_diff_key ($volatiles_inner, $names);

			// Make variable as available for next generations
			$names[$name] = true;
		}

		// Generate evaluation code for body
		$volatiles_inner = array ();

		$output->append ($this->body->compile ($generator, $volatiles_inner));
		$output->append_code ('}');

		// Append required volatiles but the ones provided by all assignments
		$volatiles += array_diff_key ($volatiles_inner, $names);

		return $output;
	}

	public function inject ($constants)
	{
		$assignments = array ();
		$requires = array ();

		foreach ($this->assignments as $assignment)
		{
			list ($name, $value) = $assignment;

			$value = $value->inject ($constants);

			unset ($constants[$name]);

			// Assignment can be evaluated, move to known constants
			if ($value->evaluate ($result))
				$constants[$name] = $result;

			// Assignment can't be computed yet, keep in assignments
			else
				$assignments[] = array ($name, $value);
		}

		$body = $this->body->inject ($constants);
		$body->compile (Generator::dummy (), $requires);

		if (count ($assignments) === 0 || count ($requires) === 0)
			return $body;

		return new self ($assignments, $body);
	}

	public function resolve ($blocks)
	{
		return new self ($this->assignments, $this->body->resolve ($blocks));
	}
}

?>
