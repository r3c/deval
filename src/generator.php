<?php

namespace Deval;

class Generator
{
	private static $input_name = '_deval_input';
	private static $state_name = '_deval_state';

	public static function emit_member ($source, $index)
	{
		return '\\' . __NAMESPACE__ . '\\Runtime::member(' . $source . ',' . $index . ')';
	}

	public static function emit_scope_pop ($names)
	{
		return 'list(' . implode (',', array_map (function ($name)
		{
			return Generator::emit_symbol ($name);
		}, $names)) . ')=$' . self::$state_name . '->scope_pop();';
	}

	public static function emit_scope_push ($names)
	{
		return '$' . self::$state_name . '->scope_push(' . implode (',', array_map (function ($name)
		{
			$symbol = Generator::emit_symbol ($name);

			return 'isset(' . $symbol . ')?' . $symbol . ':null';
		}, $names)) . ');';
	}

	public static function emit_state ($names)
	{
		return
			'$' . self::$state_name . '=new\\' . __NAMESPACE__ . '\\Runtime(' . self::emit_value ($names) . ',$' . self::$input_name . ');' .
			'\\extract($' . self::$input_name . ');';
	}

	public static function emit_symbol ($name)
	{
		if (!preg_match ('/^[_A-Za-z][_0-9A-Za-z]*$/', $name) || $name === self::$input_name || $name === self::$state_name)
			throw new RenderException ('invalid symbol name ' . $name);

		return '$' . $name;
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
					'collapse'	=> function ($s) { return preg_replace ('/\\s+/mu', ' ', $s); },
					'deindent'	=> function ($s) { return preg_replace ("/^(?:\n|\r|\n\r|\r\n)[\t ]*|(?:\n|\r|\n\r|\r\n)[\t ]*$/u", '', $s); },
					'preserve'	=> function ($s) { return $s; }
				);
			}

			if (is_string ($setup->style))
			{
				$trimmers = array ();

				foreach (explode (',', $setup->style) as $style)
				{
					if (!isset ($trims[$style]))
						throw new CompileException ('<setup>', 'unknown style "' . $style . '"');

					$trimmers[] = $trims[$style];
				}

				if (count ($trimmers) === 1)
					$this->trimmer = $trimmers[0];
				else
				{
					$this->trimmer = function ($s) use ($trimmers)
					{
						foreach ($trimmers as $trimmer)
							$s = $trimmer ($s);

						return $s;
					};
				}
			}
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

	public function emit_local ()
	{
		return '$' . self::$state_name . '->_' . $this->local++;
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
