<?php

namespace Deval;

class UnaryExpression implements Expression
{
	public function __construct ($value, $op)
	{
		static $functions;

		if (!isset ($functions))
		{
			$functions = array
			(
				'!' => array
				(
					function ($value) { return !$value; },
					function ($value) { return '!' . $value; }
				),
				'+' => array
				(
					function ($value) { return $value; },
					function ($value) { return '+' . $value; }
				),
				'-' => array
				(
					function ($value) { return -$value; },
					function ($value) { return '-' . $value; }
				),
				'~' => array
				(
					function ($value) { return ~$value; },
					function ($value) { return '~' . $value; }
				)
			);
		}

		if (!isset ($functions[$op]))
			throw new \Exception ('undefined unary operator');

		list ($evaluate, $generate) = $functions[$op];

		$this->f_evaluate = $evaluate;
		$this->f_generate = $generate;
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

	public function generate (&$volatiles)
	{
		$generate = $this->f_generate;

		return $generate ($this->value->generate ($volatiles));
	}

	public function inject ($constants)
	{
		$value = $this->value->inject ($constants);

		if (!$value->evaluate ($result))
			return new self ($value, $this->op);

		$evaluate = $this->f_evaluate;

		return new ConstantExpression ($evaluate ($result));
	}
}

?>
