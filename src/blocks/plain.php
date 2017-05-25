<?php

namespace Deval;

class PlainBlock extends Block
{
	public function __construct ($text)
	{
		$this->text = $text;
	}

	public function compile ($trim, &$volatiles)
	{
		return (new Output ())->append_text ($trim ($this->text));
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
