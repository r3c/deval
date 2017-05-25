<?php

namespace Deval;

interface Expression
{
	public function evaluate (&$result);
	public function __toString ();
	public function generate (&$volatiles);
	public function inject ($constants);
}

?>
