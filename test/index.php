<?php

require '../src/deval.php';

function assert_string ($source, $expect)
{
	$document = new Deval\Document ($source);
	$actual = (string)$document;

	//assert ($actual === $expect, var_export ($actual, true) . ' !== ' . var_export ($expect, true));
	assert (is_string ($actual));
}

assert_string ('lol', 'plain(lol)');
assert_string ('l{o}l', 'plain(l{o}l)');
assert_string ('{{ name }}', 'echo(name)');
assert_string ('{% buffer name %}x{% end %}', '');
assert_string ('{% if 3 %}x{% end %}', '');
assert_string ('{% if 3 %}x{% else %}y{% end %}', '');
assert_string ('{% if 3 %}x{% else if 4 %}y{% end %}', '');
assert_string ('{% if 3 %}x{% else if 4 %}y{% else %}z{% end %}', '');
assert_string ('{% for k in 3 %}x{% end %}', '');
assert_string ('{% for k, v in 3 %}x{% end %}', '');
assert_string ('{% for k, v in 3 %}x{% empty %}y{% end %}', '');
assert_string ('{% let a as 5 %}x{% end %}', '');
assert_string ('{% let a as 5, b as 7 %}x{% end %}', '');

?>
