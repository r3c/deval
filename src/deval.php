<?php

namespace Deval;

class CompileException extends \Exception
{
    public function __construct($message)
    {
        parent::__construct('compile error: ' . $message);
    }
}

class ParseException extends \Exception
{
    public $location;

    public function __construct($location, $message)
    {
        parent::__construct('parse error in ' . $location->context . ' at line ' . $location->line . ' column ' . $location->column . ': ' . $message);

        $this->location = $location;
    }
}

class RenderException extends \Exception
{
    public function __construct($message)
    {
        parent::__construct('runtime error: ' . $message);
    }
}

class SetupException extends \Exception
{
    public function __construct($message)
    {
        parent::__construct('setup error: ' . $message);
    }
}

class Builtin
{
    public static function _array()
    {
        $array = array();

        foreach (func_get_args() as $value) {
            $array = array_merge($array, (array)$value);
        }

        return $array;
    }

    public static function _bool($value)
    {
        return (bool)$value;
    }

    public static function _cat()
    {
        $args = func_get_args();

        if (count($args) < 1) {
            return null;
        } elseif (is_array($args[0])) {
            return call_user_func_array('array_merge', $args);
        }

        $buffer = '';

        foreach ($args as $arg) {
            $buffer .= (string)$arg;
        }

        return $buffer;
    }

    public static function _compare($a, $b)
    {
        $type_a = gettype($a);
        $type_b = gettype($b);

        if ($type_a !== $type_b) {
            return strcmp($type_a, $type_b);
        }

        switch ($type_a) {
            case "boolean":
                return (int)$a - (int)$b;

            case "double":
            case "integer":
                return $a - $b;

            case "string":
                return strcmp($a, $b);

            default:
                return 0;
        }
    }

    public static function _default($value, $fallback)
    {
        return $value !== null ? $value : $fallback;
    }

    public static function _filter($items, $predicate = null)
    {
        if ($predicate === null) {
            $predicate = function ($v) {
                return $v;
            };
        }

        return array_filter($items, $predicate, ARRAY_FILTER_USE_BOTH);
    }

    public static function _find($items, $predicate = null)
    {
        foreach ($items as $key => $value) {
            if ($predicate === null || $predicate($value, $key)) {
                return array($key, $value);
            }
        }

        return null;
    }

    public static function _flip($items)
    {
        return array_flip($items);
    }

    public static function _float($value)
    {
        return (float)$value;
    }

    public static function _group($items, $get_key = null, $get_value = null, $initial = null)
    {
        if ($get_key === null) {
            $get_key = function ($v) {
                return $v;
            };
        }

        if ($get_value === null) {
            $get_value = function ($_, $v) {
                return $v;
            };
        }

        $groups = array();

        foreach ($items as $key => $value) {
            $group_key = $get_key($value, $key);
            $group_value = $get_value(isset($groups[$group_key]) ? $groups[$group_key] : $initial, $value);

            $groups[$group_key] = $group_value;
        }

        return $groups;
    }

    public static function _int($value)
    {
        return (int)$value;
    }

    public static function _join($items, $separator = '')
    {
        return implode($separator, $items);
    }

    public static function _keys($items)
    {
        return array_keys($items);
    }

    public static function _length($value)
    {
        if ($value === null) {
            return null;
        } elseif (is_array($value)) {
            return count($value);
        } else {
            return mb_strlen((string)$value);
        }
    }

    public static function _map($items, $apply)
    {
        return array_map($apply, $items);
    }

    public static function _max($first)
    {
        if (is_array($first) && count($first) === 0) {
            return null;
        }

        return call_user_func_array('max', func_get_args());
    }

    public static function _min($first)
    {
        if (is_array($first) && count($first) === 0) {
            return null;
        }

        return call_user_func_array('min', func_get_args());
    }

