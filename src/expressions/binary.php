<?php

namespace Deval;

class BinaryExpression implements Expression
{
	public function __construct ($operator, $lhs, $rhs)
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
					function ($lhs, $rhs) { return '(' . Generator::emit_local () . '=' . $lhs . ')?' . $rhs . ':' . Generator::emit_local (); },
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

		if (!isset ($callbacks[$operator]))
			throw new \Exception ('undefined binary operator');

		list ($emit, $early, $lazy) = $callbacks[$operator];

		$this->early = $early;
		$this->emit = $emit;
		$this->lazy = $lazy;
		$this->lhs = $lhs;
		$this->operator = $operator;
		$this->rhs = $rhs;
	}

	public function __toString ()
	{
		return $this->lhs . ' ' . $this->operator . ' ' . $this->rhs;
	}

	public function count_symbol ($name)
	{
		return $this->lhs->count_symbol ($name) + $this->rhs->count_symbol ($name);
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

	public function inject ($invariants)
	{
		$early = $this->early;
		$lhs = $this->lhs->inject ($invariants);

		if (!$lhs->get_value ($lhs_result))
			return new self ($this->operator, $lhs, $this->rhs->inject ($invariants));
		else if ($early !== null && $early ($lhs_result))
			return new ConstantExpression ($lhs_result);
		else
		{
			$lazy = $this->lazy;
			$rhs = $this->rhs->inject ($invariants);

			if (!$rhs->get_value ($rhs_result))
				return new self ($this->operator, $lhs, $rhs);
			else
				return new ConstantExpression ($lazy ($lhs_result, $rhs_result));
		}
	}
}

?>
