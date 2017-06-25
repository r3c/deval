<?php

namespace Deval;

class ConstantExpression implements Expression
{
	public function __construct ($value)
	{
		$this->value = $value;
	}

	public function __toString ()
	{
		return var_export ($this->value, true);
	}

	public function get_member ($index, &$result)
	{
		return false;
	}

	public function get_value (&$result)
	{
		$result = $this->value;

		return true;
	}

	public function generate ($generator, &$volatiles)
	{
		return Generator::emit_value ($this->value);
	}

	public function inject ($constants)
	{
		return $this;
	}
}

?>
