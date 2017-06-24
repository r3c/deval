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

	public function evaluate (&$result)
	{
		return $this->expression->evaluate ($result);
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
