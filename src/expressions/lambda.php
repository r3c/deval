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
		// Deduce captures from requires symbols minus provided arguments
		$captures = array_diff (array_keys ($this->body->get_symbols ()), $this->names);

		// Generate lambda code from captures, parameters and body expression
		$callback = function ($name) { return Generator::emit_symbol ($name); };

		return
			'function(' . implode (',', array_map ($callback, $this->names)) . ')' .
			(count ($captures) > 0 ? 'use(' . implode (',', array_map ($callback, $captures)) . ')' : '') .
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
