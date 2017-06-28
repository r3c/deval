<?php

namespace Deval;

class IfBlock implements Block
{
	public function __construct ($branches, $fallback)
	{
		$this->branches = $branches;
		$this->fallback = $fallback;
	}

	public function compile ($generator, $expressions, &$variables)
	{
		$keyword = 'if';
		$output = new Output ();
		$static = true;

		foreach ($this->branches as $branch)
		{
			list ($condition, $body) = $branch;

			// Conditions can be statically evaluated if previous ones were too
			$condition = $condition->inject ($expressions);

			if ($static && $condition->get_value ($result))
			{
				if ($result)
					return $body->compile ($generator, $expressions, $variables);

				continue;
			}

			// First non-static condition triggers dynamic code generation
			$output->append_code ($keyword . '(' . $condition->generate ($generator, $variables) . ')');
			$output->append_code ('{');
			$output->append ($body->compile ($generator, $expressions, $variables));
			$output->append_code ('}');

			$keyword = 'else if';
			$static = false;
		}

		// Output fallback if conditions were static and evaluated to false
		if ($static)
			return $this->fallback->compile ($generator, $expressions, $variables);

		// Otherwise generate dynamic fallback code
		$fallback = $this->fallback->compile ($generator, $expressions, $variables);

		if ($fallback->has_data ())
		{
			$output->append_code ('else');
			$output->append_code ('{');
			$output->append ($fallback);
			$output->append_code ('}');
		}

		return $output;
	}

	public function resolve ($blocks)
	{
		$branches = array ();

		foreach ($this->branches as $branch)
			$branches[] = array ($branch[0], $branch[1]->resolve ($blocks));

		return new self ($branches, $this->fallback->resolve ($blocks));
	}

	public function wrap ($caller)
	{
		$branches = array ();

		foreach ($this->branches as $branch)
			$branches[] = array ($branch[0], $branch[1]->wrap ($caller));

		return new self ($branches, $this->fallback->wrap ($caller));
	}
}

?>
