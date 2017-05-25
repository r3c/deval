<?php

namespace Deval;

class IfBlock extends Block
{
	public function __construct ($branches, $fallback)
	{
		$this->branches = $branches;
		$this->fallback = $fallback;
	}

	public function compile ($trim, &$variables)
	{
		$output = new Output ();
		$first = true;

		foreach ($this->branches as $branch)
		{
			list ($condition, $body) = $branch;

			$output->append_code (($first ? 'if' : 'else if ') . '(' . $condition->generate ($variables) . ')');
			$output->append_code ('{');
			$output->append ($body->compile ($trim, $variables));
			$output->append_code ('}');

			$first = false;
		}

		if ($this->fallback !== null)
		{
			$output->append_code ('else');
			$output->append_code ('{');
			$output->append ($this->fallback->compile ($trim, $variables));
			$output->append_code ('}');
		}

		return $output;
	}

	public function inject ($variables)
	{
		$branches = array ();

		foreach ($this->branches as $branch)
		{
			list ($condition, $body) = $branch;

			$body = $body->inject ($variables);
			$condition = $condition->inject ($variables);

			if (!$condition->evaluate ($result))
				$branches[] = array ($condition, $body);
			else if ($result)
				return $body;
		}

		$fallback = $this->fallback !== null ? $this->fallback->inject ($variables) : null;

		if (count ($branches) === 0)
			return $fallback !== null ? $fallback : new VoidBlock ();

		return new self ($branches, $fallback);
	}

	public function resolve ($blocks)
	{
		$fallback = $this->fallback !== null ? $this->fallback->resolve ($blocks) : null;

		return new self (array_map (function ($branch) use ($blocks)
		{
			return array ($branch[0], $branch[1]->resolve ($blocks));
		}, $this->branches), $fallback);
	}
}

?>
