<?php

namespace Deval;

class BinaryExpression implements Expression
{
	public function __construct ($lhs, $rhs, $op)
	{
		static $callbacks;

		if (!isset ($callbacks))
		{
			$callbacks = array
			(
				'%'		=> function ($lhs, $rhs) { return $lhs % $rhs; },
				'&&'	=> function ($lhs, $rhs) { return $lhs && $rhs; },
				'==='	=> function ($lhs, $rhs) { return $lhs === $rhs; },
				'!=='	=> function ($lhs, $rhs) { return $lhs !== $rhs; },
				'>'		=> function ($lhs, $rhs) { return $lhs > $rhs; },
				'>='	=> function ($lhs, $rhs) { return $lhs >= $rhs; },
				'<'		=> function ($lhs, $rhs) { return $lhs < $rhs; },
				'<='	=> function ($lhs, $rhs) { return $lhs <= $rhs; },
				'*'		=> function ($lhs, $rhs) { return $lhs * $rhs; },
				'+'		=> function ($lhs, $rhs) { return $lhs + $rhs; },
				'-'		=> function ($lhs, $rhs) { return $lhs - $rhs; },
				'/'		=> function ($lhs, $rhs) { return $lhs / $rhs; },
				'||'	=> function ($lhs, $rhs) { return $lhs || $rhs; }
			);
		}

		if (!isset ($callbacks[$op]))
			throw new \Exception ('undefined binary operator');

		$this->callback = $callbacks[$op];
		$this->lhs = $lhs;
		$this->op = $op;
		$this->rhs = $rhs;
	}

	public function __toString ()
	{
		return $this->lhs . ' ' . $this->op . ' ' . $this->rhs;
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
		return $this->lhs->generate ($generator, $variables) . $this->op . $this->rhs->generate ($generator, $variables);
	}

	public function inject ($expressions)
	{
		$lhs = $this->lhs->inject ($expressions);
		$rhs = $this->rhs->inject ($expressions);

		if (!$lhs->get_value ($result1) || !$rhs->get_value ($result2))
			return new self ($lhs, $rhs, $this->op);

		$callback = $this->callback;

		return new ConstantExpression ($callback ($result1, $result2));		
	}
}

?>
