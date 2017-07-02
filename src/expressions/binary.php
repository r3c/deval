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
					function ($generator, $lhs, $rhs) { return $lhs . '%' . $rhs; },
					null,
					function ($lhs, $rhs) { return $lhs % $rhs; }
				),
				'&&'	=> array
				(
					function ($generator, $lhs, $rhs) { return '(' . $generator->emit_temporary () . '=' . $lhs . ')?' . $rhs . ':' . $generator->emit_temporary (); },
					function ($lhs) { return !$lhs; },
					function ($lhs, $rhs) { return $lhs ? $rhs : $lhs; }
				),
				'=='	=> array
				(
					function ($generator, $lhs, $rhs) { return $lhs . '===' . $rhs; },
					null,
					function ($lhs, $rhs) { return $lhs === $rhs; }
				),
				'!='	=> array
				(
					function ($generator, $lhs, $rhs) { return $lhs . '!==' . $rhs; },
					null,
					function ($lhs, $rhs) { return $lhs !== $rhs; }
				),
				'>'		=> array
				(
					function ($generator, $lhs, $rhs) { return $lhs . '>' . $rhs; },
					null,
					function ($lhs, $rhs) { return $lhs > $rhs; }
				),
				'>='	=> array
				(
					function ($generator, $lhs, $rhs) { return $lhs . '>=' . $rhs; },
					null,
					function ($lhs, $rhs) { return $lhs >= $rhs; }
				),
				'<'		=> array
				(
					function ($generator, $lhs, $rhs) { return $lhs . '<' . $rhs; },
					null,
					function ($lhs, $rhs) { return $lhs < $rhs; }
				),
				'<='	=> array
				(
					function ($generator, $lhs, $rhs) { return $lhs . '<=' . $rhs; },
					null,
					function ($lhs, $rhs) { return $lhs <= $rhs; }
				),
				'*'		=> array
				(
					function ($generator, $lhs, $rhs) { return $lhs . '*' . $rhs; },
					null,
					function ($lhs, $rhs) { return $lhs * $rhs; }
				),
				'+'		=> array
				(
					function ($generator, $lhs, $rhs) { return $lhs . '+' . $rhs; },
					null,
					function ($lhs, $rhs) { return $lhs + $rhs; }
				),
				'-'		=> array
				(
					function ($generator, $lhs, $rhs) { return $lhs . '-' . $rhs; },
					null,
					function ($lhs, $rhs) { return $lhs - $rhs; }
				),
				'/'		=> array
				(
					function ($generator, $lhs, $rhs) { return $lhs . '/' . $rhs; },
					null,
					function ($lhs, $rhs) { return $lhs / $rhs; }
				),
				'||'	=> array
				(
					function ($generator, $lhs, $rhs) { return $lhs . '?:' . $rhs; },
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

	public function generate ($generator)
	{
		$emit = $this->emit;

		return '(' . $emit ($generator, $this->lhs->generate ($generator), $this->rhs->generate ($generator)) . ')';
	}

	public function get_symbols ()
	{
		$symbols = array ();

		Generator::merge_symbols ($symbols, $this->lhs->get_symbols ());
		Generator::merge_symbols ($symbols, $this->rhs->get_symbols ());

		return $symbols;
	}

	public function inject ($invariants)
	{
		$early = $this->early;
		$lhs = $this->lhs->inject ($invariants);

		if (!$lhs->try_evaluate ($lhs_result))
			return new self ($this->operator, $lhs, $this->rhs->inject ($invariants));
		else if ($early !== null && $early ($lhs_result))
			return new ConstantExpression ($lhs_result);
		else
		{
			$lazy = $this->lazy;
			$rhs = $this->rhs->inject ($invariants);

			if (!$rhs->try_evaluate ($rhs_result))
				return new self ($this->operator, $lhs, $rhs);
			else
				return new ConstantExpression ($lazy ($lhs_result, $rhs_result));
		}
	}

	public function try_enumerate (&$elements)
	{
		return false;
	}

	public function try_evaluate (&$value)
	{
		return false;
	}
}

?>
