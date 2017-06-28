<?php

namespace Deval;

class Compiler
{
	private static $bases = array ();

	private $expressions = array ();

	public static function parse_code ($source, $blocks = array ())
	{
		return self::parse ('<source>', $source, $blocks);
	}

	public static function parse_file ($path, $blocks = array ())
	{
		$base = count (self::$bases) > 0 ? self::$bases[count (self::$bases) - 1] : '.';

		if (!preg_match ('@^(/|[A-Za-z][-+.0-9A-Za-z]*://)@', $path) && count (self::$bases) > 0)
			$path = $base . DIRECTORY_SEPARATOR . $path;

		if (!file_exists ($path))
			throw new ParseException ($path, 'source file doesn\'t exist');

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
			throw new ParseException ($context, $exception->getMessage () . ' at line ' . $exception->grammarLine . ', character ' . $exception->grammarColumn);
		}
	}

	private $block;

	public function __construct ($block)
	{
		$this->block = $block;
	}

	public function compile ($setup, &$names)
	{
		$variables = array ();
		$source = $this->block->compile (new Generator ($setup), $this->expressions, $variables);
		$names = array_keys ($variables);

		$output = new Output ();
		$output->append_code (Generator::emit_state ($names));
		$output->append ($source);

		return $output->source ();
	}

	public function inject ($constants)
	{
		foreach ($constants as $name => $value)
			$this->expressions[$name] = new ConstantExpression ($value);
	}
}

?>
