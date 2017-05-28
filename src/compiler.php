<?php

namespace Deval;

class Compiler
{
	private static $bases = array ();

	public static function parse_code ($source, $blocks = array ())
	{
		return self::parse ('source code', $source, $blocks);
	}

	public static function parse_file ($path, $blocks = array ())
	{
		$base = count (self::$bases) > 0 ? self::$bases[count (self::$bases) - 1] : '.';
		$path = strlen ($path) > 0 && $path[0] === DIRECTORY_SEPARATOR ? $path : $base . DIRECTORY_SEPARATOR . $path;

		if (!file_exists ($path))
			throw new CompileException ($path, 'source file doesn\'t exist');

		array_push (self::$bases, dirname ($path));

		try
		{
			$block = self::parse ($path, file_get_contents ($path), $blocks);
		}
		catch (\Exception $exception)
		{
			array_pop (self::$bases);

			throw $exception;
		}

		array_pop (self::$bases);

		return $block;
	}

	private static function parse ($context, $source, $blocks)
	{
		$parser = new \PhpPegJs\Parser ();

		try
		{
			return $parser->parse ($source)->resolve ($blocks);
		}
		catch (\PhpPegJs\SyntaxError $exception)
		{
			throw new CompileException ($context, $exception->getMessage () . ' at line ' . $exception->grammarLine . ', character ' . $exception->grammarColumn);
		}
	}

	private $block;

	public function __construct ($block)
	{
		$this->block = $block;
	}

	public function compile ($style, &$names)
	{
		static $trims;

		if (!isset ($trims))
		{
			$trims = array
			(
				'collapse'	=> function ($s) { return preg_replace ('/\\s+/', ' ', $s); },
				'html'		=> function ($s) { return preg_replace (array ('/(^|>)\\s+/m', '/\\s+(<|$)/m'), array ('$1 ', ' $1'), $s); }
			);
		}

		if (is_string ($style) && isset ($trims[$style]))
			$trim = $trims[$style];
		else if (is_callable ($style))
			$trim = $style;
		else
			$trim = function ($s) { return $s; };

		$volatiles = array ();
		$source = $this->block->compile (new Generator ($trim), $volatiles);
		$names = array_keys ($volatiles);

		$output = new Output ();
		$output->append_code (Generator::emit_create ($names));
		$output->append ($source);

		return $output->source ();
	}

	public function inject ($constants)
	{
		$this->block = $this->block->inject ($constants);
	}
}

?>
