<?php

namespace Deval;

class UnaryExpression implements Expression
{
	public function __construct ($value, $op)
	{
		static $callbacks;

		if (!isset ($callbacks))
		{
			$callbacks = array
			(
				'!'	=> function ($value) { return !$value; },
				'+'	=> function ($value) { return $value; },
				'-'	=> function ($value) { return -$value; },
				'~'	=> function ($value) { return ~$value; }
			);
		}

		if (!isset ($callbacks[$op]))
			throw new \Exception ('undefined unary operator');

		$this->callback = $callbacks[$op];
		$this->op = $op;
		$this->value = $value;
	}

	public function __toString ()
	{
		return $this->op . $this->value;
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
		return $this->op . $this->value->generate ($generator, $volatiles);
	}

	public function inject ($expressions)
	{
		$value = $this->value->inject ($expressions);

		if (!$value->get_value ($result))
			return new self ($value, $this->op);

		$callback = $this->callback;

		return new ConstantExpression ($callback ($result));
	}
}

?>
