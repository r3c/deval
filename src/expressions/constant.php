<?php

namespace Deval;

class ConstantExpression extends Expression
{
	public function __construct ($value)
	{
		$this->value = $value;
	}

	public function __toString ()
	{
		return var_export ($this->value, true);
	}

	public function evaluate (&$result)
	{
		$result = $this->value;

		return true;
	}

	public function generate (&$variables)
	{
		return State::export ($this->value);
	}

	public function inject ($variables)
	{
		return $this;
	}
}

?>