    public static function _php($symbol)
    {
        if (!preg_match('/^(([0-9A-Z\\\\_a-z]+)::)?([#$])?([0-9A-Z_a-z]+)$/', $symbol, $match)) {
            throw new RenderException('invalid symbol "' . $symbol . '" passed to php() builtin');
        }

        switch ($match[3]) {
            case '#':
                return constant($match[1] . $match[4]);

            case '$':
                $class = $match[2];
                $name = $match[4];

                if ($class === '') {
                    return isset($GLOBALS[$name]) ? $GLOBALS[$name] : null;
                }

                $vars = get_class_vars($class);

                return isset($vars[$name]) ? $vars[$name] : null;

            default:
                return $match[0];
        }
    }

    public static function _range($start, $stop, $step = 1)
    {
        if ((int)$start !== (int)$stop && ($start < $stop) !== ($step > 0)) {
            return array();
        }

        return range((int)$start, (int)$stop, (int)$step);
    }

    public static function _reduce($items, $callback, $initial = null)
    {
        return array_reduce($items, $callback, $initial);
    }

    public static function _replace($value, $replacements)
    {
        return str_replace(array_keys($replacements), array_values($replacements), $value);
    }

    public static function _reverse($value)
    {
        if (is_array($value)) {
            return array_reverse($value, true);
        }

        return strrev((string)$value);
    }

    public static function _slice($value, $offset, $count = null)
    {
        if ($value === null) {
            return null;
        } elseif (is_array($value)) {
            if ($count !== null) {
                return array_slice($value, $offset, $count);
            }

            return array_slice($value, $offset);
        } else {
            if ($count !== null) {
                return mb_substr((string)$value, $offset, $count);
            }

            return mb_substr((string)$value, $offset);
        }
    }

    public static function _sort($items, $compare = null)
    {
        if ($compare !== null) {
            uasort($items, $compare);
        } else {
            asort($items);
        }

        return $items;
    }

    public static function _split($string, $separator, $limit = PHP_INT_MAX)
    {
        return explode($separator, $string, $limit);
    }

    public static function _str($value)
    {
        return (string)$value;
    }

    public static function _values($items)
    {
        return array_values($items);
    }

    public static function _void()
    {
        return null;
    }

    public static function _zip($keys, $values)
    {
        return array_combine($keys, $values);
    }

    public static function deval()
    {
        $class = '\\' . get_called_class();
        $names = array(
            'array',
            'bool',
            'cat',
            'compare',
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
            'reverse',
            'slice',
            'sort',
            'split',
            'str',
            'values',
            'void',
            'zip'
        );

        return array_combine($names, array_map(function ($name) use ($class) {
            return array($class, '_' . $name);
        }, $names));
    }

    public static function php()
    {
        $functions = get_defined_functions();

        return array_combine($functions['internal'], $functions['internal']);
    }
}

class Evaluator
{
    public static function code($_deval_code, $_deval_input)
    {
        ob_start();

        try {
            eval('?>' . $_deval_code);
        } catch (\Exception $exception) { // Replace by "finally" once PHP < 5.5 compatibility can be dropped
            ob_end_clean();

            throw $exception;
        }

        return ob_get_clean();
    }

    public static function path($_deval_path, $_deval_input)
    {
        ob_start();

        try {
            require $_deval_path;
        } catch (\Exception $exception) { // Replace by "finally" once PHP < 5.5 compatibility can be dropped
            ob_end_clean();

            throw $exception;
        }

        return ob_get_clean();
    }
}

class Loader
{
    public static function load()
    {
        static $setup;

        if (isset($setup)) {
            return;
        }

        require __DIR__ . '/block.php';
        require __DIR__ . '/blocks/concat.php';
        require __DIR__ . '/blocks/echo.php';
        require __DIR__ . '/blocks/for.php';
        require __DIR__ . '/blocks/if.php';
        require __DIR__ . '/blocks/label.php';
        require __DIR__ . '/blocks/let.php';
        require __DIR__ . '/blocks/plain.php';
        require __DIR__ . '/blocks/unwrap.php';
        require __DIR__ . '/blocks/void.php';
        require __DIR__ . '/compiler.php';
        require __DIR__ . '/expression.php';
        require __DIR__ . '/expressions/array.php';
        require __DIR__ . '/expressions/binary.php';
        require __DIR__ . '/expressions/constant.php';
        require __DIR__ . '/expressions/defer.php';
        require __DIR__ . '/expressions/group.php';
        require __DIR__ . '/expressions/invoke.php';
        require __DIR__ . '/expressions/lambda.php';
        require __DIR__ . '/expressions/member.php';
        require __DIR__ . '/expressions/symbol.php';
        require __DIR__ . '/expressions/unary.php';
        require __DIR__ . '/generator.php';
        require __DIR__ . '/location.php';
        require __DIR__ . '/output.php';
        require __DIR__ . '/parser.php';

        $setup = true;
    }
}

