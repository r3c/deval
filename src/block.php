<?php

namespace Deval;

interface Block
{
	public function compile ($generator, &$volatiles);
	public function inject ($constants);
	public function resolve ($blocks);
}

?>
