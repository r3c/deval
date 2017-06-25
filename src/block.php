<?php

namespace Deval;

interface Block
{
	public function compile ($generator, &$volatiles);
	public function inject ($constants);
	public function is_void ();
	public function resolve ($blocks);
}

?>
