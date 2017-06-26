<?php

namespace Deval;

class MemberExpression implements Expression
{
	public function __construct ($source, $index)
	{
		$this->index = $index;
		$this->source = $source;
	}

	public function __toString ()
	{
		return $this->source . '[' . $this->index . ']';
	}

	public function get_elements (&$elements)
	{
		return false;
	}

	public function get_value (&$value)
	{
		return false;
	}

	public function generate ($generator, &$volatiles)
	{
		$index = $this->index->generate ($generator, $volatiles);
		$source = $this->source->generate ($generator, $volatiles);

		return Generator::emit_member ($source, $index);
	}

	public function inject ($expressions)
	{
		$index = $this->index->inject ($expressions);
		$source = $this->source->inject ($expressions);

		// Try to fetch member from source if index can be evaluated
		if ($index->get_value ($index_value))
		{
			// Resolve to constant value if both source and index can be evaluated
			if ($source->get_value ($source_value))
				return new ConstantExpression (Runtime::member ($source_value, $index_value));

			else if ($source->get_elements ($elements) && array_key_exists ($index_value, $elements) && $elements[$index_value]->get_value ($value))
				return new ConstantExpression ($value);
		}

		// Otherwise return injected source and index
		return new self ($source, $index);
	}
}

?>
