<?php

namespace Deval;

class BinaryExpression extends Expression
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
			throw new \Exception ('unknown operator "' . $op . '"');

		list ($evaluate, $generate) = $functions[$op];

		$this->f_evaluate = $evaluate;
		$this->f_generate = $generate;
		$this->lhs = $lhs;
		$this->op = $op;
		$this->rhs = $rhs;
	}

	public function generate (&$variables)
	{
		$generate = $this->f_generate;

		return $generate ($this->lhs->generate ($variables), $this->rhs->generate ($variables));
	}

	public function inject ($variables)
	{
		$lhs = $this->lhs->inject ($variables);
		$rhs = $this->rhs->inject ($variables);

		if (!$lhs->evaluate ($result1) || !$rhs->evaluate ($result2))
			return new self ($lhs, $rhs, $this->op);

		$evaluate = $this->f_evaluate;

		return new ConstantExpression ($evaluate ($result1, $result2));		
	}
}

?>
