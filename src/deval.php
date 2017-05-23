<?php

namespace Deval;

class Compiler
{
	private static $bases = array ();

	public static function parse_code ($source)
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

		return $parser->parse ($source);
	}

	public static function parse_file ($path)
	{
		$base = count (self::$bases) > 0 ? self::$bases[count (self::$bases) - 1] : '.';
		$path = strlen ($path) > 0 && $path[0] === DIRECTORY_SEPARATOR ? $path : $base . DIRECTORY_SEPARATOR . $path;

		if (!file_exists ($path))
			throw new \Exception ('cannot include missing file "' . $path . '"');

		array_push (self::$bases, dirname ($path));

		try
		{
			$block = self::parse_code (file_get_contents ($path));
		}
		catch (\Exception $exception)
		{
			array_pop (self::$bases);

			throw $exception;
		}

		array_pop (self::$bases);

		return $block;
	}

	private $root;

	public function __construct ($root)
	{
		$this->root = $root;
	}

	public function compile (&$names = null)
	{
		$variables = array ();
		$source = $this->root->compile ($variables);
		$names = array_keys ($variables);

		$output = new Output ();
		$output->append_code (State::emit_create ($names));
		$output->append ($source);

		return $output->source ();
	}

	public function inject ($variables)
	{
		$this->root = $this->root->inject ($variables);
	}
}

class Evaluator
{
	public static function code ($_deval_code, $_deval_input)
	{
		ob_start ();

		try
		{
			eval ('?>' . $_deval_code);
		}
		catch (\Exception $exception)
		{
			ob_end_clean ();

			throw $exception;
		}

		return ob_get_clean ();
	}

	public static function path ($_deval_path, $_deval_input)
	{
		ob_start ();

		try
		{
			require $_deval_path;
		}
		catch (\Exception $exception)
		{
			ob_end_clean ();

			throw $exception;
		}

		return ob_get_clean ();
	}
}

class CacheRenderer
{
	public function __construct ($directory, $variables)
	{
		$this->directory = $directory;
		$this->variables = $variables;
	}

	public function render ($path, $variables)
	{
		$cache = $this->directory . DIRECTORY_SEPARATOR . pathinfo (basename ($path), PATHINFO_FILENAME) . '_' . md5 ($path . ':' . serialize ($this->variables)) . '.php';

		if (!file_exists ($cache))
		{
			$compiler = new Compiler (Compiler::parse_file ($path));
			$compiler->inject ($this->variables);

			file_put_contents ($cache, $compiler->compile ());
		}

		Evaluator::path ($cache, $variables);
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
	private static $input_name = '_deval_input';
	private static $state_name = '_deval_state';

	private $loops = array ();

	public static function assert_symbol ($name)
	{
		if (!preg_match ('/^[_A-Za-z][_0-9A-Za-z]*$/', $name))
			throw new \Exception ('invalid symbol name "' . $name . '"');
	}

	public static function emit_create ($keys)
	{
		return
			'$' . self::$state_name . '=new \\' . get_class () . '(array_diff(' . self::export ($keys) . ',array_keys($' . self::$input_name . ')));' .
			'extract($' . self::$input_name . ');';
	}

	public static function emit_loop_start ()
	{
		return '$' . self::$state_name . '->loop_start()';
	}

	public static function emit_loop_step ()
	{
		return '$' . self::$state_name . '->loop_step()';
	}

	public static function emit_loop_stop ()
	{
		return '$' . self::$state_name . '->loop_stop()';
	}

	public static function emit_member ($arguments)
	{
		return '\\' . get_class () . '::member(' . implode (',', $arguments) . ')';
	}

	public static function export ($input)
	{
		if (is_array ($input))
		{
			$out = '';

			if (array_reduce (array_keys ($input), function (&$result, $item) { return $result === $item ? $item + 1 : null; }, 0) !== count ($input))
			{
				foreach ($input as $key => $value)
					$out .= ($out !== '' ? ',' : '') . self::export ($key) . '=>' . self::export ($value);
			}
			else
			{
				foreach ($input as $value)
					$out .= ($out !== '' ? ',' : '') . self::export ($value);
			}

			return 'array(' . $out . ')';
		}

		return var_export ($input, true);
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

	public function __construct ($keys)
	{
		if (count ($keys) > 0)
			throw new \Exception ('undefined runtime variables: ' . implode (', ', $keys));
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
