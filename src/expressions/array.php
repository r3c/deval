<?php

namespace Deval;

class ArrayExpression extends Expression
{
	public function __construct ($elements)
	{
		$this->elements = $elements;
	}

	public function generate (&$variables)
	{
		$elements = array ();

		foreach ($this->elements as $element)
			$elements[] = $element->generate ($variables);

		return 'array(' . implode (',', $elements) . ')';
	}

	public function inject ($variables)
	{
		$elements = array ();
		$ready = true;
		$values = array ();

		foreach ($this->elements as $element)
		{
			$element = $element->inject ($variables);

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
