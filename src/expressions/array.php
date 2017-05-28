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
		return '[' . implode (', ', array_map (function ($e) { return (string)$e; }, $this->elements)) . ']';
	}

	public function evaluate (&$result)
	{
		return false;
	}

	public function generate ($generator, &$volatiles)
	{
		$elements = array ();

		foreach ($this->elements as $element)
			$elements[] = $element->generate ($generator, $volatiles);

		return 'array(' . implode (',', $elements) . ')';
	}

	public function inject ($constants)
	{
		$elements = array ();
		$ready = true;
		$values = array ();

		foreach ($this->elements as $element)
		{
			$element = $element->inject ($constants);

			if ($element->evaluate ($result))
				$values[] = $result;
			else
				$ready = false;

			$elements[] = $element;
		}

		// Return fully built array if all elements could be evaluated
		if ($ready)
			return new ConstantExpression ($values);

		// Otherwise return array construct with injected elements
		return new self ($elements);
	}
}

?>
