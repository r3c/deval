<?php

require '../src/deval.php';
require 'test.php';

header ('Content-Type: text/plain');

// Compile exceptions
raise_compile ('{{ x y }}', 'but "y" found');
raise_compile ('{{ x ** y }}', 'but "*" found');

// Inject exceptions
raise_inject ('{{ x }}', array ('x' => array ()), 'cannot be converted to string');
raise_inject ('{% for i in x %}{% end %}', array ('x' => 1), 'is not iterable');
raise_inject ('{{ f() }}', array ('f' => 1), 'is not callable');
raise_inject ('{{ f() }}', array ('f' => 'i_do_not_exist'), 'is not callable');

// Render plain texts
render_code ('lol', array (), 'lol');
render_code ('l{o}l', array (), 'l{o}l');

// Render variables
render_code ('{{ bool }}', make_combinations (array ('bool' => true)), '1');
render_code ('{{ int }}', make_combinations (array ('int' => 3)), '3');
render_code ('{{ str }}', make_combinations (array ('str' => 'value')), 'value');

// Render binary expressions
render_code ('{{ 1 + 1 }}', array (), '2');
render_code ('{{ x + 1 }}', make_combinations (array ('x' => '5')), '6');
render_code ('{{ 2 - 1 }}', array (), '1');
render_code ('{{ 2 * 2 }}', array (), '4');
render_code ('{{ 6 / 3 }}', array (), '2');
render_code ('{{ 4 % 3 }}', array (), '1');
render_code ('{{ 1 && 0 }}', array (), '');
render_code ('{{ 1 && 2 }}', array (), '1');
render_code ('{{ 0 || 0 }}', array (), '');
render_code ('{{ 1 || 0 }}', array (), '1');

// Render invoke expressions
render_code ('{{ one() }}', make_combinations (array ('one' => function () { return 1; })), '1');
render_code ('{{ strlen("Hello, World!") }}', make_combinations (array ('strlen' => 'strlen')), '13');
render_code ('{{ implode(":", [1, 2, 3]) }}', make_combinations (array ('implode' => 'implode')), '1:2:3');
render_code ('{{ two()() }}', make_combinations (array ('two' => function () { return function () { return 2; }; })), '2');
render_code ('{{ strlen(x) }}', make_combinations (array ('strlen' => 'strlen', 'x' => 'something')), '9');
render_code ('{{ inc(x) }}', make_slices (array ('inc' => function ($x) { return $x + 1; }, 'x' => 1)), '2');

// Render member expressions
render_code ('{{ [1][0] }}', array (), '1');
render_code ('{{ a[0] }}', make_combinations (array ('a' => array (7))), '7');
render_code ('{{ [2, 9, 3][x] }}', make_combinations (array ('x' => 1)), '9');
render_code ('{{ a[x][y] }}', make_combinations (array ('a' => array (0, 0, array (0, 5)), 'x' => 2, 'y' => 1)), '5');

// Render unary expressions
render_code ('{{ 5 + -3 }}', array (), '2');
render_code ('{{ 5 + +3 }}', array (), '8');
render_code ('{{ !2 }}', array (), '');
render_code ('{{ !0 }}', array (), '1');
render_code ('{{ ~0 }}', array (), '-1');
render_code ('{{ ~2 }}', array (), '-3');

// Render for command
render_code ('{% for v in [1, 2, 3] %}{{ v }}{% end %}', array (), '123');
render_code ('{% for k, v in [1, 2, 3] %}{{ k }}:{{ v }}{% end %}', array (), '0:11:22:3');
render_code ('{% for k, v in [1] %}x{% empty %}y{% end %}', array (), 'x');
render_code ('{% for k, v in [] %}x{% empty %}y{% end %}', array (), 'y');

// Render if command
render_code ('{% if 3 %}x{% end %}', array (), 'x');
render_code ('{% if 3 %}x{% else %}y{% end %}', array (), 'x');
render_code ('{% if 4 %}x{% else if 8 %}y{% end %}', array (), 'x');
render_code ('{% if 0 %}x{% else if 4 %}y{% end %}', array (), 'y');
render_code ('{% if 1 %}x{% else if 0 %}y{% else %}z{% end %}', array (), 'x');
render_code ('{% if 0 %}x{% else if 1 %}y{% else %}z{% end %}', array (), 'y');
render_code ('{% if 0 %}x{% else if 0 %}y{% else %}z{% end %}', array (), 'z');

// Render import command
render_code ('{% import template/import_inner.deval %}{% block first %}1{% block second %}2{% end %}', make_combinations (array ('a' => 'A', 'b' => 'B', 'c' => 'C')), 'A1B2C');
render_code ('{% import template/import_outer.deval %}{% end %}', make_combinations (array ('first' => 'x', 'second' => 'y')), '1x2y3');

// Render include command
render_code ('{% include template/include_inner.deval %}', make_combinations (array ('inner_x' => 'x', 'inner_y' => 'y')), 'xy');
render_code ('{% include template/include_outer.deval %}', make_combinations (array ('outer_x' => 'x', 'outer_y' => 'y')), 'xy');

// Render let command
render_code ('{% let a = 5 %}{{ a }}{% end %}', array (), '5');
render_code ('{% let a = 5, b = 7 %}{{ a }}{{ b }}{% end %}', array (), '57');
render_code ('{% let a = x %}{{ a }}{% end %}', make_combinations (array ('x' => 'test')), 'test');

render_code ('{% let a = x, b = a %}{{ b }}{% end %}', make_combinations (array ('x' => 'test')), 'test');
render_code ('{% let a = b, b = x %}{{ a }}{{ b }}{% end %}', make_combinations (array ('b' => '1', 'x' => '2')), '12');

// Render files
render_file ('template/member.deval', 'template', make_combinations (array ('x' => 0)), '1337');
render_file ('template/symbol.deval', 'template', make_combinations (array ('x' => 1, 'y' => 2, 'z' => 3)), '1 2 3');

?>
