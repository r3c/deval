<?php

namespace Deval;

class Generator
{
	private static $input_name = '_deval_input';
	private static $local_name = '_deval_local';

	public static function assert_symbol ($name)
	{
		if (!preg_match ('/^[_A-Za-z][_0-9A-Za-z]*$/', $name))
			throw new \Exception ('invalid symbol name');
	}

	public static function emit_create ($names)
	{
		return
			'\\' . __NAMESPACE__ . '\\run(' . self::emit_value ($names) . ',$' . self::$input_name . ');' .
			'\\extract($' . self::$input_name . ');';
	}

	public static function emit_member ($source, $index)
	{
		return '\\' . __NAMESPACE__ . '\\member(' . $source . ',' . $index . ')';
	}

	public static function emit_value ($input)
	{
		if (is_array ($input))
		{
			$source = '';

			if (array_reduce (array_keys ($input), function (&$result, $item) { return $result === $item ? $item + 1 : null; }, 0) !== count ($input))
			{
				foreach ($input as $key => $value)
					$source .= ($source !== '' ? ',' : '') . self::emit_value ($key) . '=>' . self::emit_value ($value);
			}
			else
			{
				foreach ($input as $value)
					$source .= ($source !== '' ? ',' : '') . self::emit_value ($value);
			}

			return 'array(' . $source . ')';
		}

		return var_export ($input, true);
	}

	public static function dummy ()
	{
		static $instance;

		if (!isset ($instance))
			$instance = new self (null);

		return $instance;
	}

	private $local;
	private $trimmer;
	private $version;

	public function __construct ($setup)
	{
		static $trims;

		if ($setup !== null)
		{
			if (!isset ($trims))
			{
				$trims = array
				(
					'collapse'	=> function ($s) { return preg_replace ('/\\s+/m', ' ', $s); },
					'deindent'	=> function ($s) { return preg_replace ("/^(?:\n|\r|\n\r|\r\n)[\t ]*|(?:\n|\r|\n\r|\r\n)[\t ]*$/", '', $s); },
					'html'		=> function ($s) { return preg_replace (array ('/(^|>)\\s+/m', '/\\s+(<|$)/m'), array ('$1 ', ' $1'), $s); },
					'preserve'	=> function ($s) { return $s; }
				);
			}

			if (is_string ($setup->style) && isset ($trims[$setup->style]))
				$this->trimmer = $trims[$setup->style];
			else if (is_callable ($setup->style))
				$this->trimmer = $setup->style;
			else
				throw new CompileException ('<setup>', 'invalid style, must be either builtin style or callable');

			$this->version = $setup->version;
		}
		else
		{
			$this->trimmer = function ($s) { return $s; };
			$this->version = PHP_VERSION;
		}

		$this->local = 0;
	}

	public function make_local ()
	{
		return self::$local_name . $this->local++;
	}

	public function make_plain ($text)
	{
		$trim = $this->trimmer;

		return $trim ($text);
	}

	public function support ($required)
	{
		return version_compare ($this->version, $required) >= 0;
	}
}

?>
