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
		$output = new Output ();

		if ($this->value->get_value ($result))
		{
			if ($result !== null && !is_scalar ($result) && (!is_object ($result) || !method_exists ($result, '__toString')))
				throw new CompileException ($result, 'cannot be converted to string');

			$output->append_text ($generator->make_plain ((string)$result));
		}
		else
			$output->append_code ('echo ' . $this->value->generate ($generator, $volatiles) . ';');

		return $output;
	}

	public function inject ($constants)
	{
		return new self ($this->value->inject ($constants));
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
