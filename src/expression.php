<?php

namespace Deval;

interface Expression
{
	public function __toString ();
	public function count_symbol ($name);
	public function generate ($generator, &$variables);
	public function inject ($invariants);
	public function try_enumerate (&$elements);
	public function try_evaluate (&$value);
}

?>
