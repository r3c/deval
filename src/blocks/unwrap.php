<?php

namespace Deval;

class UnwrapBlock implements Block
{
	public function __construct ($body)
	{
		$this->body = $body;
	}

	public function compile ($generator, $preserves)
	{
		throw new CompileException ('"unwrap" block has no "wrap" parent');
	}

	public function get_symbols ()
	{
		return $this->body->get_symbols ();
	}

	public function inject ($invariants)
	{
		return new self ($this->body->inject ($invariants));
	}

	public function resolve ($blocks)
	{
		return new self ($this->body->resolve ($blocks));
	}

	public function wrap ($caller)
	{
		return $this->body;
	}
}

?>
