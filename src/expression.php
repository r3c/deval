<?php

namespace Deval;

interface Expression
{
	public function __toString ();
	public function evaluate (&$result);
	public function generate ($generator, &$volatiles);
	public function inject ($constants);
}

?>
