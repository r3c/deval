<?php

namespace Deval;

class CompileException extends \Exception
{
	public function __construct ($message)
	{
		parent::__construct ('compile error: ' . $message);
	}
}

class ParseException extends \Exception
{
	public function __construct ($location, $message)
	{
		parent::__construct ('parse error in ' . $location->context . ' at line ' . $location->line . ' column ' . $location->column . ': ' . $message);

		$this->location = $location;
	}
}

class RenderException extends \Exception
{
	public function __construct ($message)
	{
		parent::__construct ('runtime error: ' . $message);
	}
}

class SetupException extends \Exception
{
	public function __construct ($message)
	{
		parent::__construct ('setup error: ' . $message);
	}
}

class Builtin
{
	public static function _array ()
	{
		$array = array ();

		foreach (func_get_args () as $value)
			$array = array_merge ($array, (array)$value);

		return $array;
	}

	public static function _bool ($value)
	{
		return (bool)$value;
	}

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

	public static function _filter ($items, $predicate = null)
	{
		if ($predicate === null)
			$predicate = function ($v) { return $v; };

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

	public static function _float ($value)
	{
		return (float)$value;
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

	public static function _int ($value)
	{
		return (int)$value;
	}

	public static function _join ($items, $separator = '')
	{
		return implode ($separator, $items);
	}

	public static function _keys ($items)
	{
		return array_keys ($items);
	}

	public static function _length ($value)
	{
		if ($value === null)
			return null;
		else if (is_array ($value))
			return count ($value);
		else
			return mb_strlen ((string)$value);
	}

	public static function _map ($items, $apply)
	{
		return array_map ($apply, $items);
	}

	public static function _max ($first)
	{
		if (is_array ($first) && count ($first) === 0)
			return null;

		return call_user_func_array ('max', func_get_args ());
	}

	public static function _min ($first)
	{
		if (is_array ($first) && count ($first) === 0)
			return null;

		return call_user_func_array ('min', func_get_args ());
	}

	public static function _php ($symbol)
	{
		if (!preg_match ('/^(([0-9A-Z\\\\_a-z]+)::)?([#$])?([0-9A-Z_a-z]+)$/', $symbol, $match))
			throw new RenderException ('invalid symbol "' . $symbol . '" passed to php() builtin');

		switch ($match[3])
		{
			case '#':
				return constant ($match[1] . $match[4]);

			case '$':
				$class = $match[2];
				$name = $match[4];

				if ($class === '')
					return isset ($GLOBALS[$name]) ? $GLOBALS[$name] : null;

				$vars = get_class_vars ($class);

				return isset ($vars[$name]) ? $vars[$name] : null;

			default:
				return $match[0];
		}
	}

	public static function _range ($start, $stop, $step = 1)
	{
		if ((int)$start !== (int)$stop && ($start < $stop) !== ($step > 0))
			return array ();

		return range ((int)$start, (int)$stop, (int)$step);
	}

	public static function _reduce ($items, $callback, $initial = null)
	{
		return array_reduce ($items, $callback, $initial);
	}

	public static function _replace ($value, $replacements)
	{
		return str_replace (array_keys ($replacements), array_values ($replacements), $value);
	}

	public static function _slice ($value, $offset, $count = null)
	{
		if ($value === null)
			return null;
		else if (is_array ($value))
		{
			if ($count !== null)
				return array_slice ($value, $offset, $count);

			return array_slice ($value, $offset);
		}
		else
		{
			if ($count !== null)
				return mb_substr ((string)$value, $offset, $count);

			return mb_substr ((string)$value, $offset);
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

	public static function _split ($string, $separator, $limit = PHP_INT_MAX)
	{
		return explode ($separator, $string, $limit);
	}

	public static function _str ($value)
	{
		return (string)$value;
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
			'array',
			'bool',
			'cat',
			'default',
			'filter',
			'find',
			'flip',
			'float',
			'group',
			'int',
			'join',
			'keys',
			'length',
			'map',
			'max',
			'min',
			'php',
			'range',
			'reduce',
			'replace',
			'slice',
			'sort',
			'split',
			'str',
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
		require $path . '/blocks/unwrap.php';
		require $path . '/blocks/void.php';
		require $path . '/compiler.php';
		require $path . '/expression.php';
		require $path . '/expressions/array.php';
		require $path . '/expressions/binary.php';
		require $path . '/expressions/constant.php';
		require $path . '/expressions/defer.php';
		require $path . '/expressions/group.php';
		require $path . '/expressions/invoke.php';
		require $path . '/expressions/lambda.php';
		require $path . '/expressions/member.php';
		require $path . '/expressions/symbol.php';
		require $path . '/expressions/unary.php';
		require $path . '/generator.php';
		require $path . '/location.php';
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

class CacheRenderer implements Renderer
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

		if (!file_exists ($cache) || filemtime ($cache) < filemtime ($this->path) || $this->invalidate)
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

class Setup
{
	public $style = 'deindent';
	public $version = PHP_VERSION;
}

/*
** Membership function used to access member by key from parent instance.
** $parent:	parent array or object
** $key:	member key
** return:	member value or null if not found
*/
function m ($parent, $key)
{
	if (is_object ($parent))
	{
		if (is_callable (array ($parent, $key)))
			return array ($parent, $key);
		else if (property_exists ($parent, $key))
			return $parent->$key;
		else if ($parent instanceof \ArrayAccess)
			return $parent[$key];
	}
	else if (isset ($parent[$key]))
		return $parent[$key];

	return null;
}

/*
** Deval run method, assert provided symbols match required ones and throw
** exception otherwise.
** $required:	required symbols list
** $provided:	provided symbols map
*/
function r ($required, &$provided)
{
	$undefined = array_diff ($required, array_keys ($provided));

	if (count ($undefined) > 0)
		throw new RenderException ('undefined symbol(s) ' . implode (', ', $undefined));
}

?>
