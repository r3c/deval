<?php

namespace Deval;

class IfBlock implements Block
{
	public function __construct ($branches, $fallback)
	{
		$this->branches = $branches;
		$this->fallback = $fallback;
	}

	public function compile ($generator, &$variables)
	{
		$keyword = 'if';
		$output = new Output ();
		$static = true;

		foreach ($this->branches as $branch)
		{
			list ($condition, $body) = $branch;

			// Conditions can be statically evaluated if previous ones were too
			$condition = $condition->inject (array ());

			if ($static && $condition->get_value ($result))
			{
				if ($result)
					return $body->compile ($generator, $variables);

				continue;
			}

			// First non-static condition triggers dynamic code generation
			$output->append_code ($keyword . '(' . $condition->generate ($generator, $variables) . ')');
			$output->append_code ('{');
			$output->append ($body->compile ($generator, $variables));
			$output->append_code ('}');

			$keyword = 'else if';
			$static = false;
		}

		// Output fallback if conditions were static and evaluated to false
		if ($static)
			return $this->fallback->compile ($generator, $variables);

		// Otherwise generate dynamic fallback code
		if (!$this->fallback->is_void ())
		{
			$output->append_code ('else');
			$output->append_code ('{');
			$output->append ($this->fallback->compile ($generator, $variables));
			$output->append_code ('}');
		}

		return $output;
	}

	public function inject ($expressions)
	{
		$branches = array ();

		foreach ($this->branches as $branch)
		{
			list ($condition, $body) = $branch;

			$branches[] = array ($condition->inject ($expressions), $body->inject ($expressions));
		}

		return new self ($branches, $this->fallback->inject ($expressions));
	}

	public function is_void ()
	{
		foreach ($this->branches as $branch)
		{
			if (!$branch->is_void ())
				return false;
		}

		return $this->fallback->is_void ();
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
