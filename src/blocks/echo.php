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
		return (new Output ())->append_code ('echo ' . $this->value->generate ($generator, $volatiles) . ';');
	}

	public function inject ($constants)
	{
		$value = $this->value->inject ($constants);

		if ($value->get_value ($result))
		{
			if ($result !== null && !is_scalar ($result) && (!is_object ($result) || !method_exists ($result, '__toString')))
				return new self (new ErrorExpression ($result, 'cannot be converted to string'));

			return new PlainBlock ((string)$result);
		}

		return new self ($value);
	}

	public function is_void ()
	{
		return $this->body->is_void ();
	}

	public function resolve ($blocks)
	{
		return $this;
	}

	public function wrap ($name)
	{
		return new self (new InvokeExpression (new SymbolExpression ($name), array ($this->value)));
	}
}

?>
