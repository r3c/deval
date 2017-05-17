<?php

namespace Deval;

class LetBlock extends Block
{
	public function __construct ($assignments, $body)
	{
		foreach ($assignments as $assignment)
			Compiler::assert_symbol ($assignment[0]);

		$this->assignments = $assignments;
		$this->body = $body;
	}

	public function compile (&$variables)
	{
		$output = new Output ();
		$output->append_code ('{', true);

		$variables_excludes = array ();

		foreach ($this->assignments as $assignment)
		{
			list ($name, $value) = $assignment;

			$output->append_code ('$' . $name . '=' . $value->generate ($variables) . ';');

			$variables_exclude[$name] = true;
		}

		$variables_inner = array ();

		$output->append ($this->body->compile ($variables_inner));

		foreach (array_keys (array_diff_key ($variables_inner, $variables_exclude)) as $name)
			$variables[$name] = true;

		$output->append_code ('}');

		return $output;
	}

	public function inject ($variables)
	{
		$assignments = array ();
		$requires = array ();

		foreach ($this->assignments as $assignment)
		{
			list ($name, $value) = $assignment;

			$value = $value->inject ($variables);

			// Assignment can be evaluated, move to known variables
			if ($value->evaluate ($result))
				$variables[$name] = $result;

			// Assignment can't be computed yet, keep it and remove from outer scope
			else
			{
				$assignments[] = array ($name, $value);

				unset ($variables[$name]);
			}
		}

		$body = $this->body->inject ($variables);
		$body->compile ($requires);

		if (count ($assignments) === 0 || count ($requires) === 0)
			return $body;

		return new self ($assignments, $body);
	}
}

?>
