<?php

namespace Deval;

class EchoBlock implements Block
{
	public function __construct ($value)
	{
		$this->value = $value;
	}

	public function compile ($generator, &$volatiles)
	{
		return (new Output ())->append_code ('echo ' . $this->value->generate ($volatiles) . ';');
	}

	public function inject ($constants)
	{
		$value = $this->value->inject ($constants);

		if ($value->evaluate ($result))
		{
			// FIXME: verify $result can be converted to string
			return new PlainBlock ((string)$result);
		}

		return new self ($value);
	}

	public function resolve ($blocks)
	{
		return $this;
	}
}

?>
