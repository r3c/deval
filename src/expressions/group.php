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

	public function count_symbol ($name)
	{
		return $this->expression->count_symbol ($name);
	}

	public function get_elements (&$elements)
	{
		return $this->expression->get_elements ($elements);
	}

	public function get_value (&$value)
	{
		return $this->expression->get_value ($value);
	}

	public function generate ($generator, &$variables)
	{
		return '(' . $this->expression->generate ($generator, $variables) . ')';
	}

	public function inject ($expressions)
	{
		return new self ($this->expression->inject ($expressions));
	}
}

?>
