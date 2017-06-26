<?php

namespace Deval;

class LabelBlock implements Block
{
	public function __construct ($name)
	{
		$this->name = $name;
	}

	public function compile ($generator, &$volatiles)
	{
		throw new \Exception ('cannot compile label block');
	}

	public function inject ($expressions)
	{
		throw new \Exception ('cannot inject label block');
	}

	public function is_void ()
	{
		return true;
	}

	public function resolve ($blocks)
	{
		if (isset ($blocks[$this->name]))
			return $blocks[$this->name];

		return new VoidBlock ();
	}

	public function wrap ($value)
	{
		return $this;
	}
}

?>
