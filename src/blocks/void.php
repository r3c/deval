<?php

namespace Deval;

class VoidBlock implements Block
{
	public function compile ($generator, $expressions, &$variables)
	{
		return new Output ();
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
