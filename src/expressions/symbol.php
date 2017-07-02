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

	public function generate ($generator, &$variables)
	{
		$variables[$this->name] = true;

		return Generator::emit_symbol ($this->name);
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
