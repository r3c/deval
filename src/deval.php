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
		require $path . '/output.php';
		require $path . '/parser.php';

		$setup = true;
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
	private $setup;

	public function __construct ($path, $directory, $setup = null, $invalidate = false)
	{
		$this->constants = array ();
		$this->directory = $directory;
		$this->invalidate = $invalidate;
		$this->path = $path;
		$this->setup = $setup;
	}

	public function inject ($constants)
	{
		$this->constants += $constants;
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
		$compiler->inject ($this->constants);

		return $compiler->compile ($this->setup, $names);
	}
}

class DirectRenderer implements Renderer
{
	private $compiler;
	private $setup;

	protected function __construct ($compiler, $setup)
	{
		$this->compiler = $compiler;
		$this->setup = $setup;
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
		return $this->compiler->compile ($this->setup, $names);
	}
}

class FileRenderer extends DirectRenderer
{
	public function __construct ($path, $setup = null)
	{
		Loader::load ();

		parent::__construct (new Compiler (Compiler::parse_file ($path)), $setup);
	}
}

class StringRenderer extends DirectRenderer
{
	public function __construct ($source, $setup = null)
	{
		Loader::load ();

		parent::__construct (new Compiler (Compiler::parse_code ($source)), $setup);
	}
}

class Setup
{
	public $style = null;
	public $version = PHP_VERSION;
}

function member ($source, $index)
{
	$array = (array)$source;

	if (isset ($array[$index]))
		return $array[$index];

	return null;
}

function run ($required, &$provided)
{
	$undefined = array_diff ($required, array_keys ($provided));

	if (count ($undefined) > 0)
		throw new RuntimeException ('undefined symbol(s) ' . implode (', ', $undefined));
}

?>