interface Renderer
{
    public function inject($constants);
    public function render($variables = array());
    public function source(&$names = null);
}

class CacheRenderer implements Renderer
{
    private $constants;
    private $directory;
    private $invalidate;
    private $path;
    private $setup;

    public function __construct($path, $directory, $setup = null, $invalidate = false)
    {
        $this->constants = array();
        $this->directory = $directory;
        $this->invalidate = $invalidate;
        $this->path = $path;
        $this->setup = $setup ?: new Setup();
    }

    public function inject($constants)
    {
        $this->constants += $constants;
    }

    public function render($variables = array())
    {
        $cache = $this->directory . DIRECTORY_SEPARATOR . pathinfo(basename($this->path), PATHINFO_FILENAME) . '_' . md5($this->path . ':' . serialize($this->constants)) . '.php';

        if (!file_exists($cache) || filemtime($cache) < filemtime($this->path) || $this->invalidate) {
            file_put_contents($cache, $this->source($names), LOCK_EX);
        }

        return Evaluator::path($cache, $variables);
    }

    public function source(&$names = null)
    {
        Loader::load();

        $compiler = new Compiler(Compiler::parse_file($this->path));
        $compiler->inject($this->constants);

        return $compiler->compile($this->setup, $names);
    }
}

class DirectRenderer implements Renderer
{
    private $compiler;
    private $setup;

    protected function __construct($compiler, $setup)
    {
        $this->compiler = $compiler;
        $this->setup = $setup;
    }

    public function inject($constants)
    {
        $this->compiler->inject($constants);
    }

    public function render($variables = array())
    {
        return Evaluator::code($this->source($names), $variables);
    }

    public function source(&$names = null)
    {
        return $this->compiler->compile($this->setup, $names);
    }
}

class FileRenderer extends DirectRenderer
{
    public function __construct($path, $setup = null)
    {
        Loader::load();

        parent::__construct(new Compiler(Compiler::parse_file($path)), $setup ?: new Setup());
    }
}

class StringRenderer extends DirectRenderer
{
    public function __construct($source, $setup = null)
    {
        Loader::load();

        parent::__construct(new Compiler(Compiler::parse_code($source)), $setup ?: new Setup());
    }
}

class Setup
{
    public $plain_text_processor = 'deindent';
    public $undefined_variable_fallback = null;
    public $version = PHP_VERSION;
}

/*
** Membership function used to access member by key from parent instance.
** $parent:	parent array or object
** $key:	member key
** return:	member value or null if not found
*/
function m($parent, $key)
{
    if (is_object($parent)) {
        if (is_callable(array($parent, $key))) {
            return array($parent, $key);
        } elseif (property_exists($parent, $key)) {
            return $parent->$key;
        } elseif ($parent instanceof \ArrayAccess) {
            return $parent[$key];
        }
    } elseif (isset($parent[$key])) {
        return $parent[$key];
    }

    return null;
}

/*
** Deval run method, assert provided symbols match required ones and throw
** exception otherwise.
** $provided: provided symbols map
** $required: required symbols list
** $fallback: undefined variable fallback function or `null` to throw on undefined variables
*/
function r($provided, $required, $fallback)
{
    $names = array_diff($required, array_keys($provided));

    if (count($names) > 0) {
        if ($fallback === null) {
            throw new RenderException('undefined symbol(s) ' . implode(', ', $names));
        } else if (!is_callable($fallback)) {
            throw new RenderException('configured undefined variable fallback is not a callable function');
        }

        foreach ($names as $name) {
            $provided[$name] = $fallback($name);
        }
    }

    return $provided;
}
