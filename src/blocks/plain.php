<?php

namespace Deval;

class PlainBlock implements Block
{
	public function __construct ($text)
	{
		$this->text = $text;
	}

	public function compile ($generator, &$volatiles)
	{
		return (new Output ())->append_text ($generator->make_plain ($this->text));
	}

	public function inject ($constants)
	{
		return $this;
	}

	public function resolve ($blocks)
	{
		return $this;
	}
}

?>
