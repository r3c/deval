<?php

namespace Deval;

class CompileException extends \Exception
{
	public function __construct ($context, $message)
	{
		parent::__construct ('compile error in ' . $context . ': ' . $message);
	}
}

class RuntimeException extends \Exception
{
	public function __construct ($message)
	{
		parent::__construct ('runtime error: ' . $message);
	}
}

class Builtin
{
	public static function internal ()
	{
		$functions = get_defined_functions ();

		return array_combine ($functions['internal'], $functions['internal']);
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
		catch (\Exception $exception) // Replace by "finally" once PHP < 5.5 compatibility can be dropped
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
		catch (\Exception $exception) // Replace by "finally" once PHP < 5.5 compatibility can be dropped
		{
			ob_end_clean ();

			throw $exception;
		}

		return ob_get_clean ();
	}
}

class Loader
{
	public static function load ()
	{
		static $setup;

		if (isset ($setup))
			return;

		$path = dirname (__FILE__);

		require $path . '/block.php';
		require $path . '/blocks/concat.php';
		require $path . '/blocks/echo.php';
		require $path . '/blocks/for.php';
		require $path . '/blocks/if.php';
		require $path . '/blocks/label.php';
		require $path . '/blocks/let.php';
		require $path . '/blocks/plain.php';
		require $path . '/blocks/void.php';
		require $path . '/compiler.php';
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

interface Renderer
{
	public function inject ($constants);
	public function render ($volatiles = array (), $style = null);
}

class CachedRenderer implements Renderer
{
	private $constants;
	private $directory;
	private $invalidate;
	private $path;

	public function __construct ($path, $directory, $invalidate = false)
	{
		$this->constants = null;
		$this->directory = $directory;
		$this->invalidate = $invalidate;
		$this->path = $path;
	}

	public function inject ($constants)
	{
		$this->constants = $constants;
	}

	public function render ($volatiles = array (), $style = null)
	{
		$cache = $this->directory . DIRECTORY_SEPARATOR . pathinfo (basename ($this->path), PATHINFO_FILENAME) . '_' . md5 ($this->path . ':' . serialize ($this->constants)) . '.php';

		if (!file_exists ($cache) || $this->invalidate)
		{
			Loader::load ();

			$compiler = new Compiler (Compiler::parse_file ($this->path));

			if ($this->constants !== null)
				$compiler->inject ($this->constants);

			file_put_contents ($cache, $compiler->compile ($style));
		}

		return Evaluator::path ($cache, $volatiles);
	}
}

class DirectRenderer implements Renderer
{
	private $compiler;

	protected function __construct ($compiler)
	{
		$this->compiler = $compiler;
	}

	public function inject ($constants)
	{
		$this->compiler->inject ($constants);
	}

	public function render ($volatiles = array (), $style = null)
	{
		return Evaluator::code ($this->compiler->compile ($style), $volatiles);
	}

	public function source ($style = null)
	{
		return $this->compiler->compile ($style);
	}
}

class FileRenderer extends DirectRenderer
{
	public function __construct ($path)
	{
		Loader::load ();

		parent::__construct (new Compiler (Compiler::parse_file ($path)));
	}
}

class StringRenderer extends DirectRenderer
{
	public function __construct ($source)
	{
		Loader::load ();

		parent::__construct (new Compiler (Compiler::parse_code ($source)));
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
			throw new \Exception ('invalid symbol name');
	}

	public static function emit_create ($names)
	{
		return
			'$' . self::$state_name . '=new \\' . get_class () . '(' . self::export ($names) . ',$' . self::$input_name . ');' .
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

	public static function member ($source, $indices)
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

	public function __construct ($required, &$provided)
	{
		$undefined = array_diff ($required, array_keys ($provided));

		if (count ($undefined) > 0)
			throw new RuntimeException ('undefined symbol(s) ' . implode (', ', $undefined));
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
