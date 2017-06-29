<?php

namespace Deval;

class PlainBlock implements Block
{
	public function __construct ($text)
	{
		$this->text = $text;
	}

	public function compile ($generator, $expressions, &$variables)
	{
		return (new Output ())->append_text ($generator->make_plain ($this->text));
	}

	public function count_symbol ($name)
	{
		return 0;
	}

	public function resolve ($blocks)
	{
		return $this;
	}

	public function wrap ($caller)
	{
		return $this;
	}
}

?>
