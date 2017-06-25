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

	public function get_elements (&$elements)
	{
		return false;
	}

	public function get_value (&$value)
	{
		return false;
	}

	public function generate ($generator, &$volatiles)
	{
		$arguments = array ();

		foreach ($this->arguments as $argument)
			$arguments[] = $argument->generate ($generator, $volatiles);

		// If caller can't be evaluated to a value, generate as an expression
		if (!$this->caller->get_value ($result))
		{
			$caller = $this->caller->generate ($generator, $volatiles);
			$direct = $this->caller instanceof SymbolExpression || $generator->support ('7.0.1');
		}

		// Make sure caller is valid before trying to generate code
		else if (!is_callable ($result))
			throw new CompileException ($this->caller, 'is not callable');

		// Use array caller syntax if caller is a two-elements array, e.g. "array ('Class', 'method')"
		else if (is_array ($result) && count ($result) === 2 && is_string ($result[0]) && is_string ($result[1]))
		{
			$method = new \ReflectionMethod ($result[0], $result[1]);

			if (!$method->isStatic ())
				throw new CompileException ($this->caller, 'is not a static method');

			$caller = $result[0] . '::' . $result[1];
			$direct = true;
		}

		// Use literal function name if caller is a string e.g. "func()"
		else if (is_string ($result))
		{
			$caller = $result;
			$direct = true;
		}

		// Otherwise caller is probably a closure and can't be easily serialized
		else
			throw new CompileException ($this->caller, 'only strings or arrays can be injected as functions, not closures');

		// Hack: PHP versions < 7.0.1 do not support syntax "func()()"
		if ($direct)
			return $caller . '(' . implode (',', $arguments) . ')';
		else
			return '\\call_user_func(' . implode (',', array_merge (array ($caller), $arguments)) . ')';
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

			if ($argument->get_value ($result))
				$values[] = $result;
			else
				$ready = false;

			$arguments[] = $argument;
		}

		// Invoke and pass return value if caller and arguments were evaluated
		if ($ready && $caller->get_value ($result))
		{
			if (!is_callable ($result))
				return new ErrorExpression ($result, 'is not callable');

			return new ConstantExpression (call_user_func_array ($result, $values));
		}

		// Otherwise return injected caller and arguments
		return new self ($caller, $arguments);
	}
}

?>
