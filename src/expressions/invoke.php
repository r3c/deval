<?php

namespace Deval;

class InvokeExpression implements Expression
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

	public function evaluate (&$result)
	{
		return false;
	}

	public function generate (&$volatiles)
	{
		$arguments = array ();

		foreach ($this->arguments as $argument)
			$arguments[] = $argument->generate ($volatiles);

		/*
		** PHP hack: function name must be written as a literal symbol when
		** constant and only in that case e.g. "func()" vs "$f = 'func'; $f()".
		*/
		if ($this->caller->evaluate ($result))
			self::check_callable ($this->caller, $result);
		else
			$result = $this->caller->generate ($volatiles);

		return $result . '(' . implode (',', $arguments) . ')';
	}

	public function inject ($constants)
	{
		$arguments = array ();
		$caller = $this->caller->inject ($constants);
		$ready = true;
		$values = array ();

		foreach ($this->arguments as $argument)
		{
			$argument = $argument->inject ($constants);

			if ($argument->evaluate ($result))
				$values[] = $result;
			else
				$ready = false;

			$arguments[] = $argument;
		}

		// Invoke and pass return value if caller and arguments were evaluated
		if ($ready && $caller->evaluate ($result))
		{
			self::check_callable ($caller, $result);

			return new ConstantExpression (call_user_func_array ($result, $values));
		}

		// Otherwise return injected caller and arguments
		return new self ($caller, $arguments);
	}

	private static function check_callable ($caller, $value)
	{
		if (!is_callable ($value))
			throw new RuntimeException ('function caller is not a callable variable: ' . $caller);
	}
}

?>
