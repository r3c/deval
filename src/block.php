<?php

namespace Deval;

interface Block
{
	public function compile ($generator, $expressions, &$variables);
	public function count_symbol ($name);
	public function resolve ($blocks);
	public function wrap ($caller);
}

?>
