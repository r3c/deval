<?php

require '../src/deval.php';

function assert_render ($source, $variables, $expect)
{
	$compiler = new Deval\Compiler (Deval\Block::parse_code ($source));
	$compiler->inject ($variables);

	$result = Deval\Evaluator::code ($compiler->compile (), array ());

	assert ($result === $expect, 'execution failed: ' . var_export ($result, true) . ' !== ' . var_export ($expect, true));
}

function one ()
{
	return 1;
}

// Plain text
assert_render ('lol', array (), 'lol');
assert_render ('l{o}l', array (), 'l{o}l');

// Variable
assert_render ('{{ name }}', array ('name' => 'value'), 'value');

// Expressions (binary)
assert_render ('{{ 1 + 1 }}', array (), '2');
assert_render ('{{ x + 1 }}', array ('x' => '5'), '6');
assert_render ('{{ 2 - 1 }}', array (), '1');
assert_render ('{{ 2 * 2 }}', array (), '4');
assert_render ('{{ 6 / 3 }}', array (), '2');
assert_render ('{{ 4 % 3 }}', array (), '1');
assert_render ('{{ 1 && 0 }}', array (), '');
assert_render ('{{ 1 && 2 }}', array (), '1');
assert_render ('{{ 0 || 0 }}', array (), '');
assert_render ('{{ 1 || 0 }}', array (), '1');

// Expressions (invoke)
assert_render ('{{ one() }}', array ('one' => 'one'), '1');
assert_render ('{{ strlen("Hello, World!") }}', array ('strlen' => 'strlen'), '13');
assert_render ('{{ implode(":", [1, 2, 3]) }}', array ('implode' => 'implode'), '1:2:3');

// Expressions (member)
assert_render ('{{ [1][0] }}', array (), '1');
assert_render ('{{ a[0] }}', array ('a' => array (7)), '7');
assert_render ('{{ [2, 9, 3][x] }}', array ('x' => 1), '9');
assert_render ('{{ a[x][y] }}', array ('a' => array (0, 0, array (0, 5)), 'x' => 2, 'y' => 1), '5');

// Expressions (unary)
assert_render ('{{ 5 + -3 }}', array (), '2');
assert_render ('{{ 5 + +3 }}', array (), '8');
assert_render ('{{ !2 }}', array (), '');
assert_render ('{{ !0 }}', array (), '1');
assert_render ('{{ ~0 }}', array (), '-1');
assert_render ('{{ ~2 }}', array (), '-3');

// If command
assert_render ('{% if 3 %}x{% end %}', array (), 'x');
assert_render ('{% if 3 %}x{% else %}y{% end %}', array (), 'x');
assert_render ('{% if 4 %}x{% else if 8 %}y{% end %}', array (), 'x');
assert_render ('{% if 0 %}x{% else if 4 %}y{% end %}', array (), 'y');
assert_render ('{% if 1 %}x{% else if 0 %}y{% else %}z{% end %}', array (), 'x');
assert_render ('{% if 0 %}x{% else if 1 %}y{% else %}z{% end %}', array (), 'y');
assert_render ('{% if 0 %}x{% else if 0 %}y{% else %}z{% end %}', array (), 'z');

// Include command
assert_render ('{% include template/include_inner.deval %}', array ('inner_x' => 'x', 'inner_y' => 'y'), 'xy');
assert_render ('{% include template/include_outer.deval %}', array ('outer_x' => 'x', 'outer_y' => 'y'), 'xy');

// For command
assert_render ('{% for v in [1, 2, 3] %}{{ v }}{% end %}', array (), '123');
assert_render ('{% for k, v in [1, 2, 3] %}{{ k }}:{{ v }}{% end %}', array (), '0:11:22:3');
assert_render ('{% for k, v in [1] %}x{% empty %}y{% end %}', array (), 'x');
assert_render ('{% for k, v in [] %}x{% empty %}y{% end %}', array (), 'y');

// Let command
assert_render ('{% let a = 5 %}{{ a }}{% end %}', array (), '5');
assert_render ('{% let a = 5, b = 7 %}{{ a }}{{ b }}{% end %}', array (), '57');
assert_render ('{% let a = x %}{{ a }}{% end %}', array ('x' => 'test'), 'test');
assert_render ('{% let a = x, b = a %}{{ b }}{% end %}', array ('x' => 'test'), 'test');

// Renderer
$renderer = new Deval\CacheRenderer ('template', array ('x' => 1, 'y' => 2));
$renderer->render ('template/simple.deval', array ('z' => 3));

?>
