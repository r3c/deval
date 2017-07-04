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

	public function generate ($generator, $preserves)
	{
		$source = '';

		foreach ($this->elements as $element)
		{
			list ($e_key, $e_value) = $element;

			$value = $e_value->generate ($generator, $preserves);

			if ($e_key !== null)
				$source .= ',' . $e_key->generate ($generator, $preserves) . '=>' . $value;
			else
				$source .= ',' . $value;
		}

		return 'array(' . (string)substr ($source, 1) . ')';
	}

	public function get_symbols ()
	{
		$symbols = array ();

		foreach ($this->elements as $element)
		{
			if ($element[0] !== null)
				Generator::merge_symbols ($symbols, $element[0]->get_symbols ());

			Generator::merge_symbols ($symbols, $element[1]->get_symbols ());
		}

		return $symbols;
	}

	public function inject ($invariants)
	{
		$elements = array ();
		$ready = true;
		$values = array ();

		foreach ($this->elements as $element)
		{
			list ($key, $value) = $element;

			if ($key !== null)
				$key = $key->inject ($invariants);

			$value = $value->inject ($invariants);

			if (!$value->try_evaluate ($value_result))
				$ready = false;
			else if ($key === null)
				$values[] = $value_result;
			else if (!$key->try_evaluate ($key_result))
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

	public function try_enumerate (&$elements)
	{
		$elements = array ();

		foreach ($this->elements as $element)
		{
			list ($key, $value) = $element;

			// Let PHP define an automatic index if no key was specified
			if ($key === null)
				$elements[] = $value;

			// Assign element to index if key can be evaluated
			else if ($key->try_evaluate ($index))
				$elements[$index] = $value;

			// Otherwise keys set can't be evaluated
			else
				return false;
		}

		return true;
	}

	public function try_evaluate (&$value)
	{
		return false;
	}
}

?>
