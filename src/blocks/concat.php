<?php

namespace Deval;

class ConcatBlock implements Block
{
	public function __construct ($blocks)
	{
		$this->blocks = $blocks;
	}

	public function compile ($generator, &$volatiles)
	{
		if (count ($this->blocks) < 1)
			return new Output ();

		$output = $this->blocks[0]->compile ($generator, $volatiles);

		for ($i = 1; $i < count ($this->blocks); ++$i)
			$output->append ($this->blocks[$i]->compile ($generator, $volatiles));

		return $output;
	}

	public function inject ($constants)
	{
		$blocks = array ();

		foreach ($this->blocks as $block)
			$blocks[] = $block->inject ($constants);

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

	public function wrap ($value)
	{
		$results = array ();

		foreach ($this->blocks as $block)
			$results[] = $block->wrap ($value);

		return new self ($results);
	}
}

?>
