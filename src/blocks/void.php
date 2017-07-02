<?php

namespace Deval;

class VoidBlock implements Block
{
	public function compile ($generator, &$variables)
	{
		return new Output ();
	}

	public function count_symbol ($name)
	{
		return 0;
	}

	public function inject ($invariants)
	{
		return $this;
	}

	public function resolve ($blocks)
	{
		return $this;
	}

	public function wrap ($caller)
	{
		return $this;
	}
}

?>
