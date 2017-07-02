<?php

namespace Deval;

interface Block
{
	public function compile ($generator, &$variables);
	public function count_symbol ($name);
	public function inject ($invariants);
	public function resolve ($blocks);
	public function wrap ($caller);
}

?>
