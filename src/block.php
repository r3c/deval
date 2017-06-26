<?php

namespace Deval;

interface Block
{
	public function compile ($generator, &$volatiles);
	public function inject ($expressions);
	public function is_void ();
	public function resolve ($blocks);
	public function wrap ($value);
}

?>
