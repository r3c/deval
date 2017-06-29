<?php

namespace Deval;

class MemberExpression implements Expression
{
	public function __construct ($source, $offset)
	{
		$this->offset = $offset;
		$this->source = $source;
	}

	public function __toString ()
	{
		return $this->source . '[' . $this->offset . ']';
	}

	public function count_symbol ($name)
	{
		return $this->offset->count_symbol ($name) + $this->source->count_symbol ($name);
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
		$offset = $this->offset->generate ($generator, $variables);
		$source = $this->source->generate ($generator, $variables);

		return Generator::emit_member ($source, $offset);
	}

	public function inject ($expressions)
	{
		$offset = $this->offset->inject ($expressions);
		$source = $this->source->inject ($expressions);

		// Member can be fetched from source only if offset can be evaluated
		if ($offset->get_value ($key))
		{
			// Fetch member from parent if source can be evaluated
			if ($source->get_value ($parent))
				return new ConstantExpression (Runtime::member ($parent, $key));

			// Otherwise find member in source elements if possible
			else if ($source->get_elements ($elements) && array_key_exists ($key, $elements))
				return new GroupExpression ($elements[$key]);
		}

		// Otherwise return injected source and offset
		return new self ($source, $offset);
	}
}

?>
