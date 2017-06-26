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

	public function inject ($expressions)
	{
		return $this;
	}

	public function is_void ()
	{
		return false;
	}

	public function resolve ($blocks)
	{
		return $this;
	}

	public function wrap ($value)
	{
		return $this;
	}
}

?>
