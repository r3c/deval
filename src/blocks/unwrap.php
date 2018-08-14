<?php

namespace Deval;

class UnwrapBlock implements Block
{
	public function __construct ($location, $body)
	{
		$this->body = $body;
		$this->location = $location;
	}

	public function compile ($generator, $preserves)
	{
		throw new \Exception ('cannot compile unwrap block');
	}

	public function get_symbols ()
	{
		return $this->body->get_symbols ();
	}

	public function inject ($invariants)
	{
		throw new \Exception ('cannot inject unwrap block');
	}

	public function resolve ($blocks)
	{
		throw new ParseException ($this->location, 'block "unwrap" has no "wrap" parent block');
	}

	public function wrap ($caller)
	{
		return $this->body;
	}
}

?>
