<?php

require '../src/deval.php';
require 'test.php';

header ('Content-Type: text/plain');

// Plain text
render_code ('lol', array (), 'lol');
render_code ('l{o}l', array (), 'l{o}l');

// Variable
render_code ('{{ name }}', make_combinations (array ('name' => 'value')), 'value');

// Expressions (binary)
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

// Expressions (invoke)
render_code ('{{ one() }}', make_combinations (array ('one' => function () { return 1; })), '1');
render_code ('{{ strlen("Hello, World!") }}', make_combinations (array ('strlen' => 'strlen')), '13');
render_code ('{{ implode(":", [1, 2, 3]) }}', make_combinations (array ('implode' => 'implode')), '1:2:3');
render_code ('{{ two()() }}', make_constants (array ('two' => function () { return function () { return 2; }; })), '2');
render_code ('{{ strlen(x) }}', make_combinations (array ('strlen' => 'strlen', 'x' => 'something')), '9');

// Expressions (member)
render_code ('{{ [1][0] }}', array (), '1');
render_code ('{{ a[0] }}', make_combinations (array ('a' => array (7))), '7');
render_code ('{{ [2, 9, 3][x] }}', make_combinations (array ('x' => 1)), '9');
render_code ('{{ a[x][y] }}', make_combinations (array ('a' => array (0, 0, array (0, 5)), 'x' => 2, 'y' => 1)), '5');

// Expressions (unary)
render_code ('{{ 5 + -3 }}', array (), '2');
render_code ('{{ 5 + +3 }}', array (), '8');
render_code ('{{ !2 }}', array (), '');
render_code ('{{ !0 }}', array (), '1');
render_code ('{{ ~0 }}', array (), '-1');
render_code ('{{ ~2 }}', array (), '-3');

// For command
render_code ('{% for v in [1, 2, 3] %}{{ v }}{% end %}', array (), '123');
render_code ('{% for k, v in [1, 2, 3] %}{{ k }}:{{ v }}{% end %}', array (), '0:11:22:3');
render_code ('{% for k, v in [1] %}x{% empty %}y{% end %}', array (), 'x');
render_code ('{% for k, v in [] %}x{% empty %}y{% end %}', array (), 'y');

// If command
render_code ('{% if 3 %}x{% end %}', array (), 'x');
render_code ('{% if 3 %}x{% else %}y{% end %}', array (), 'x');
render_code ('{% if 4 %}x{% else if 8 %}y{% end %}', array (), 'x');
render_code ('{% if 0 %}x{% else if 4 %}y{% end %}', array (), 'y');
render_code ('{% if 1 %}x{% else if 0 %}y{% else %}z{% end %}', array (), 'x');
render_code ('{% if 0 %}x{% else if 1 %}y{% else %}z{% end %}', array (), 'y');
render_code ('{% if 0 %}x{% else if 0 %}y{% else %}z{% end %}', array (), 'z');

// Import command
render_code ('{% import template/import_inner.deval %}{% block first %}1{% block second %}2{% end %}', make_combinations (array ('a' => 'A', 'b' => 'B', 'c' => 'C')), 'A1B2C');
render_code ('{% import template/import_outer.deval %}{% end %}', make_combinations (array ('first' => 'x', 'second' => 'y')), '1x2y3');

// Include command
render_code ('{% include template/include_inner.deval %}', make_combinations (array ('inner_x' => 'x', 'inner_y' => 'y')), 'xy');
render_code ('{% include template/include_outer.deval %}', make_combinations (array ('outer_x' => 'x', 'outer_y' => 'y')), 'xy');

// Let command
render_code ('{% let a = 5 %}{{ a }}{% end %}', array (), '5');
render_code ('{% let a = 5, b = 7 %}{{ a }}{{ b }}{% end %}', array (), '57');
render_code ('{% let a = x %}{{ a }}{% end %}', make_combinations (array ('x' => 'test')), 'test');

render_code ('{% let a = x, b = a %}{{ b }}{% end %}', make_combinations (array ('x' => 'test')), 'test');
render_code ('{% let a = b, b = x %}{{ a }}{{ b }}{% end %}', make_combinations (array ('b' => '1', 'x' => '2')), '12');

// Renderer
render_file ('template/member.deval', 'template', make_combinations (array ('x' => 0)), '1337');
render_file ('template/symbol.deval', 'template', make_combinations (array ('x' => 1, 'y' => 2, 'z' => 3)), '1 2 3');

?>
