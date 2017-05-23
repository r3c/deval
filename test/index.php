<?php

require '../src/deval.php';

function assert_evaluate ($source, $variables, $expect)
{
	$compiler = new Deval\Compiler (Deval\Block::parse_code ($source));
	$compiler->inject ($variables);

	$result = Deval\Evaluator::code ($compiler->compile (), array ());

	assert ($result === $expect, 'execution failed: ' . var_export ($result, true) . ' !== ' . var_export ($expect, true));
}

function assert_render ($directory, $path, $variables)
{
	$renderer = new Deval\CacheRenderer ($directory, $variables);
	$expect = $renderer->render ($directory . DIRECTORY_SEPARATOR . $path);

	for ($i = count ($variables); $i-- > 0; )
	{
		foreach (combinations ($i, count ($variables)) as $combination)
		{
			$dynamics = array ();
			$statics = array ();
			$j = 0;

			foreach ($variables as $key => $value)
			{
				if ($combination[$j++])
					$dynamics[$key] = $value;
				else
					$statics[$key] = $value;
			}

			$renderer = new Deval\CacheRenderer ('template', $statics);
			$result = $renderer->render ('template/' . $path, $dynamics);

			assert ($result === $expect, 'rendering failed: ' . var_export ($result, true) . ' !== ' . var_export ($expect, true));
		}
	}
}

function combinations ($k, $n, $candidates = array ())
{
	if ($n === 0)
		return array ($candidates);
	else if ($k > $n)
		return array ();
	else if ($k === $n)
		return array (array_merge ($candidates, array_fill (0, $n, true)));
	else if ($k > 0)
		return array_merge (combinations ($k - 1, $n - 1, array_merge ($candidates, array (true))), combinations ($k, $n - 1, array_merge ($candidates, array (false))));
	else
		return array (array_merge ($candidates, array_fill (0, $n, false)));
}

function one ()
{
	return 1;
}

// Plain text
assert_evaluate ('lol', array (), 'lol');
assert_evaluate ('l{o}l', array (), 'l{o}l');

// Variable
assert_evaluate ('{{ name }}', array ('name' => 'value'), 'value');

// Expressions (binary)
assert_evaluate ('{{ 1 + 1 }}', array (), '2');
assert_evaluate ('{{ x + 1 }}', array ('x' => '5'), '6');
assert_evaluate ('{{ 2 - 1 }}', array (), '1');
assert_evaluate ('{{ 2 * 2 }}', array (), '4');
assert_evaluate ('{{ 6 / 3 }}', array (), '2');
assert_evaluate ('{{ 4 % 3 }}', array (), '1');
assert_evaluate ('{{ 1 && 0 }}', array (), '');
assert_evaluate ('{{ 1 && 2 }}', array (), '1');
assert_evaluate ('{{ 0 || 0 }}', array (), '');
assert_evaluate ('{{ 1 || 0 }}', array (), '1');

// Expressions (invoke)
assert_evaluate ('{{ one() }}', array ('one' => 'one'), '1');
assert_evaluate ('{{ strlen("Hello, World!") }}', array ('strlen' => 'strlen'), '13');
assert_evaluate ('{{ implode(":", [1, 2, 3]) }}', array ('implode' => 'implode'), '1:2:3');

// Expressions (member)
assert_evaluate ('{{ [1][0] }}', array (), '1');
assert_evaluate ('{{ a[0] }}', array ('a' => array (7)), '7');
assert_evaluate ('{{ [2, 9, 3][x] }}', array ('x' => 1), '9');
assert_evaluate ('{{ a[x][y] }}', array ('a' => array (0, 0, array (0, 5)), 'x' => 2, 'y' => 1), '5');

// Expressions (unary)
assert_evaluate ('{{ 5 + -3 }}', array (), '2');
assert_evaluate ('{{ 5 + +3 }}', array (), '8');
assert_evaluate ('{{ !2 }}', array (), '');
assert_evaluate ('{{ !0 }}', array (), '1');
assert_evaluate ('{{ ~0 }}', array (), '-1');
assert_evaluate ('{{ ~2 }}', array (), '-3');

// If command
assert_evaluate ('{% if 3 %}x{% end %}', array (), 'x');
assert_evaluate ('{% if 3 %}x{% else %}y{% end %}', array (), 'x');
assert_evaluate ('{% if 4 %}x{% else if 8 %}y{% end %}', array (), 'x');
assert_evaluate ('{% if 0 %}x{% else if 4 %}y{% end %}', array (), 'y');
assert_evaluate ('{% if 1 %}x{% else if 0 %}y{% else %}z{% end %}', array (), 'x');
assert_evaluate ('{% if 0 %}x{% else if 1 %}y{% else %}z{% end %}', array (), 'y');
assert_evaluate ('{% if 0 %}x{% else if 0 %}y{% else %}z{% end %}', array (), 'z');

// Include command
assert_evaluate ('{% include template/include_inner.deval %}', array ('inner_x' => 'x', 'inner_y' => 'y'), 'xy');
assert_evaluate ('{% include template/include_outer.deval %}', array ('outer_x' => 'x', 'outer_y' => 'y'), 'xy');

// For command
assert_evaluate ('{% for v in [1, 2, 3] %}{{ v }}{% end %}', array (), '123');
assert_evaluate ('{% for k, v in [1, 2, 3] %}{{ k }}:{{ v }}{% end %}', array (), '0:11:22:3');
assert_evaluate ('{% for k, v in [1] %}x{% empty %}y{% end %}', array (), 'x');
assert_evaluate ('{% for k, v in [] %}x{% empty %}y{% end %}', array (), 'y');

// Let command
assert_evaluate ('{% let a = 5 %}{{ a }}{% end %}', array (), '5');
assert_evaluate ('{% let a = 5, b = 7 %}{{ a }}{{ b }}{% end %}', array (), '57');
assert_evaluate ('{% let a = x %}{{ a }}{% end %}', array ('x' => 'test'), 'test');
assert_evaluate ('{% let a = x, b = a %}{{ b }}{% end %}', array ('x' => 'test'), 'test');

// Renderer
assert_render ('template', 'simple.deval', array ('x' => 1, 'y' => 2, 'z' => 3));

?>
