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

	public function count_symbol ($name)
	{
		$count = 0;

		foreach ($this->elements as $element)
		{
			if ($element[0] !== null)
				$count += $element[0]->count_symbol ($name);

			$count += $element[1]->count_symbol ($name);
		}

		return $count;
	}

	public function get_elements (&$elements)
	{
		$elements = array ();

		foreach ($this->elements as $element)
		{
			list ($key, $value) = $element;

			// Let PHP define an automatic index if no key was specified
			if ($key === null)
				$elements[] = $value;

			// Assign element to index if key can be evaluated
			else if ($key->get_value ($index))
				$elements[$index] = $value;

			// Otherwise keys set can't be evaluated
			else
				return false;
		}

		return true;
	}

	public function get_value (&$value)
	{
		return false;
	}

	public function generate ($generator, &$variables)
	{
		$source = '';

		foreach ($this->elements as $element)
		{
			list ($e_key, $e_value) = $element;

			$value = $e_value->generate ($generator, $variables);

			if ($e_key !== null)
				$source .= ',' . $e_key->generate ($generator, $variables) . '=>' . $value;
			else
				$source .= ',' . $value;
		}

		return 'array(' . (string)substr ($source, 1) . ')';
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

			if (!$value->get_value ($value_result))
				$ready = false;
			else if ($key === null)
				$values[] = $value_result;
			else if (!$key->get_value ($key_result))
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
