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

	public function evaluate (&$result)
	{
		return false;
	}

	public function generate ($generator, &$volatiles)
	{
		// Generate body, split volatiles into parameters and external uses
		$volatiles_inner = array ();

		$body = $this->body->generate ($generator, $volatiles_inner);

		$volatiles_use = array_diff_key ($volatiles_inner, array_flip ($this->names));
		$volatiles += $volatiles_use;

		// Generate lambda code
		$parameters = array_map (function ($name) { return '$' . $name; }, $this->names);
		$uses = array_map (function ($name) { return '$' . $name; }, array_keys ($volatiles_use));

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
