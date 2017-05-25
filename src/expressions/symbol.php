<?php

namespace Deval;

class SymbolExpression extends Expression
{
	public function __construct ($name)
	{
		State::assert_symbol ($name);

		$this->name = $name;
	}

	public function __toString ()
	{
		return $this->name;
	}

	public function generate (&$volatiles)
	{
		$volatiles[$this->name] = true;

		return '$' . $this->name;
	}

	public function inject ($constants)
	{
		if (array_key_exists ($this->name, $constants))
			return new ConstantExpression ($constants[$this->name]);

		return $this;
	}
}

?>
