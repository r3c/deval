<?php

namespace Deval;

class Generator
{
	private static $input_name = '_deval_input';
	private static $local_name = '_deval_local';
	private static $state_name = '_deval_state';

	public static function assert_symbol ($name)
	{
		if (!preg_match ('/^[_A-Za-z][_0-9A-Za-z]*$/', $name))
			throw new \Exception ('invalid symbol name');
	}

	public static function emit_create ($names)
	{
		return
			'$' . self::$state_name . '=new \\Deval\\State(' . self::emit_value ($names) . ',$' . self::$input_name . ');' .
			'extract($' . self::$input_name . ');';
	}

	public static function emit_member ($source, $index)
	{
		return '\\' . get_class () . '::member(' . $source . ',' . $index . ')';
	}

	public static function emit_value ($input)
	{
		if (is_array ($input))
		{
			$out = '';

			if (array_reduce (array_keys ($input), function (&$result, $item) { return $result === $item ? $item + 1 : null; }, 0) !== count ($input))
			{
				foreach ($input as $key => $value)
					$out .= ($out !== '' ? ',' : '') . self::emit_value ($key) . '=>' . self::emit_value ($value);
			}
			else
			{
				foreach ($input as $value)
					$out .= ($out !== '' ? ',' : '') . self::emit_value ($value);
			}

			return 'array(' . $out . ')';
		}

		return var_export ($input, true);
	}

	public static function dummy ()
	{
		return new self (function ($s) { return ''; });
	}

	public static function member ($source, $index)
	{
		$array = (array)$source;

		if (isset ($array[$index]))
			return $array[$index];

		return null;
	}

	private $local;
	private $trim;

	public function __construct ($trim)
	{
		$this->local = 0;
		$this->trim = $trim;
	}

	public function make_local ()
	{
		return self::$local_name . $this->local++;
	}

	public function make_plain ($text)
	{
		$trim = $this->trim;

		return $trim ($text);
	}
}

?>
