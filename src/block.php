<?php

namespace Deval;

interface Block
{
	public function compile ($trim, &$volatiles);
	public function inject ($constants);
	public function resolve ($blocks);
}

?>
