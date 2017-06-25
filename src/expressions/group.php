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

	public function get_member ($index, &$result)
	{
		return $this->expression->get_member ($index, $result);
	}

	public function get_value (&$result)
	{
		return $this->expression->get_value ($result);
	}

	public function generate ($generator, &$volatiles)
	{
		return '(' . $this->expression->generate ($generator, $volatiles) . ')';
	}

	public function inject ($constants)
	{
		return new self ($this->expression->inject ($constants));
	}
}

?>
