<?php

namespace Deval;

class UnaryExpression extends Expression
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
			throw new \Exception ('unknown operator "' . $op . '"');

		list ($evaluate, $generate) = $functions[$op];

		$this->f_evaluate = $evaluate;
		$this->f_generate = $generate;
		$this->op = $op;
		$this->value = $value;
	}

	public function generate (&$variables)
	{
		$generate = $this->f_generate;

		return $generate ($this->value->generate ($variables));
	}

	public function inject ($variables)
	{
		$value = $this->value->inject ($variables);

		if (!$value->evaluate ($result))
			return new self ($value, $this->op);

		$evaluate = $this->f_evaluate;

		return new ConstantExpression ($evaluate ($result));
	}
}

?>
