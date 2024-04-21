<?php

namespace Deval;

class Compiler
{
    private static $bases = array();

    private $expressions = array();

    public static function parse_code($source, $blocks = array())
    {
        return self::parse('<source>', $source, $blocks);
    }

    public static function parse_file($path, $blocks = array())
    {
        $base = count(self::$bases) > 0 ? self::$bases[count(self::$bases) - 1] : '.';

        if (!preg_match('@^(/|[A-Za-z][-+.0-9A-Za-z]*://)@', $path) && count(self::$bases) > 0) {
            $path = $base . DIRECTORY_SEPARATOR . $path;
        }

        if (!file_exists($path)) {
            throw new ParseException(new Location($path, 0, 0), 'source file doesn\'t exist');
        }

        array_push(self::$bases, dirname($path));

        try {
            $block = self::parse($path, file_get_contents($path), $blocks);
        } catch (\Exception $exception) {
            array_pop(self::$bases);

            throw $exception;
        }

        array_pop(self::$bases);

        return $block;
    }

    private static function parse($context, $source, $blocks)
    {
        $parser = new \PhpPegJs\Parser();
        $parser->context = $context;

        try {
            return $parser->parse($source)->resolve($blocks);
        } catch (\PhpPegJs\SyntaxError $exception) {
            $location = new Location($context, $exception->grammarLine, $exception->grammarColumn);

            throw new ParseException($location, $exception->getMessage());
        }
    }

    private $block;

    public function __construct($block)
    {
        $this->block = $block;
    }

    public function compile($setup, &$names)
    {
        $block = $this->block->inject($this->expressions);
        $symbols = $block->get_symbols();
        $names = array_keys($block->get_symbols());

        $generator = new Generator($setup);
        $source = $block->compile($generator, $symbols);

        $output = new Output();
        $output->append_code($generator->emit_run($names));
        $output->append($source);

        return $output->source();
    }

    public function inject($constants)
    {
        foreach ($constants as $name => $value) {
            $this->expressions[$name] = new ConstantExpression($value);
        }
    }
}
