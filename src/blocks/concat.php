<?php

namespace Deval;

class ConcatBlock extends Block
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

	public function compile (&$variables)
	{
		if (count ($this->blocks) < 1)
			return new Output ();

		$output = $this->blocks[0]->compile ($variables);

		for ($i = 1; $i < count ($this->blocks); ++$i)
			$output->append ($this->blocks[$i]->compile ($variables));

		return $output;
	}

	public function inject ($variables)
	{
		return new self (array_map (function ($block) use (&$variables)
		{
			return $block->inject ($variables);
		}, $this->blocks));
	}
}

?>
