<?php

namespace Deval;

class Compiler
{
	private $root;

	public static function assert_symbol ($name)
	{
		if (!preg_match ('/^[_A-Za-z][_0-9A-Za-z]*$/', $name))
			throw new \Exception ('invalid symbol name "' . $name . '"');
	}

	public function __construct ($source)
	{
		static $setup;

		if (!isset ($setup))
		{
			$path = dirname (__FILE__);

			require $path . '/block.php';
			require $path . '/blocks/concat.php';
			require $path . '/blocks/echo.php';
			require $path . '/blocks/for.php';
			require $path . '/blocks/if.php';
			require $path . '/blocks/let.php';
			require $path . '/blocks/plain.php';
			require $path . '/blocks/void.php';
			require $path . '/expression.php';
			require $path . '/expressions/array.php';
			require $path . '/expressions/binary.php';
			require $path . '/expressions/constant.php';
			require $path . '/expressions/invoke.php';
			require $path . '/expressions/member.php';
			require $path . '/expressions/symbol.php';
			require $path . '/expressions/unary.php';
			require $path . '/parser.php';

			$setup = true;
		}

		$parser = new \PhpPegJs\Parser ();

		$this->root = $parser->parse ($source);
	}

	public function compile (&$requires)
	{
		$variables = array ();

		$output = new Output ();
		$output->append_code (State::emit_create () . ';');
		$output->append ($this->root->compile ($variables));
		$requires = array_keys ($variables);

		return $output->source ();
	}

	public function execute ($variables = array ())
	{
		$requires = array ();
		$source = $this->compile ($requires);

		$names = array_diff ($requires, array_keys ($variables));

		if (count ($names) > 0)
			throw new \Exception ('missing variables for execution: ' . implode (', ' . $names));

		extract ($variables);
		eval ('?>' . $source);
	}

	public function inject ($variables)
	{
		$this->root = $this->root->inject ($variables);
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
