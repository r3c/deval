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

	public function inject ($constants)
	{
		throw new \Exception ('cannot inject label block');
	}

	public function resolve ($blocks)
	{
		if (!isset ($blocks[$this->name]))
			throw new RuntimeException ('undefined label "' . $this->name . '"');

		return $blocks[$this->name];
	}
}

?>
