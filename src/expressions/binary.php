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
				'.'		=> function ($lhs, $rhs) { return $lhs . $rhs; },
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

	public function evaluate (&$result)
	{
		return false;
	}

	public function generate ($generator, &$volatiles)
	{
		return $this->lhs->generate ($generator, $volatiles) . $this->op . $this->rhs->generate ($generator, $volatiles);
	}

	public function inject ($constants)
	{
		$lhs = $this->lhs->inject ($constants);
		$rhs = $this->rhs->inject ($constants);

		if (!$lhs->evaluate ($result1) || !$rhs->evaluate ($result2))
			return new self ($lhs, $rhs, $this->op);

		$callback = $this->callback;

		return new ConstantExpression ($callback ($result1, $result2));		
	}
}

?>
