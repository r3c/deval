<?php

namespace Deval;

class VoidBlock implements Block
{
	public function compile ($generator, &$variables)
	{
		return new Output ();
	}

	public function inject ($expressions)
	{
		return $this;
	}

	public function is_void ()
	{
		return true;
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
