<?php

namespace Deval;

class ConcatBlock implements Block
{
	public static function create ($blocks)
	{
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
		return new self (array_map (function ($block) use ($constants)
		{
			return $block->inject ($constants);
		}, $this->blocks));
	}

	public function resolve ($blocks)
	{
		return new self (array_map (function ($block) use ($blocks)
		{
			return $block->resolve ($blocks);
		}, $this->blocks));
	}
}

?>
