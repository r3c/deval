<?php

namespace Deval;

class IfBlock implements Block
{
	public function __construct ($branches, $fallback)
	{
		$this->branches = $branches;
		$this->fallback = $fallback;
	}

	public function compile ($generator, &$volatiles)
	{
		$output = new Output ();
		$first = true;

		foreach ($this->branches as $branch)
		{
			list ($condition, $body) = $branch;

			$output->append_code (($first ? 'if' : 'else if ') . '(' . $condition->generate ($generator, $volatiles) . ')');
			$output->append_code ('{');
			$output->append ($body->compile ($generator, $volatiles));
			$output->append_code ('}');

			$first = false;
		}

		if ($this->fallback !== null)
		{
			$output->append_code ('else');
			$output->append_code ('{');
			$output->append ($this->fallback->compile ($generator, $volatiles));
			$output->append_code ('}');
		}

		return $output;
	}

	public function inject ($constants)
	{
		// Evaluate conditions to find matching branch if any
		$remains = array ();

		foreach ($this->branches as $branch)
		{
			list ($condition, $body) = $branch;

			$condition = $condition->inject ($constants);

			if (!$condition->evaluate ($result))
				$remains[] = array ($condition, $body);
			else if ($result)
				return $body->inject ($constants);
		}

		$fallback = $this->fallback !== null ? $this->fallback->inject ($constants) : null;

		// No unevaluated branch remains, return fallback or empty block
		if (count ($remains) === 0)
			return $fallback !== null ? $fallback : new VoidBlock ();

		// Inject constants in remaining branch and rebuild block
		$injects = array ();

		foreach ($remains as $branch)
		{
			list ($condition, $body) = $branch;

			$injects[] = array ($condition, $body->inject ($constants));
		}

		return new self ($injects, $fallback);
	}

	public function resolve ($blocks)
	{
		$branches = array ();
		$fallback = $this->fallback !== null ? $this->fallback->resolve ($blocks) : null;

		foreach ($this->branches as $branch)
			$branches[] = array ($branch[0], $branch[1]->resolve ($blocks));

		return new self ($branches, $fallback);
	}
}

?>
