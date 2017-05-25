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

	public function generate (&$variables)
	{
		$variables[$this->name] = true;

		return '$' . $this->name;
	}

	public function inject ($variables)
	{
		if (array_key_exists ($this->name, $variables))
			return new ConstantExpression ($variables[$this->name]);

		return $this;
	}
}

?>
