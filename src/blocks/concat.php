<?php

namespace Deval;

class ConcatBlock implements Block
{
	public function __construct ($blocks)
	{
		$this->blocks = $blocks;
	}

	public function compile ($generator, &$variables)
	{
		if (count ($this->blocks) < 1)
			return new Output ();

		$output = $this->blocks[0]->compile ($generator, $variables);

		for ($i = 1; $i < count ($this->blocks); ++$i)
			$output->append ($this->blocks[$i]->compile ($generator, $variables));

		return $output;
	}

	public function inject ($expressions)
	{
		$blocks = array ();

		foreach ($this->blocks as $block)
			$blocks[] = $block->inject ($expressions);

		switch (count ($blocks))
		{
			case 0:
				return new VoidBlock ();

			case 1:
				return $blocks[0];

			default:
				return new self ($blocks);
		}
	}

	public function is_void ()
	{
		foreach ($this->blocks as $block)
		{
			if (!$block->is_void ())
				return false;
		}

		return true;
	}

	public function resolve ($blocks)
	{
		$results = array ();

		foreach ($this->blocks as $block)
			$results[] = $block->resolve ($blocks);

		return new self ($results);
	}

	public function wrap ($caller)
	{
		$results = array ();

		foreach ($this->blocks as $block)
			$results[] = $block->wrap ($caller);

		return new self ($results);
	}
}

?>
