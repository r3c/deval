<?php

namespace Deval;

class GroupExpression implements Expression
{
	public function __construct ($expression)
	{
		$this->expression = $expression;
	}

	public function __toString ()
	{
		return '(' . $this->expression . ')';
	}

	public function get_elements (&$elements)
	{
		return $this->expression->get_elements ($elements);
	}

	public function get_value (&$value)
	{
		return $this->expression->get_value ($value);
	}

	public function generate ($generator, &$volatiles)
	{
		return '(' . $this->expression->generate ($generator, $volatiles) . ')';
	}

	public function inject ($expressions)
	{
		return new self ($this->expression->inject ($expressions));
	}
}

?>
