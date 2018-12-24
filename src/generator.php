<?php

namespace Deval;

class Generator
{
    private static $input_name = '_deval_input';

    public static function emit_backup($backup, $names)
    {
        if (count($names) < 1) {
            return '';
        }

        return self::emit_symbol($backup) . '=array(' . implode(',', array_map(function ($name) {
            $symbol = Generator::emit_symbol($name);

            return 'isset(' . $symbol . ')?' . $symbol . ':null';
        }, $names)) . ');';
    }

    public static function emit_member($source, $index)
    {
        return '\\' . __NAMESPACE__ . '\\m(' . $source . ',' . $index . ')';
    }

    public static function emit_restore($backup, $names)
    {
        if (count($names) < 1) {
            return '';
        }

        return 'list(' . implode(',', array_map(function ($name) {
            return Generator::emit_symbol($name);
        }, $names)) . ')=' . self::emit_symbol($backup) . ';';
    }

    public static function emit_run($names)
    {
        return
            '\\' . __NAMESPACE__ . '\\r(' . self::emit_value($names) . ',$' . self::$input_name . ');' .
            '\\extract($' . self::$input_name . ');';
    }

    public static function emit_symbol($name)
    {
        if (!preg_match('/^[_A-Za-z][_0-9A-Za-z]*$/', $name) || $name === self::$input_name) {
            throw new RenderException('invalid or reserved symbol name ' . $name);
        }

        return '$' . $name;
    }

    public static function emit_value($input)
    {
        if (is_array($input)) {
            $source = '';

            if (array_reduce(array_keys($input), function (&$result, $item) {
                return $result === $item ? $item + 1 : null;
            }, 0) !== count($input)) {
                foreach ($input as $key => $value) {
                    $source .= ($source !== '' ? ',' : '') . self::emit_value($key) . '=>' . self::emit_value($value);
                }
            } else {
                foreach ($input as $value) {
                    $source .= ($source !== '' ? ',' : '') . self::emit_value($value);
                }
            }

            return 'array(' . $source . ')';
        }

        return var_export($input, true);
    }

    public static function merge_symbols(&$symbols, $others)
    {
        foreach ($others as $name => $count) {
            $symbols[$name] = (isset($symbols[$name]) ? $symbols[$name] : 0) + $count;
        }

        return $symbols;
    }

    private $trimmer;
    private $unique;
    private $version;

    public function __construct($setup)
    {
        static $trims;

        if (!isset($trims)) {
            $trims = array(
                'collapse'	=> function ($s) {
                    return preg_replace('/\\s+/mu', ' ', $s);
                },
                'deindent'	=> function ($s) {
                    return preg_replace("/^(?:\n|\r|\n\r|\r\n)[\t ]*|(?:\n|\r|\n\r|\r\n)[\t ]*$/u", '', $s);
                },
                'preserve'	=> function ($s) {
                    return $s;
                }
            );
        }

        if (is_string($setup->style)) {
            $trimmers = array();

            foreach (explode(',', $setup->style) as $style) {
                if (!isset($trims[$style])) {
                    throw new SetupException('unknown style "' . $style . '"');
                }

                $trimmers[] = $trims[$style];
            }

            if (count($trimmers) === 1) {
                $this->trimmer = $trimmers[0];
            } else {
                $this->trimmer = function ($s) use ($trimmers) {
                    foreach ($trimmers as $trimmer) {
                        $s = $trimmer($s);
                    }

                    return $s;
                };
            }
        } elseif (is_callable($setup->style)) {
            $this->trimmer = $setup->style;
        } else {
            throw new SetupException('invalid style, must be either builtin style or callable');
        }

        $this->unique = 0;
        $this->version = $setup->version;
    }

    public function make_local($preserves)
    {
        do {
            $name = '_' . $this->unique++;
        } while (isset($preserves[$name]));

        return $name;
    }

    public function make_plain($text)
    {
        $trim = $this->trimmer;

        return $trim($text);
    }

    public function support($required)
    {
        return version_compare($this->version, $required) >= 0;
    }
}
