<?php

namespace Deval;

class CompileException extends \Exception
{
	public function __construct ($value, $message)
	{
		parent::__construct ('compile error: "' . var_export ($value, true) . '" ' . $message);
	}
}

class ParseException extends \Exception
{
	public function __construct ($context, $message)
	{
		parent::__construct ('parse error: ' . $message . ' in "' . $context . '"');
	}
}

class RenderException extends \Exception
{
	public function __construct ($message)
	{
		parent::__construct ('runtime error: ' . $message);
	}
}

class Builtin
{
	public static function _cat ()
	{
		$args = func_get_args ();

		if (count ($args) < 1)
			return null;
		else if (is_array ($args[0]))
			return call_user_func_array ('array_merge', $args);

		$buffer = '';

		foreach ($args as $arg)
			$buffer .= (string)$arg;

		return $buffer;
	}

	public static function _default ($value, $fallback)
	{
		return $value !== null ? $value : $fallback;
	}

	public static function _filter ($items, $predicate)
	{
		return array_filter ($items, $predicate, ARRAY_FILTER_USE_BOTH);
	}

	public static function _find ($items, $predicate = null)
	{
		if ($predicate === null)
			return each ($items);

		foreach ($items as $key => $value)
		{
			if ($predicate ($value, $key))
				return array ($key, $value);
		}

		return null;
	}

	public static function _flip ($items)
	{
		return array_flip ($items);
	}

	public static function _group ($items, $get_key = null, $get_value = null, $merge = null)
	{
		if ($get_key === null)
			$get_key = function ($v) { return $v; };

		if ($get_value === null)
			$get_value = function ($v) { return $v; };

		if ($merge === null)
			$merge = function ($v) { return $v; };

		$groups = array ();

		foreach ($items as $key => $value)
		{
			$k = $get_key ($value, $key);
			$v = $get_value ($value, $key);

			if (isset ($groups[$k]))
				$groups[$k] = $merge ($groups[$k], $v);
			else
				$groups[$k] = $v;
		}

		return $groups;
	}

	public static function _join ($items, $separator = '')
	{
		return implode ($separator, $items);
	}

	public static function _keys ($items)
	{
		return array_keys ($items);
	}

	public static function _length ($input)
	{
		if ($input === null)
			return null;
		else if (is_array ($input))
			return count ($input);
		else
			return strlen ((string)$input);
	}

	public static function _map ($items, $apply)
	{
		return array_map ($apply, $items);
	}

	public static function _php ($symbol)
	{
		switch (substr ($symbol, 0, 1))
		{
			case '#':
				return constant ((string)substr ($symbol, 1));

			case '$':
				$parts = explode ('::', (string)substr ($symbol, 1), 2);

				if (count ($parts) < 2)
					return isset ($GLOBALS[$parts[0]]) ? $GLOBALS[$parts[0]] : null;

				$vars = get_class_vars ($parts[0]);

				return isset ($vars[$parts[1]]) ? $vars[$parts[1]] : null;

			default:
				return $symbol;
		}
	}

	public static function _slice ($input, $offset, $count = null)
	{
		if ($input === null)
			return null;
		else if (is_array ($input))
		{
			if ($count !== null)
				return array_slice ($input, $offset, $count);

			return array_slice ($input, $offset);
		}
		else
		{
			if ($count !== null)
				return substr ((string)$input, $offset, $count);

			return substr ((string)$input, $offset);
		}
	}

	public static function _sort ($items, $compare = null)
	{
		if ($compare !== null)
			uasort ($items, $compare);
		else
			asort ($items);

		return $items;
	}

	public static function _split ($string, $separator, $limit = null)
	{
		if ($limit !== null)
			return explode ($separator, $string, $limit);

		return explode ($separator, $string);
	}

	public static function _values ($items)
	{
		return array_values ($items);
	}

	public static function _void ()
	{
		return null;
	}

	public static function _zip ($keys, $values)
	{
		return array_combine ($keys, $values);
	}

	public static function deval ()
	{
		$class = '\\' . get_class ();
		$names = array
		(
			'cat',
			'default',
			'filter',
			'find',
			'flip',
			'group',
			'join',
			'keys',
			'length',
			'map',
			'php',
			'slice',
			'sort',
			'split',
			'values',
			'void',
			'zip'
		);

		return array_combine ($names, array_map (function ($name) use ($class)
		{
			return array ($class, '_' . $name);
		}, $names));
	}

	public static function php ()
	{
		$functions = get_defined_functions ();

		return array_combine ($functions['internal'], $functions['internal']);
	}
}

class Evaluator
{
	public static function code ($_d_code, $_d_input)
	{
		ob_start ();

		try
		{
			eval ('?>' . $_d_code);
		}
		catch (\Exception $exception) // Replace by "finally" once PHP < 5.5 compatibility can be dropped
		{
			ob_end_clean ();

			throw $exception;
		}

		return ob_get_clean ();
	}

	public static function path ($_d_path, $_d_input)
	{
		ob_start ();

		try
		{
			require $_d_path;
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
		require $path . '/expressions/group.php';
		require $path . '/expressions/invoke.php';
		require $path . '/expressions/lambda.php';
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
	public function render ($variables = array ());
	public function source (&$names = null);
}

class CachingRenderer implements Renderer
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
		$this->setup = $setup ?: new Setup ();
	}

	public function inject ($constants)
	{
		$this->constants += $constants;
	}

	public function render ($variables = array ())
	{
		$cache = $this->directory . DIRECTORY_SEPARATOR . pathinfo (basename ($this->path), PATHINFO_FILENAME) . '_' . md5 ($this->path . ':' . serialize ($this->constants)) . '.php';

		if (!file_exists ($cache) || $this->invalidate)
			file_put_contents ($cache, $this->source ($names), LOCK_EX);

		return Evaluator::path ($cache, $variables);
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

	public function render ($variables = array ())
	{
		return Evaluator::code ($this->source ($names), $variables);
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

		parent::__construct (new Compiler (Compiler::parse_file ($path)), $setup ?: new Setup ());
	}
}

class StringRenderer extends DirectRenderer
{
	public function __construct ($source, $setup = null)
	{
		Loader::load ();

		parent::__construct (new Compiler (Compiler::parse_code ($source)), $setup ?: new Setup ());
	}
}

class Runtime
{
	public static function member ($parent, $key)
	{
		if (is_object ($parent))
		{
			if (method_exists ($parent, $key))
				return array ($parent, $key);
			else if (property_exists ($parent, $key))
				return $parent->$key;
		}

		if (isset ($parent[$key]))
			return $parent[$key];

		return null;
	}

	private $scopes = array ();

	public function __construct ($required, &$provided)
	{
		$undefined = array_diff ($required, array_keys ($provided));

		if (count ($undefined) > 0)
			throw new RenderException ('undefined symbol(s) ' . implode (', ', $undefined));
	}

	public function scope_pop ()
	{
		return array_pop ($this->scopes);
	}

	public function scope_push ()
	{
		$this->scopes[] = func_get_args ();
	}
}

class Setup
{
	public $style = 'deindent';
	public $version = PHP_VERSION;
}

?>
