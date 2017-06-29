<?php

namespace Deval;

class Generator
{
	private static $input_name = '_d_input';
	private static $local_name = '_d_local';
	private static $state_name = '_d_state';

	public static function emit_local ()
	{
		return '$' . self::$local_name;
	}

	public static function emit_member ($source, $index)
	{
		return '\\' . __NAMESPACE__ . '\\Runtime::member(' . $source . ',' . $index . ')';
	}

	public static function emit_scope_pop ($names)
	{
		if (count ($names) < 1)
			return '';

		return 'list(' . implode (',', array_map (function ($name)
		{
			return Generator::emit_symbol ($name);
		}, $names)) . ')=$' . self::$state_name . '->scope_pop();';
	}

	public static function emit_scope_push ($names)
	{
		if (count ($names) < 1)
			return '';

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
		if (!preg_match ('/^[_A-Za-z][_0-9A-Za-z]*$/', $name) || $name === self::$input_name || $name === self::$local_name || $name === self::$state_name)
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

	private $trimmer;
	private $unique;
	private $version;

	public function __construct ($setup)
	{
		static $trims;

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
					throw new ParseException ('<setup>', 'unknown style "' . $style . '"');

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
			throw new ParseException ('<setup>', 'invalid style, must be either builtin style or callable');

		$this->unique = 0;
		$this->version = $setup->version;
	}

	public function emit_unique ()
	{
		return '$' . self::$state_name . '->_' . $this->unique++;
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
