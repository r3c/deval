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

	public function generate ($generator)
	{
		$offset = $this->offset->generate ($generator);
		$source = $this->source->generate ($generator);

		return Generator::emit_member ($source, $offset);
	}

	public function get_symbols ()
	{
		$symbols = array ();

		Generator::merge_symbols ($symbols, $this->offset->get_symbols ());
		Generator::merge_symbols ($symbols, $this->source->get_symbols ());

		return $symbols;
	}

	public function inject ($invariants)
	{
		$offset = $this->offset->inject ($invariants);
		$source = $this->source->inject ($invariants);

		// Member can be fetched from source only if offset can be evaluated
		if ($offset->try_evaluate ($key))
		{
			// Fetch member from parent if source can be evaluated
			if ($source->try_evaluate ($parent))
				return new ConstantExpression (m ($parent, $key));

			// Otherwise find member in source elements if possible
			else if ($source->try_enumerate ($elements) && array_key_exists ($key, $elements))
				return new GroupExpression ($elements[$key]);
		}

		// Otherwise return injected source and offset
		return new self ($source, $offset);
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
