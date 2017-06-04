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

	public function evaluate (&$result)
	{
		return false;
	}

	public function generate ($generator, &$volatiles)
	{
		return $this->op . $this->value->generate ($generator, $volatiles);
	}

	public function inject ($constants)
	{
		$value = $this->value->inject ($constants);

		if (!$value->evaluate ($result))
			return new self ($value, $this->op);

		$callback = $this->callback;

		return new ConstantExpression ($callback ($result));
	}
}

?>
