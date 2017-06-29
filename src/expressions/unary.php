<?php

namespace Deval;

class UnaryExpression implements Expression
{
	public function __construct ($operator, $operand)
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

		if (!isset ($callbacks[$operator]))
			throw new \Exception ('undefined unary operator');

		$this->callback = $callbacks[$operator];
		$this->operand = $operand;
		$this->operator = $operator;
	}

	public function __toString ()
	{
		return $this->operator . $this->operand;
	}

	public function count_symbol ($name)
	{
		return $this->operand->count_symbol ($name);
	}

	public function get_elements (&$elements)
	{
		return false;
	}

	public function get_value (&$value)
	{
		return false;
	}

	public function generate ($generator, &$variables)
	{
		return $this->operator . $this->operand->generate ($generator, $variables);
	}

	public function inject ($expressions)
	{
		$operand = $this->operand->inject ($expressions);

		if (!$operand->get_value ($value))
			return new self ($this->operator, $operand);

		$callback = $this->callback;

		return new ConstantExpression ($callback ($value));
	}
}

?>
