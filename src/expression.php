<?php

namespace Deval;

abstract class Expression
{
	public function evaluate (&$result)
	{
		return false;
	}

	public abstract function __toString ();

	public abstract function generate (&$variables);

	public abstract function inject ($variables);
}

?>
