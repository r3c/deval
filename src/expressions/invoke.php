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

	public function generate ($generator, $preserves)
	{
		$arguments = array ();

		foreach ($this->arguments as $argument)
			$arguments[] = $argument->generate ($generator, $preserves);

		// If caller can't be evaluated to a value, generate as an expression
		if (!$this->caller->try_evaluate ($result))
		{
			$caller = $this->caller->generate ($generator, $preserves);
			$direct = $this->caller instanceof SymbolExpression || $generator->support ('7.0.1');
		}

		// Make sure caller is valid before trying to generate code
		else if (!is_callable ($result))
			throw new CompileException ('"' . var_export ($this->caller, true) . '" is not callable');

		// Use array caller syntax if caller is a two-elements array, e.g. "array ('Class', 'method')"
		else if (is_array ($result) && count ($result) === 2 && is_string ($result[0]) && is_string ($result[1]))
		{
			$method = new \ReflectionMethod ($result[0], $result[1]);

			if (!$method->isStatic ())
				throw new CompileException ('"' . var_export ($this->caller, true) . '" is not a static method');

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
			throw new CompileException ('"' . var_export ($this->caller, true) . '" only strings or arrays can be injected as functions, not closures');

		// Hack: PHP versions < 7.0.1 do not support syntax "func()()"
		if ($direct)
			return $caller . '(' . implode (',', $arguments) . ')';
		else
			return '\\call_user_func(' . implode (',', array_merge (array ($caller), $arguments)) . ')';
	}

	public function get_symbols ()
	{
		$symbols = $this->caller->get_symbols ();

		foreach ($this->arguments as $argument)
			Generator::merge_symbols ($symbols, $argument->get_symbols ());

		return $symbols;
	}

	public function inject ($invariants)
	{
		$arguments = array ();
		$caller = $this->caller->inject ($invariants, true);
		$evaluate = true;
		$values = array ();

		foreach ($this->arguments as $argument)
		{
			$argument = $argument->inject ($invariants, true);

			if ($argument->try_evaluate ($value))
				$values[] = $value;
			else
				$evaluate = false;

			$arguments[] = $argument;
		}

		// Invoke and pass return value if caller and arguments were evaluated
		if ($evaluate && $caller->try_evaluate ($value))
		{
			if (!is_callable ($value))
				throw new CompileException ('"' . var_export ($caller, true) . '" is not callable');

			return new ConstantExpression (call_user_func_array ($value, $values));
		}

		// Otherwise return injected caller and arguments
		return new self ($caller, $arguments);
	}

	public function try_enumerate (&$elements)
	{
		return false;
	}

	public function try_evaluate (&$value)
	{
		return false;
	}
}

?>
