<?php

namespace Deval;

abstract class Expression
{
	public function evaluate (&$result)
	{
		return false;
	}

	public abstract function __toString ();

	public abstract function generate (&$volatiles);

	public abstract function inject ($constants);
}

?>
