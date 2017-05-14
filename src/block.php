<?php

namespace Deval;

abstract class Block
{
	abstract function __toString ();
}

class BufferBlock extends Block
{
	public function __construct ($name, $body)
	{
		$this->body = $body;
		$this->name = $name;
	}

	public function __toString ()
	{
		return 'buffer(' . $this->name . ', ' . $this->body . ')';
	}
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
}

?>
