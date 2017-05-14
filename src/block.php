<?php

namespace Deval;

abstract class Block
{
	abstract function __toString ();
	abstract function inject ($variables);
	abstract function render (&$variables);
}

class ConcatBlock extends Block
{
	public static function create ($blocks)
	{
		switch (count ($blocks))
		{
			case 0:
				return new VoidBlock ();

			case 1:
				return $blocks[0];

			default:
				return new self ($blocks);
		}
	}

	public function __construct ($blocks)
	{
		$this->blocks = $blocks;
	}

	public function __toString ()
	{
		return 'concat(' . implode (', ', array_map (function ($block) { return (string)$block; }, $this->blocks)) . ')';
	}

	public function inject ($variables)
	{
		return new self (array_map (function ($block) use (&$variables)
		{
			return $block->inject ($variables);
		}, $this->blocks));
	}

	public function render (&$variables)
	{
		$render = $this->blocks[0]->render ($variables);

		for ($i = 1; $i < count ($this->blocks); ++$i)
			$render->append ($this->blocks[$i]->render ($variables));

		return $render;
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

	public function inject ($variables)
	{
		$value = $this->value->inject ($variables);

		if ($value->evaluate ($result))
			return new PlainBlock ((string)$result);

		return new self ($value);
	}

	public function render (&$variables)
	{
		return (new Output ())->append_code ('echo ' . $this->value->generate ($variables) . ';');
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

	public function inject ($variables)
	{
		$body = $this->body->inject ($variables);
		$empty = $this->empty !== null ? $this->empty->inject ($variables) : null;
		$source = $this->source->inject ($variables);

		if (!$source->evaluate ($result))
			return new self ($source, $this->key, $this->value, $body, $empty);

		$blocks = array ();

		foreach ($result as $key => $value)
			$blocks[] = $body->inject (array ($this->key => $key, $this->value => $value));

		return ConcatBlock::create ($blocks);
	}

	public function render (&$variables)
	{
		$output = new Output ();

		// Write loop control
		$output->append_code (State::emit_loop_start() . ';');
		$output->append_code ('for(' . $this->source->generate ($variables) . ' as ');

		if ($this->key !== null)
			$output->append_code ('$' . $this->key . '=>$' . $this->value);
		else
			$output->append_code ('$' . $this->value);

		$output->append_code (')');

		// Write body and merge inner variables into parent
		$variables_inner = array ();

		$output->append_code ('{');
		$output->append ($this->body->render ($variables_inner));
		$output->append_code (State::emit_loop_step() . ';');
		$output->append_code ('}');

		if ($this->key !== null)
			unset ($variables_inner[$this->key]);

		unset ($variables_inner[$this->value]);

		foreach (array_keys ($variables_inner) as $name)
			$variables[$name] = true;

		// Write empty block if any
		if ($this->empty !== null)
		{
			$output->append_code ('if(' . State::emit_loop_stop() . ')');
			$output->append_code ('{');
			$output->append ($this->empty->render ($variables));
			$output->append_code ('}');
		}
		else
			$output->append_code (State::emit_loop_stop() . ';');

		return $output;
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

	public function render (&$variables)
	{
		$output = new Output ();
		$first = true;

		foreach ($this->branches as $branch)
		{
			list ($condition, $body) = $branch;

			$output->append_code (($first ? 'if' : 'else if ') . '(' . $condition->generate ($variables) . ')');
			$output->append_code ('{');
			$output->append ($body->render ($variables));
			$output->append_code ('}');

			$first = false;
		}

		if ($this->fallback !== null)
		{
			$output->append_code ('else');
			$output->append_code ('{');
			$output->append ($this->fallback->render ($variables));
			$output->append_code ('}');
		}

		return $output;
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
		$body->render ($requires);

		if (count ($assignments) === 0 || count ($requires) === 0)
			return $body;

		return new self ($assignments, $body);
	}

	public function render (&$variables)
	{
		$output = new Output ();
		$output->append_code ('{', true);

		$variables_excludes = array ();

		foreach ($this->assignments as $assignment)
		{
			list ($name, $value) = $assignment;

			$output->append_code ('$' . $name . '=' . $value->generate ($variables) . ';');

			$variables_exclude[$name] = true;
		}

		$variables_inner = array ();

		$output->append ($this->body->render ($variables_inner));

		foreach (array_keys (array_diff_key ($variables_inner, $variables_exclude)) as $name)
			$variables[$name] = true;

		$output->append_code ('}');

		return $output;
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

	public function inject ($variables)
	{
		return $this;
	}

	public function render (&$variables)
	{
		return (new Output ())->append_text ($this->text);
	}
}

class VoidBlock extends Block
{
	public function __toString ()
	{
		return 'void()';
	}

	public function inject ($variables)
	{
		return $this;
	}

	public function render (&$variables)
	{
		return new Output ();
	}
}

?>
