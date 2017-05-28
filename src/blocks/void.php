<?php

namespace Deval;

class VoidBlock implements Block
{
	public function compile ($generator, &$volatiles)
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
