<?php

namespace Deval;

abstract class Expression
{
	abstract function __toString ();
}

class ArrayExpression extends Expression
{
	public function __construct ($elements)
	{
		$this->elements = $elements;
	}

	public function __toString ()
	{
		return '[' . implode (', ', array_map ('strval', $this->elements)) . ']';
	}
}

class BinaryExpression extends Expression
{
	public function __construct ($lhs, $rhs, $op)
	{
		$this->lhs = $lhs;
		$this->rhs = $rhs;
		$this->op = $op;
	}

	public function __toString ()
	{
		return '(' . $this->lhs . ' ' . $this->op . ' ' . $this->rhs . ')';
	}
}

class ConstantExpression extends Expression
{
	public function __construct ($value)
	{
		$this->value = $value;
	}

	public function __toString ()
	{
		return (string)$this->value;
	}
}

class InvokeExpression extends Expression
{
	public function __construct ($caller, $arguments)
	{
		$this->arguments = $arguments;
		$this->caller = $caller;
	}

	public function __toString ()
	{
		return $this->caller . '(' . implode (', ', array_map ('strval', $this->arguments)) . ')';
	}
}

class UnaryExpression extends Expression
{
	public function __construct ($value, $op)
	{
		$this->op = $op;
		$this->value = $value;
	}

	public function __toString ()
	{
		return '(' . $this->op . $this->value . ')';
	}
}

class SymbolExpression extends Expression
{
	public function __construct ($name)
	{
		$this->name = $name;
	}

	public function __toString ()
	{
		return $this->name;
	}
}

?>
