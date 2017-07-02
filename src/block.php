<?php

namespace Deval;

interface Block
{
	public function compile ($generator);
	public function get_symbols ();
	public function inject ($invariants);
	public function resolve ($blocks);
	public function wrap ($caller);
}

?>
