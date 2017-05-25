<?php

namespace Deval;

class LetBlock implements Block
{
	public function __construct ($assignments, $body)
	{
		foreach ($assignments as $assignment)
			State::assert_symbol ($assignment[0]);

		$this->assignments = $assignments;
		$this->body = $body;
	}

	public function compile ($trim, &$volatiles)
	{
		$output = new Output ();
		$output->append_code ('{', true);

		$volatiles_exclude = array ();

		foreach ($this->assignments as $assignment)
		{
			list ($name, $value) = $assignment;

			$output->append_code ('$' . $name . '=' . $value->generate ($volatiles) . ';');

			$volatiles_exclude[$name] = true;
		}

		$volatiles_inner = array ();

		$output->append ($this->body->compile ($trim, $volatiles_inner));

		foreach (array_keys (array_diff_key ($volatiles_inner, $volatiles_exclude)) as $name)
			$volatiles[$name] = true;

		$output->append_code ('}');

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

			// Assignment can be evaluated, move to known constants
			if ($value->evaluate ($result))
				$constants[$name] = $result;

			// Assignment can't be computed yet, keep it and remove from outer scope
			else
			{
				$assignments[] = array ($name, $value);

				unset ($constants[$name]);
			}
		}

		$body = $this->body->inject ($constants);
		$body->compile (function ($s) { return ''; }, $requires);

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
