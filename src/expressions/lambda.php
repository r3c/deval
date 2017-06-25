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

	public function get_member ($index, &$result)
	{
		return false;
	}

	public function get_value (&$result)
	{
		return false;
	}

	public function generate ($generator, &$volatiles)
	{
		// Generate body, split volatiles into parameters and external uses
		$requires = array ();
		$body = $this->body->generate ($generator, $requires);

		$captures = array_diff_key ($requires, array_flip ($this->names));
		$volatiles += $captures;

		// Generate lambda code
		$emit_symbol = function ($name) use ($generator) { return $generator->emit_symbol ($name); };

		$parameters = array_map ($emit_symbol, $this->names);
		$uses = array_map ($emit_symbol, array_keys ($captures));

		return
			'function(' . implode (',', $parameters) . ')' .
			(count ($uses) > 0 ? 'use(' . implode (',', $uses) . ')' : '') .
			'{return ' . $body . ';}';
	}

	public function inject ($constants)
	{
		return new self ($this->names, $this->body->inject ($constants));
	}
}

?>
