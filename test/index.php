<?php

require '../src/deval.php';

function assert_render ($source, $variables, $expect)
{
	$document = new Deval\Document ($source);
	$document->inject ($variables);

	$requires = array ();
	$render = $document->render ($requires);

	assert (count ($requires) === 0, 'rendering failed: ' . var_export ($requires, true));

	ob_start ();
	eval ('?>' . $render);

	$result = ob_get_clean ();

	assert ($result === $expect, 'evaluation failed: ' . var_export ($result, true) . ' !== ' . var_export ($expect, true));
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

// For command
//assert_render ('{% for k in 3 %}x{% end %}', array (), '');
//assert_render ('{% for k, v in 3 %}x{% end %}', array (), '');
//assert_render ('{% for k, v in 3 %}x{% empty %}y{% end %}', array (), '');

// Let command
assert_render ('{% let a as 5 %}{{ a }}{% end %}', array (), '5');
assert_render ('{% let a as 5, b as 7 %}{{ a }}{{ b }}{% end %}', array (), '57');
assert_render ('{% let a as x %}{{ a }}{% end %}', array ('x' => 'test'), 'test');
assert_render ('{% let a as x, b as a %}{{ b }}{% end %}', array ('x' => 'test'), 'test');

?>
