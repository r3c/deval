<?php

namespace Deval;

interface Block
{
	public function compile ($generator, $expressions, &$variables);
	public function is_void ();
	public function resolve ($blocks);
	public function wrap ($caller);
}

?>
