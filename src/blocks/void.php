<?php

namespace Deval;

class VoidBlock extends Block
{
	public function compile (&$variables)
	{
		return new Output ();
	}

	public function inject ($variables)
	{
		return $this;
	}

	public function resolve ($blocks)
	{
		return $this;
	}
}

?>
