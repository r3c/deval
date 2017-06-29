<?php

namespace Deval;

interface Expression
{
	public function __toString ();
	public function count_symbol ($name);
	public function get_elements (&$elements);
	public function get_value (&$value);
	public function generate ($generator, &$variables);
	public function inject ($expressions);
}

?>
