<?php

namespace Deval;

interface Expression
{
	public function __toString ();
	public function get_member ($index, &$result);
	public function get_value (&$result);
	public function generate ($generator, &$volatiles);
	public function inject ($constants);
}

?>
