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

	public function get_elements (&$elements)
	{
		if (!is_array ($this->value) && !($this->value instanceof \Traversable))
			throw new InjectException ($this, 'is not iterable');

		$elements = array ();

		foreach ($this->value as $key => $value)
			$elements[$key] = new self ($value);

		return true;
	}

	public function get_value (&$value)
	{
		$value = $this->value;

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
