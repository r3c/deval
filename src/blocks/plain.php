<?php

namespace Deval;

class PlainBlock extends Block
{
	public function __construct ($text)
	{
		$this->text = $text;
	}

	public function compile ($trim, &$variables)
	{
		return (new Output ())->append_text ($trim ($this->text));
	}

	public function inject ($variables)
	{
		return $this;
	}

	public function resolve ($blocks)
	{
		return $this;
	}
}

?>
