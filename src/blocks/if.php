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
		$keyword = 'if';
		$output = new Output ();
		$static = true;

		foreach ($this->branches as $branch)
		{
			list ($condition, $body) = $branch;

			if ($static && $condition->get_value ($result))
			{
				if ($result)
					return $body->compile ($generator, $volatiles);

				continue;
			}

			$output->append_code ($keyword . '(' . $condition->generate ($generator, $volatiles) . ')');
			$output->append_code ('{');
			$output->append ($body->compile ($generator, $volatiles));
			$output->append_code ('}');

			$keyword = 'else if';
			$static = false;
		}

		if ($static)
			return $this->fallback->compile ($generator, $volatiles);

		if (!$this->fallback->is_void ())
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
		$branches = array ();

		foreach ($this->branches as $branch)
		{
			list ($condition, $body) = $branch;

			$branches[] = array ($condition->inject ($constants), $body->inject ($constants));
		}

		return new self ($branches, $this->fallback->inject ($constants));
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

	public function wrap ($value)
	{
		$branches = array ();

		foreach ($this->branches as $branch)
			$branches[] = array ($branch[0], $branch[1]->wrap ($value));

		return new self ($branches, $this->fallback->wrap ($value));
	}
}

?>
