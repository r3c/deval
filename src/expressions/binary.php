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
				'%'		=> array
				(
					function ($lhs, $rhs) { return $lhs . '%' . $rhs; },
					null,
					function ($lhs, $rhs) { return $lhs % $rhs; }
				),
				'&&'	=> array
				(
					function ($lhs, $rhs) { return $lhs . '?' . $rhs . ':' . $lhs; },
					function ($lhs) { return !$lhs; },
					function ($lhs, $rhs) { return $lhs ? $rhs : $lhs; }
				),
				'=='	=> array
				(
					function ($lhs, $rhs) { return $lhs . '===' . $rhs; },
					null,
					function ($lhs, $rhs) { return $lhs === $rhs; }
				),
				'!='	=> array
				(
					function ($lhs, $rhs) { return $lhs . '!==' . $rhs; },
					null,
					function ($lhs, $rhs) { return $lhs !== $rhs; }
				),
				'>'		=> array
				(
					function ($lhs, $rhs) { return $lhs . '>' . $rhs; },
					null,
					function ($lhs, $rhs) { return $lhs > $rhs; }
				),
				'>='	=> array
				(
					function ($lhs, $rhs) { return $lhs . '>=' . $rhs; },
					null,
					function ($lhs, $rhs) { return $lhs >= $rhs; }
				),
				'<'		=> array
				(
					function ($lhs, $rhs) { return $lhs . '<' . $rhs; },
					null,
					function ($lhs, $rhs) { return $lhs < $rhs; }
				),
				'<='	=> array
				(
					function ($lhs, $rhs) { return $lhs . '<=' . $rhs; },
					null,
					function ($lhs, $rhs) { return $lhs <= $rhs; }
				),
				'*'		=> array
				(
					function ($lhs, $rhs) { return $lhs . '*' . $rhs; },
					null,
					function ($lhs, $rhs) { return $lhs * $rhs; }
				),
				'+'		=> array
				(
					function ($lhs, $rhs) { return $lhs . '+' . $rhs; },
					null,
					function ($lhs, $rhs) { return $lhs + $rhs; }
				),
				'-'		=> array
				(
					function ($lhs, $rhs) { return $lhs . '-' . $rhs; },
					null,
					function ($lhs, $rhs) { return $lhs - $rhs; }
				),
				'/'		=> array
				(
					function ($lhs, $rhs) { return $lhs . '/' . $rhs; },
					null,
					function ($lhs, $rhs) { return $lhs / $rhs; }
				),
				'||'	=> array
				(
					function ($lhs, $rhs) { return $lhs . '?:' . $rhs; },
					function ($lhs) { return !!$lhs; },
					function ($lhs, $rhs) { return $lhs ?: $rhs; }
				)
			);
		}

		if (!isset ($callbacks[$op]))
			throw new \Exception ('undefined binary operator');

		list ($emit, $early, $lazy) = $callbacks[$op];

		$this->early = $early;
		$this->emit = $emit;
		$this->lazy = $lazy;
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
		$emit = $this->emit;

		return '(' . $emit ($this->lhs->generate ($generator, $variables), $this->rhs->generate ($generator, $variables)) . ')';
	}

	public function inject ($expressions)
	{
		$early = $this->early;
		$lhs = $this->lhs->inject ($expressions);

		if (!$lhs->get_value ($lhs_result))
			return new self ($lhs, $this->rhs->inject ($expressions), $this->op);
		else if ($early !== null && $early ($lhs_result))
			return new ConstantExpression ($lhs_result);
		else
		{
			$lazy = $this->lazy;
			$rhs = $this->rhs->inject ($expressions);

			if (!$rhs->get_value ($rhs_result))
				return new self ($lhs, $rhs, $this->op);
			else
				return new ConstantExpression ($lazy ($lhs_result, $rhs_result));
		}
	}
}

?>
