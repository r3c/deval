<?php

namespace Deval;

abstract class Block
{
	abstract function __toString ();
	abstract function generate (&$variables);
	abstract function inject ($variables);
}

class ConcatBlock extends Block
{
	public function __construct ($blocks)
	{
		$this->blocks = $blocks;
	}

	public function __toString ()
	{
		return 'concat(' . implode (', ', array_map (function ($block) { return (string)$block; }, $this->blocks)) . ')';
	}

	public function generate (&$variables)
	{
		$render = $this->blocks[0]->generate ($variables);

		for ($i = 1; $i < count ($this->blocks); ++$i)
			$render->append ($this->blocks[$i]->generate ($variables));

		return $render;
	}

	public function inject ($variables)
	{
		return new self (array_map (function ($block) use (&$variables)
		{
			return $block->inject ($variables);
		}, $this->blocks));
	}
}

class EchoBlock extends Block
{
	public function __construct ($value)
	{
		$this->value = $value;
	}

	public function __toString ()
	{
		return 'echo(' . $this->value . ')';
	}

	public function generate (&$variables)
	{
		return (new Output ())->append_code ('echo ' . $this->value->generate ($variables) . ';');
	}

	public function inject ($variables)
	{
		$value = $this->value->inject ($variables);

		if ($value->evaluate ($result))
			return new PlainBlock ((string)$result);

		return new self ($value);
	}
}

class ForBlock extends Block
{
	public function __construct ($source, $key, $value, $body, $empty)
	{
		$this->body = $body;
		$this->empty = $empty;
		$this->key = $key;
		$this->source = $source;
		$this->value = $value;
	}

	public function __toString ()
	{
		return 'for(' . ($this->key !== null ? $this->key . ', ' . $this->value : $this->value) . ', ' . $this->source . ', ' . $this->body . ($this->empty !== null ? ', ' . $this->empty : '') . ')';
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

class IfBlock extends Block
{
	public function __construct ($branches, $fallback)
	{
		$this->branches = $branches;
		$this->fallback = $fallback;
	}

	public function __toString ()
	{
		$branches = array_map (function ($b) { return $b[0] . ' => ' . $b[1]; }, $this->branches);

		return 'if([' . implode (', ', $branches) . ']' . ($this->fallback !== null ? ', ' . $this->fallback : '') . ')';
	}

	public function generate (&$variables)
	{
		$output = new Output ();
		$first = true;

		foreach ($this->branches as $branch)
		{
			list ($condition, $body) = $branch;

			$output->append_code (($first ? 'if' : 'else if ') . '(' . $condition->generate ($variables) . '){');
			$output->append ($body->generate ($variables));
			$output->append_code ('}');

			$first = false;
		}

		if ($this->fallback !== null)
		{
			$output->append_code ('else{');
			$output->append ($this->fallback->generate ($variables));
			$output->append_code ('}');
		}

		return $output;
	}

	public function inject ($variables)
	{
		$branches = array ();

		foreach ($this->branches as $branch)
		{
			list ($condition, $body) = $branch;

			$body = $body->inject ($variables);
			$condition = $condition->inject ($variables);

			if (!$condition->evaluate ($result))
				$branches[] = array ($condition, $body);
			else if ($result)
				return $body;
		}

		$fallback = $this->fallback !== null ? $this->fallback->inject ($variables) : null;

		if (count ($branches) === 0)
			return $fallback !== null ? $fallback : new VoidBlock ();

		return new self ($branches, $fallback);
	}
}

class LetBlock extends Block
{
	public function __construct ($assignments, $body)
	{
		$this->assignments = $assignments;
		$this->body = $body;
	}

	public function __toString ()
	{
		$assignments = array_map (function ($a) { return $a[0] . ' = ' . $a[1]; }, $this->assignments);

		return 'let([' . implode (', ', $assignments) . '], ' . $this->body . ')';
	}

	public function generate (&$variables)
	{
		$output = new Output ();
		$output->append_code ('{', true);

		foreach ($this->assignments as $assignment)
		{
			list ($name, $value) = $assignment;

			$output->append_code ('$' . $name . '=' . $value->generate ($variables) . ';');
		}

		$output->append ($this->body->generate ($variables));
		$output->append_code ('}');

		return $output;
	}

	public function inject ($variables)
	{
		$assignments = array ();
		$requires = array ();

		foreach ($this->assignments as $assignment)
		{
			list ($name, $value) = $assignment;

			$value = $value->inject ($variables);

			if ($value->evaluate ($result))
				$variables[$name] = $result;
			else
				$assignments[] = array ($name, $value);
		}

		$body = $this->body->inject ($variables);
		$body->generate ($requires);

		if (count ($assignments) === 0 || count ($requires) === 0)
			return $body;

		return new self ($assignments, $body);
	}
}

class PlainBlock extends Block
{
	public function __construct ($text)
	{
		$this->text = $text;
	}

	public function __toString ()
	{
		return 'plain(' . $this->text . ')';
	}

	public function generate (&$variables)
	{
		return (new Output ())->append_text ($this->text);
	}

	public function inject ($variables)
	{
		return $this;
	}
}

class VoidBlock extends Block
{
	public function __toString ()
	{
		return 'void()';
	}

	public function generate (&$variables)
	{
		return new Output ();
	}

	public function inject ($variables)
	{
		return $this;
	}
}

?>
