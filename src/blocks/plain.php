<?php

namespace Deval;

class PlainBlock extends Block
{
	public function __construct ($text)
	{
		$this->text = $text;
	}

	public function compile (&$variables)
	{
		return (new Output ())->append_text ($this->text);
	}

	public function inject ($variables)
	{
		return $this;
	}
}

?>
