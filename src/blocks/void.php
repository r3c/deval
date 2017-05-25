<?php

namespace Deval;

class VoidBlock extends Block
{
	public function compile ($trim, &$volatiles)
	{
		return new Output ();
	}

	public function inject ($constants)
	{
		return $this;
	}

	public function resolve ($blocks)
	{
		return $this;
	}
}

?>
