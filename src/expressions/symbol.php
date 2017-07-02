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

	public function generate ($generator)
	{
		return Generator::emit_symbol ($this->name);
	}

	public function get_symbols ()
	{
		return array ($this->name => 1);
	}

	public function inject ($invariants)
	{
		if (array_key_exists ($this->name, $invariants))
			return new GroupExpression ($invariants[$this->name]);

		return $this;
	}

	public function try_enumerate (&$elements)
	{
		return false;
	}

	public function try_evaluate (&$value)
	{
		return false;
	}
}

?>
