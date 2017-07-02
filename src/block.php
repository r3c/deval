<?php

namespace Deval;

interface Block
{
	public function compile ($generator, &$variables);
	public function get_symbols ();
	public function inject ($invariants);
	public function resolve ($blocks);
	public function wrap ($caller);
}

?>
