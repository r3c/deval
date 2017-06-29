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

	public function count_symbol ($name)
	{
		return $this->name === $name ? 1 : 0;
	}

	public function get_elements (&$elements)
	{
		return false;
	}

	public function get_value (&$value)
	{
		return false;
	}

	public function generate ($generator, &$variables)
	{
		$variables[$this->name] = true;

		return Generator::emit_symbol ($this->name);
	}

	public function inject ($expressions)
	{
		if (array_key_exists ($this->name, $expressions))
			return new GroupExpression ($expressions[$this->name]);

		return $this;
	}
}

?>
