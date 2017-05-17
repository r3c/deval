<?php

namespace Deval;

abstract class Expression
{
	public function evaluate (&$result)
	{
		return false;
	}

	public abstract function generate (&$variables);

	public abstract function inject ($variables);
}

class ArrayExpression extends Expression
{
	public function __construct ($elements)
	{
		$this->elements = $elements;
	}

	public function generate (&$variables)
	{
		$elements = array ();

		foreach ($this->elements as $element)
			$elements[] = $element->generate ($variables);

		return 'array(' . implode (',', $elements) . ')';
	}

	public function inject ($variables)
	{
		$elements = array ();
		$ready = true;
		$values = array ();

		foreach ($this->elements as $element)
		{
			$element = $element->inject ($variables);

			if ($element->evaluate ($result))
				$values[] = $result;
			else
				$ready = false;

			$elements[] = $element;
		}

		// Return fully built array if all elements could be evaluated
		if ($ready)
			return new ConstantExpression ($values);

		// Otherwise return array construct with injected elements
		return new self ($elements);
	}
}

class BinaryExpression extends Expression
{
	public function __construct ($lhs, $rhs, $op)
	{
		static $functions;

		if (!isset ($functions))
		{
			$functions = array
			(
				'%' => array
				(
					function ($lhs, $rhs) { return $lhs % $rhs; },
					function ($lhs, $rhs) { return $lhs . '%' . $rhs; }
				),
				'&&' => array
				(
					function ($lhs, $rhs) { return $lhs && $rhs; },
					function ($lhs, $rhs) { return $lhs . '&&' . $rhs; }
				),
				'*' => array
				(
					function ($lhs, $rhs) { return $lhs * $rhs; },
					function ($lhs, $rhs) { return $lhs . '*' . $rhs; }
				),
				'+' => array
				(
					function ($lhs, $rhs) { return $lhs + $rhs; },
					function ($lhs, $rhs) { return $lhs . '+' . $rhs; }
				),
				'-' => array
				(
					function ($lhs, $rhs) { return $lhs - $rhs; },
					function ($lhs, $rhs) { return $lhs . '-' . $rhs; }
				),
				'/' => array
				(
					function ($lhs, $rhs) { return $lhs / $rhs; },
					function ($lhs, $rhs) { return $lhs . '/' . $rhs; }
				),
				'||' => array
				(
					function ($lhs, $rhs) { return $lhs || $rhs; },
					function ($lhs, $rhs) { return $lhs . '||' . $rhs; }
				)
			);
		}

		if (!isset ($functions[$op]))
			throw new \Exception ('unknown operator "' . $op . '"');

		list ($evaluate, $generate) = $functions[$op];

		$this->f_evaluate = $evaluate;
		$this->f_generate = $generate;
		$this->lhs = $lhs;
		$this->op = $op;
		$this->rhs = $rhs;
	}

	public function generate (&$variables)
	{
		$generate = $this->f_generate;

		return $generate ($this->lhs->generate ($variables), $this->rhs->generate ($variables));
	}

	public function inject ($variables)
	{
		$lhs = $this->lhs->inject ($variables);
		$rhs = $this->rhs->inject ($variables);

		if (!$lhs->evaluate ($result1) || !$rhs->evaluate ($result2))
			return new self ($lhs, $rhs, $this->op);

		$evaluate = $this->f_evaluate;

		return new ConstantExpression ($evaluate ($result1, $result2));		
	}
}

class ConstantExpression extends Expression
{
	public function __construct ($value)
	{
		$this->value = $value;
	}

	public function evaluate (&$result)
	{
		$result = $this->value;

		return true;
	}

	public function generate (&$variables)
	{
		return var_export ($this->value, true);
	}

	public function inject ($variables)
	{
		return $this;
	}
}

class InvokeExpression extends Expression
{
	public function __construct ($caller, $arguments)
	{
		$this->arguments = $arguments;
		$this->caller = $caller;
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
			// FIXME: replace by specific exception
			if (!is_callable ($result))
				throw new \Exception ('injected caller is not callable');

			return new ConstantExpression (call_user_func_array ($result, $values));
		}

		// Otherwise return injected caller and arguments
		return new self ($caller, $arguments);
	}
}

class MemberExpression extends Expression
{
	public function __construct ($source, $indices)
	{
		$this->indices = $indices;
		$this->source = $source;
	}

	public function generate (&$variables)
	{
		$indices = array ();

		foreach ($this->indices as $index)
			$indices[] = $index->generate ($variables);

		return State::emit_member (array ($this->source->generate ($variables), 'array(' . implode (',', $indices) . ')'));
	}

	public function inject ($variables)
	{
		$indices = array ();
		$ready = true;
		$source = $this->source->inject ($variables);
		$values = array();

		foreach ($this->indices as $index)
		{
			$index = $index->inject ($variables);

			if ($index->evaluate ($result))
				$values[] = $result;
			else
				$ready = false;

			$indices[] = $index;
		}

		// Resolve indices and pass final value if source and indices were evaluated
		if ($ready && $source->evaluate ($result))
			return new ConstantExpression (State::member ($result, $values));

		// Otherwise return injected source and indices
		return new self ($source, $indices);
	}
}

class UnaryExpression extends Expression
{
	public function __construct ($value, $op)
	{
		static $functions;

		if (!isset ($functions))
		{
			$functions = array
			(
				'!' => array
				(
					function ($value) { return !$value; },
					function ($value) { return '!' . $value; }
				),
				'+' => array
				(
					function ($value) { return $value; },
					function ($value) { return '+' . $value; }
				),
				'-' => array
				(
					function ($value) { return -$value; },
					function ($value) { return '-' . $value; }
				),
				'~' => array
				(
					function ($value) { return ~$value; },
					function ($value) { return '~' . $value; }
				)
			);
		}

		if (!isset ($functions[$op]))
			throw new \Exception ('unknown operator "' . $op . '"');

		list ($evaluate, $generate) = $functions[$op];

		$this->f_evaluate = $evaluate;
		$this->f_generate = $generate;
		$this->op = $op;
		$this->value = $value;
	}

	public function generate (&$variables)
	{
		$generate = $this->f_generate;

		return $generate ($this->value->generate ($variables));
	}

	public function inject ($variables)
	{
		$value = $this->value->inject ($variables);

		if (!$value->evaluate ($result))
			return new self ($value, $this->op);

		$evaluate = $this->f_evaluate;

		return new ConstantExpression ($evaluate ($result));
	}
}

class SymbolExpression extends Expression
{
	public function __construct ($name)
	{
		Compiler::assert_symbol ($name);

		$this->name = $name;
	}

	public function generate (&$variables)
	{
		$variables[$this->name] = true;

		return '$' . $this->name;
	}

	public function inject ($variables)
	{
		if (array_key_exists ($this->name, $variables))
			return new ConstantExpression ($variables[$this->name]);

		return $this;
	}
}

?>
