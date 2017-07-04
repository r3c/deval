<?php

namespace Deval;

interface Expression
{
	public function __toString ();
	public function generate ($generator, $preserves);
	public function get_symbols ();
	public function inject ($invariants);
	public function try_enumerate (&$elements);
	public function try_evaluate (&$value);
}

?>
