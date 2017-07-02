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

		foreach ($this->branches as $branch)
		{
			list ($condition, $body) = $branch;

			$output->append_code ($keyword . '(' . $condition->generate ($generator, $variables) . ')');
			$output->append_code ('{');
			$output->append ($body->compile ($generator, $variables));
			$output->append_code ('}');

			$keyword = 'else if';
		}

		$fallback = $this->fallback->compile ($generator, $variables);

		if ($fallback->has_data ())
		{
			$output->append_code ('else');
			$output->append_code ('{');
			$output->append ($fallback);
			$output->append_code ('}');
		}

		return $output;
	}

	public function count_symbol ($name)
	{
		$count = $this->fallback->count_symbol ($name);

		foreach ($this->branches as $branch)
			$count += $branch[0]->count_symbol ($name) + $branch[1]->count_symbol ($name);

		return $count;
	}

	public function inject ($invariants)
	{
		$static = true;

		foreach ($this->branches as $branch)
		{
			list ($condition, $body) = $branch;

			// Conditions can be statically evaluated if previous ones were too
			$condition = $condition->inject ($invariants);

			if ($static && $condition->get_value ($value))
			{
				if ($value)
					return $body->inject ($invariants);

				continue;
			}

			// First non-static condition requires branches reconstruction
			$branches[] = array ($condition, $body->inject ($invariants));
			$static = false;
		}

		$fallback = $this->fallback->inject ($invariants);

		// Use fallback if conditions were all static and evaluated to false
		if ($static)
			return $fallback;

		// Otherwise rebuild command with injected branches and fallback
		return new self ($branches, $fallback);
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
