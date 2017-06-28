<?php

namespace Deval;

class LabelBlock implements Block
{
	public function __construct ($name)
	{
		$this->name = $name;
	}

	public function compile ($generator, $expressions, &$variables)
	{
		throw new \Exception ('cannot compile label block');
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

	public function wrap ($caller)
	{
		return $this;
	}
}

?>
