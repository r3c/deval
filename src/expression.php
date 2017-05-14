<?php

namespace Deval;

abstract class Expression
{
	public abstract function __toString ();

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

	public function __toString ()
	{
		return '[' . implode (', ', array_map ('strval', $this->elements)) . ']';
	}

	public function generate (&$variables)
	{
		throw new \Exception ('not implemented');
	}

	public function inject ($variables)
	{
		throw new \Exception ('not implemented');
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

	public function __toString ()
	{
		return '(' . $this->lhs . ' ' . $this->op . ' ' . $this->rhs . ')';
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

		if ($lhs->evaluate ($result1) && $rhs->evaluate ($result2))
		{
			$evaluate = $this->f_evaluate;

			return new ConstantExpression ($evaluate ($result1, $result2));
		}

		return new self ($lhs, $rhs, $this->op);
	}
}

class ConstantExpression extends Expression
{
	public function __construct ($value)
	{
		$this->value = $value;
	}

	public function __toString ()
	{
		return var_export ($this->value, true);
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

	public function __toString ()
	{
		return $this->caller . '(' . implode (', ', array_map ('strval', $this->arguments)) . ')';
	}

	public function generate (&$variables)
	{
		throw new \Exception ('not implemented');
	}

	public function inject ($variables)
	{
		throw new \Exception ('not implemented');
	}
}

class MemberExpression extends Expression
{
	public function __construct ($source, $indices)
	{
		$this->indices = $indices;
		$this->source = $source;
	}

	public function __toString ()
	{
		$indices = array_map (function ($i) { return '[' . $i . ']'; }, $this->indices);

		return $this->source . implode ('', $indices);
	}

	public function generate (&$variables)
	{
		throw new \Exception ('not implemented');
	}

	public function inject ($variables)
	{
		throw new \Exception ('not implemented');
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

	public function __toString ()
	{
		return '(' . $this->op . $this->value . ')';
	}

	public function generate (&$variables)
	{
		$generate = $this->f_generate;

		return $generate ($this->value->generate ($variables));
	}

	public function inject ($variables)
	{
		$value = $this->value->inject ($variables);

		if ($value->evaluate ($result))
		{
			$evaluate = $this->f_evaluate;

			return new ConstantExpression ($evaluate ($result));
		}

		return new self ($value, $this->op);
	}
}

class SymbolExpression extends Expression
{
	public function __construct ($name)
	{
		if (!preg_match ('/^[_A-Za-z][_0-9A-Za-z]*$/', $name))
			throw new \Exception ('invalid symbol name "' . $name . '"');

		$this->name = $name;
	}

	public function __toString ()
	{
		return $this->name;
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
