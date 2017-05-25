<?php

namespace Deval;

class InvokeExpression extends Expression
{
	public function __construct ($caller, $arguments)
	{
		$this->arguments = $arguments;
		$this->caller = $caller;
	}

	public function __toString ()
	{
		return $this->caller . '(' . implode (', ', array_map (function ($a) { return (string)$a; }, $this->arguments)) . ')';
	}

	public function generate (&$variables)
	{
		$arguments = array ();

		foreach ($this->arguments as $argument)
			$arguments[] = $argument->generate ($variables);

		return $this->caller->generate ($variables) . '(' . implode (',', $arguments) . ')';
	}

	public function inject ($variables)
	{
		$arguments = array ();
		$caller = $this->caller->inject ($variables);
		$ready = true;
		$values = array ();

		foreach ($this->arguments as $argument)
		{
			$argument = $argument->inject ($variables);

			if ($argument->evaluate ($result))
				$values[] = $result;
			else
				$ready = false;

			$arguments[] = $argument;
		}

		// Invoke and pass return value if caller and arguments were evaluated
		if ($ready && $caller->evaluate ($result))
		{
			if (!is_callable ($result))
				throw new RuntimeException ('function caller is not a callable variable: ' . $caller);

			return new ConstantExpression (call_user_func_array ($result, $values));
		}

		// Otherwise return injected caller and arguments
		return new self ($caller, $arguments);
	}
}

?>
