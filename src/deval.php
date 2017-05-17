<?php

namespace Deval;

class Document
{
	private $root;

	public function __construct ($source)
	{
		static $setup;

		if (!isset ($setup))
		{
			$path = dirname (__FILE__);

			require $path . '/generated/parser.php';
			require $path . '/block.php';
			require $path . '/expression.php';

			$setup = true;
		}

		$parser = new \PhpPegJs\Parser ();

		$this->root = $parser->parse ($source);
	}

	public function __toString ()
	{
		return (string)$this->root;
	}

	public function inject ($variables)
	{
		$this->root = $this->root->inject ($variables);
	}

	public function render (&$requires)
	{
		$variables = array ();

		$output = new Output ();
		$output->append_code (State::emit_create () . ';');
		$output->append ($this->root->render ($variables));
		$requires = array_keys ($variables);

		return $output->source ();
	}
}

class Output
{
	private $snippets = array ();

	public function append ($other)
	{
		if (count ($other->snippets) === 0)
			return;

		$count = count ($this->snippets);
		$last = $count > 0 && $this->snippets[$count - 1][1];

		foreach ($other->snippets as $snippet)
		{
			list ($output, $is_code) = $snippet;

			if ($count > 0 && $is_code === $last)
				$this->snippets[$count - 1][0] .= $output;
			else
			{
				$this->snippets[] = $snippet;

				$count++;
				$last = $is_code;
			}
		}

		return $this;
	}

	public function append_code ($code)
	{
		$other = new self ();
		$other->snippets[] = array ($code, true);

		return $this->append ($other);
	}

	public function append_text ($text)
	{
		$other = new self ();
		$other->snippets[] = array ($text, false);

		return $this->append ($other);
	}

	public function source ()
	{
		$output = '';
		$is_code = false;

		foreach ($this->snippets as $snippet)
		{
			list ($block_output, $block_is_code) = $snippet;

			if ($block_is_code === $is_code)
				$output .= $block_output;
			else if ($is_code)
				$output .= ' ?>' . $block_output . '<?php ';
			else
				$output .= '<?php ' . $block_output . ' ?>';
		}

		return $output;
	}
}

class State
{
	private static $name = '_deval';

	private $loops = array ();

	public static function emit_create ()
	{
		return '$' . self::$name . '=new \\' . get_class () . '()';
	}

	public static function emit_loop_start ()
	{
		return '$' . self::$name . '->loop_start()';
	}

	public static function emit_loop_step ()
	{
		return '$' . self::$name . '->loop_step()';
	}

	public static function emit_loop_stop ()
	{
		return '$' . self::$name . '->loop_stop()';
	}

	public static function emit_member ($arguments)
	{
		return '\\' . get_class () . '::member(' . implode (',', $arguments) . ')';
	}

	public static function member (&$source, $indices)
	{
		foreach ($indices as $index)
		{
			$array = (array)$source;

			if (isset ($array[$index]))
				$source =& $array[$index];
			else
			{
				unset ($source);

				break;
			}
		}

		if (isset ($source))
			return $source;

		return null;
	}

	public function loop_start ()
	{
		$this->loops[] = 0;
	}

	public function loop_step ()
	{
		++$this->loops[count ($this->loops) - 1];
	}

	public function loop_stop ()
	{
		return array_pop ($this->loops);
	}
}

?>
