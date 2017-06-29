<?php

namespace Deval;

class EchoBlock implements Block
{
	public function __construct ($value)
	{
		$this->value = $value;
	}

	public function compile ($generator, $expressions, &$variables)
	{
		$output = new Output ();
		$value = $this->value->inject ($expressions);

		if ($value->get_value ($result))
		{
			if ($result !== null && !is_scalar ($result) && (!is_object ($result) || !method_exists ($result, '__toString')))
				throw new CompileException ($result, 'cannot be converted to string');

			$output->append_text ($generator->make_plain ((string)$result));
		}
		else
			$output->append_code ('echo ' . $value->generate ($generator, $variables) . ';');

		return $output;
	}

	public function count_symbol ($name)
	{
		return $this->value->count_symbol ($name);
	}

	public function resolve ($blocks)
	{
		return $this;
	}

	public function wrap ($caller)
	{
		return new self (new InvokeExpression ($caller, array ($this->value)));
	}
}

?>
