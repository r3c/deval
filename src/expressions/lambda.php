<?php

namespace Deval;

class LambdaExpression implements Expression
{
	public function __construct ($names, $body)
	{
		$this->body = $body;
		$this->names = $names;
	}

	public function __toString ()
	{
		return '(' . implode (', ', $this->names) . ') => ' . $this->body;
	}

	public function generate ($generator)
	{
		// Helper lambda to emit symbol from name
		$emit_symbol = function ($name) use ($generator)
		{
			return $generator->emit_symbol ($name);
		};

		// Generate lambda code from captures, parameters and body expression
		$captures = array_diff_key ($this->body->get_symbols (), array_flip ($this->names));
		$parameters = array_map ($emit_symbol, $this->names);
		$uses = array_map ($emit_symbol, array_keys ($captures));

		return
			'function(' . implode (',', $parameters) . ')' .
			(count ($uses) > 0 ? 'use(' . implode (',', $uses) . ')' : '') .
			'{return ' . $this->body->generate ($generator) . ';}';
	}

	public function get_symbols ()
	{
		return array_diff_key ($this->body->get_symbols (), array_flip ($this->names));
	}

	public function inject ($invariants)
	{
		return new self ($this->names, $this->body->inject ($invariants));
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
