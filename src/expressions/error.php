<?php

namespace Deval;

class ErrorExpression implements Expression
{
	public function __construct ($value, $message)
	{
		$this->message = $message;
		$this->value = $value;
	}

	public function __toString ()
	{
		return '<error>';
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
		throw new InjectException ($this->value, $this->message);
	}

	public function inject ($constants)
	{
		return $this;
	}
}

?>
