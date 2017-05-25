<?php

namespace Deval;

class EchoBlock extends Block
{
	public function __construct ($value)
	{
		$this->value = $value;
	}

	public function compile (&$variables)
	{
		return (new Output ())->append_code ('echo ' . $this->value->generate ($variables) . ';');
	}

	public function inject ($variables)
	{
		$value = $this->value->inject ($variables);

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
