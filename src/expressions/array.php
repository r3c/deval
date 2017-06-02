<?php

namespace Deval;

class ArrayExpression implements Expression
{
	public function __construct ($elements)
	{
		$this->elements = array ();

		foreach ($elements as $element)
		{
			list ($key, $value) = $element;

			if ($key !== null)
				$this->elements[$key] = $value;
			else
				$this->elements[] = $value;
		}
	}

	public function __toString ()
	{
		return '[' . implode (', ', array_map (function ($k, $v) { return $k . ': ' . $v; }, array_keys ($this->elements), $this->elements)) . ']';
	}

	public function evaluate (&$result)
	{
		return false;
	}

	public function generate ($generator, &$volatiles)
	{
		$elements = array ();

		foreach ($this->elements as $key => $value)
			$elements[$key] = $value->generate ($generator, $volatiles);

		$source = '';

		if (array_reduce (array_keys ($elements), function (&$result, $item) { return $result === $item ? $item + 1 : null; }, 0) !== count ($elements))
		{
			foreach ($elements as $key => $value)
				$source .= ($source !== '' ? ',' : '') . Generator::emit_value ($key) . '=>' . $value;
		}
		else
		{
			foreach ($elements as $value)
				$source .= ($source !== '' ? ',' : '') . $value;
		}

		return 'array(' . $source . ')';
	}

	public function inject ($constants)
	{
		$elements = array ();
		$ready = true;
		$values = array ();

		foreach ($this->elements as $key => $value)
		{
			$value = $value->inject ($constants);

			if ($value->evaluate ($result))
				$values[$key] = $result;
			else
				$ready = false;

			$elements[] = array ($key, $value);
		}

		// Return fully built array if all elements could be evaluated
		if ($ready)
			return new ConstantExpression ($values);

		// Otherwise return array construct with injected elements
		return new self ($elements);
	}
}

?>
