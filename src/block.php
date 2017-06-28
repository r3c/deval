<?php

namespace Deval;

interface Block
{
	public function compile ($generator, $expressions, &$variables);
	public function resolve ($blocks);
	public function wrap ($caller);
}

?>
