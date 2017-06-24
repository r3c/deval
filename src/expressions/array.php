<?php

namespace Deval;

class ArrayExpression implements Expression
{
	public function __construct ($elements)
	{
		$this->elements = $elements;
	}

	public function __toString ()
	{
		return '[' . implode (', ', array_map (function ($e) { return ($e[0] !== null ? $e[0] . ': ' : '') . $e[1]; }, $this->elements)) . ']';
	}

	public function evaluate (&$result)
	{
		return false;
	}

	public function generate ($generator, &$volatiles)
	{
		$elements = array ();

		foreach ($this->elements as $element)
		{
			list ($key, $value) = $element;

			$elements[] = array
			(
				$key !== null ? $key->generate ($generator, $volatiles) : null,
				$value->generate ($generator, $volatiles)
			);
		}

		$source = '';

		foreach ($elements as $element)
		{
			list ($key, $value) = $element;

			if ($key !== null)
				$source .= ',' . $key . '=>' . $value;
			else
				$source .= ',' . $value;
		}

		return 'array(' . (string)substr ($source, 1) . ')';
	}

	public function inject ($constants)
	{
		$elements = array ();
		$ready = true;
		$values = array ();

		foreach ($this->elements as $element)
		{
			list ($key, $value) = $element;

			if ($key !== null)
				$key = $key->inject ($constants);

			$value = $value->inject ($constants);

			if (!$value->evaluate ($value_result))
				$ready = false;
			else if ($key === null)
				$values[] = $value_result;
			else if (!$key->evaluate ($key_result))
				$ready = false;
			else
				$values[$key_result] = $value_result;

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
