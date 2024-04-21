<?php

namespace Deval;

class Generator
{
    private static $input_name = '_deval_input';

    public static function merge_symbols(&$symbols, $others)
    {
        foreach ($others as $name => $count) {
            $symbols[$name] = (isset($symbols[$name]) ? $symbols[$name] : 0) + $count;
        }

        return $symbols;
    }

    private $plain_text_processor;
    private $undefined_variable_fallback;
    private $unique;
    private $version;

    public function __construct($setup)
    {
        static $named_processors;

        if (!isset($named_processors)) {
            $named_processors = array(
                'collapse' => function ($s) {
                    return preg_replace('/\\s+/mu', ' ', $s);
                },
                'deindent' => function ($s) {
                    return preg_replace("/^(?:\n|\r|\n\r|\r\n)[\t ]*|(?:\n|\r|\n\r|\r\n)[\t ]*$/u", '', $s);
                },
                'preserve' => function ($s) {
                    return $s;
                }
            );
        }

        $plain_text_processor = isset($setup->style) ? $setup->style : $setup->plain_text_processor;

        if (is_string($plain_text_processor)) {
            $processors = array();

            foreach (explode(',', $plain_text_processor) as $name) {
                if (!isset($named_processors[$name])) {
                    throw new SetupException('unknown plain text processor "' . $name . '"');
                }

                $processors[] = $named_processors[$name];
            }

            if (count($processors) === 1) {
                $this->plain_text_processor = $processors[0];
            } else {
                $this->plain_text_processor = function ($s) use ($processors) {
                    foreach ($processors as $trimmer) {
                        $s = $trimmer($s);
                    }

                    return $s;
                };
            }
        } elseif (is_callable($plain_text_processor)) {
            $this->plain_text_processor = $plain_text_processor;
        } else {
            throw new SetupException('invalid plain text processor, must be either builtin name or callable');
        }

        $this->undefined_variable_fallback = $setup->undefined_variable_fallback;
        $this->unique = 0;
        $this->version = $setup->version;
    }

    public function emit_backup($backup, $names)
    {
        if (count($names) < 1) {
            return '';
        }

        return $this->emit_symbol($backup) . '=array(' . implode(',', array_map(function ($name) {
            $symbol = $this->emit_symbol($name);

            return 'isset(' . $symbol . ')?' . $symbol . ':null';
        }, $names)) . ');';
    }

    public function emit_member($source, $index)
    {
        return '\\' . __NAMESPACE__ . '\\m(' . $source . ',' . $index . ')';
    }

    public function emit_restore($backup, $names)
    {
        if (count($names) < 1) {
            return '';
        }

        return 'list(' . implode(',', array_map(function ($name) {
            return $this->emit_symbol($name);
        }, $names)) . ')=' . $this->emit_symbol($backup) . ';';
    }

    public function emit_run($names)
    {
        if (count($names) < 1) {
            return '';
        }

        return '\\extract(\\' . __NAMESPACE__ . '\\r($' . self::$input_name . ',' . $this->emit_value($names) . ',' . $this->emit_value($this->undefined_variable_fallback) . '));';
    }

    public function emit_symbol($name)
    {
        if (!preg_match('/^[_A-Za-z][_0-9A-Za-z]*$/', $name) || $name === self::$input_name) {
            throw new RenderException('invalid or reserved symbol name ' . $name);
        }

        return '$' . $name;
    }

    public function emit_value($input)
    {
        if (is_array($input)) {
            $source = '';
            $serial = array_reduce(array_keys($input), function ($result, $item) {
                return $result === $item ? $item + 1 : null;
            }, 0);

            if ($serial !== count($input)) {
                foreach ($input as $key => $value) {
                    $source .= ($source !== '' ? ',' : '') . $this->emit_value($key) . '=>' . $this->emit_value($value);
                }
            } else {
                foreach ($input as $value) {
                    $source .= ($source !== '' ? ',' : '') . $this->emit_value($value);
                }
            }

            return 'array(' . $source . ')';
        } else if (is_callable($input, false, $callable_name)) {
            if (!is_callable($callable_name)) {
                throw new RenderException('cannot serialize anonymous function');
            }

            return var_export($input, true);
        } else {
            return var_export($input, true);
        }
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
        $processor = $this->plain_text_processor;

        return $processor($text);
    }

    public function support($required)
    {
        return version_compare($this->version, $required) >= 0;
    }
}
