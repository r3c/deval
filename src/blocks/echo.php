<?php

namespace Deval;

class EchoBlock implements Block
{
	public function __construct ($expression)
	{
		$this->expression = $expression;
	}

	public function compile ($generator, &$variables)
	{
		$output = new Output ();

		if ($this->expression->get_value ($value))
		{
			if ($value !== null && !is_scalar ($value) && (!is_object ($value) || !method_exists ($value, '__toString')))
				throw new CompileException ($value, 'cannot be converted to string');

			$output->append_text ($generator->make_plain ((string)$value));
		}
		else
			$output->append_code ('echo ' . $this->expression->generate ($generator, $variables) . ';');

		return $output;
	}

	public function count_symbol ($name)
	{
		return $this->expression->count_symbol ($name);
	}

	public function inject ($invariants)
	{
		return new self ($this->expression->inject ($invariants));
	}

	public function resolve ($blocks)
	{
		return $this;
	}

	public function wrap ($caller)
	{
		return new self (new InvokeExpression ($caller, array ($this->expression)));
	}
}

?>
