<?php

namespace Deval;

class BinaryExpression implements Expression
{
	public function __construct ($lhs, $rhs, $op)
	{
		static $functions;

		if (!isset ($functions))
		{
			$functions = array
			(
				'%' => array
				(
					function ($lhs, $rhs) { return $lhs % $rhs; },
					function ($lhs, $rhs) { return $lhs . '%' . $rhs; }
				),
				'&&' => array
				(
					function ($lhs, $rhs) { return $lhs && $rhs; },
					function ($lhs, $rhs) { return $lhs . '&&' . $rhs; }
				),
				'*' => array
				(
					function ($lhs, $rhs) { return $lhs * $rhs; },
					function ($lhs, $rhs) { return $lhs . '*' . $rhs; }
				),
				'+' => array
				(
					function ($lhs, $rhs) { return $lhs + $rhs; },
					function ($lhs, $rhs) { return $lhs . '+' . $rhs; }
				),
				'-' => array
				(
					function ($lhs, $rhs) { return $lhs - $rhs; },
					function ($lhs, $rhs) { return $lhs . '-' . $rhs; }
				),
				'/' => array
				(
					function ($lhs, $rhs) { return $lhs / $rhs; },
					function ($lhs, $rhs) { return $lhs . '/' . $rhs; }
				),
				'||' => array
				(
					function ($lhs, $rhs) { return $lhs || $rhs; },
					function ($lhs, $rhs) { return $lhs . '||' . $rhs; }
				)
			);
		}

		if (!isset ($functions[$op]))
			throw new \Exception ('undefined binary operator');

		list ($evaluate, $generate) = $functions[$op];

		$this->f_evaluate = $evaluate;
		$this->f_generate = $generate;
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

	public function generate (&$volatiles)
	{
		$generate = $this->f_generate;

		return $generate ($this->lhs->generate ($volatiles), $this->rhs->generate ($volatiles));
	}

	public function inject ($constants)
	{
		$lhs = $this->lhs->inject ($constants);
		$rhs = $this->rhs->inject ($constants);

		if (!$lhs->evaluate ($result1) || !$rhs->evaluate ($result2))
			return new self ($lhs, $rhs, $this->op);

		$evaluate = $this->f_evaluate;

		return new ConstantExpression ($evaluate ($result1, $result2));		
	}
}

?>
