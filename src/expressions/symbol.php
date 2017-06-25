<?php

namespace Deval;

class SymbolExpression implements Expression
{
	public function __construct ($name)
	{
		$this->name = $name;
	}

	public function __toString ()
	{
		return $this->name;
	}

	public function get_elements (&$elements)
	{
		return false;
	}

	public function get_value (&$value)
	{
		return false;
	}

	public function generate ($generator, &$volatiles)
	{
		$volatiles[$this->name] = true;

		return Generator::emit_symbol ($this->name);
	}

	public function inject ($constants)
	{
		if (array_key_exists ($this->name, $constants))
			return new ConstantExpression ($constants[$this->name]);

		return $this;
	}
}

?>
