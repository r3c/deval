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

	public function generate ($generator, &$variables)
	{
		return '(' . $this->expression->generate ($generator, $variables) . ')';
	}

	public function inject ($invariants)
	{
		return new self ($this->expression->inject ($invariants));
	}

	public function try_enumerate (&$elements)
	{
		return $this->expression->try_enumerate ($elements);
	}

	public function try_evaluate (&$value)
	{
		return $this->expression->try_evaluate ($value);
	}
}

?>
