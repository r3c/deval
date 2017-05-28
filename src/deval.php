<?php

namespace Deval;

class CompileException extends \Exception
{
	public function __construct ($context, $message)
	{
		parent::__construct ('compile error: ' . $message . ' in "' . $context . '"');
	}
}

class InjectException extends \Exception
{
	public function __construct ($expression, $message)
	{
		parent::__construct ('inject error: "' . $expression . '" ' . $message);
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
	public static function deval ()
	{
		return array
		(
			'php'	=> function ($name) { return $name; }
		);
	}

	public static function php ()
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
		require $path . '/generator.php';
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
	public function render ($volatiles = array ());
	public function source (&$names = null);
}

class CachedRenderer implements Renderer
{
	private $constants;
	private $directory;
	private $invalidate;
	private $path;
	private $style;

	public function __construct ($path, $directory, $style = null, $invalidate = false)
	{
		$this->constants = null;
		$this->directory = $directory;
		$this->invalidate = $invalidate;
		$this->path = $path;
		$this->style = $style;
	}

	public function inject ($constants)
	{
		$this->constants = $constants;
	}

	public function render ($volatiles = array ())
	{
		$cache = $this->directory . DIRECTORY_SEPARATOR . pathinfo (basename ($this->path), PATHINFO_FILENAME) . '_' . md5 ($this->path . ':' . serialize ($this->constants)) . '.php';

		if (!file_exists ($cache) || $this->invalidate)
			file_put_contents ($cache, $this->source ($names));

		return Evaluator::path ($cache, $volatiles);
	}

	public function source (&$names = null)
	{
		Loader::load ();

		$compiler = new Compiler (Compiler::parse_file ($this->path));

		if ($this->constants !== null)
			$compiler->inject ($this->constants);

		return $compiler->compile ($this->style, $names);
	}
}

class DirectRenderer implements Renderer
{
	private $compiler;
	private $style;

	protected function __construct ($compiler, $style)
	{
		$this->compiler = $compiler;
		$this->style = $style;
	}

	public function inject ($constants)
	{
		$this->compiler->inject ($constants);
	}

	public function render ($volatiles = array ())
	{
		return Evaluator::code ($this->source ($names), $volatiles);
	}

	public function source (&$names = null)
	{
		return $this->compiler->compile ($this->style, $names);
	}
}

class FileRenderer extends DirectRenderer
{
	public function __construct ($path, $style = null)
	{
		Loader::load ();

		parent::__construct (new Compiler (Compiler::parse_file ($path)), $style);
	}
}

class StringRenderer extends DirectRenderer
{
	public function __construct ($source, $style = null)
	{
		Loader::load ();

		parent::__construct (new Compiler (Compiler::parse_code ($source)), $style);
	}
}

class State
{
	public function __construct ($required, &$provided)
	{
		$undefined = array_diff ($required, array_keys ($provided));

		if (count ($undefined) > 0)
			throw new RuntimeException ('undefined symbol(s) ' . implode (', ', $undefined));
	}
}

?>
