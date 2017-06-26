<?php

require '../src/deval.php';
require 'test.php';

header ('Content-Type: text/plain');

class TestClass
{
	public $instance_field = 1;

	public function instance_method ()
	{
		return 42;
	}

	public static function static_method ()
	{
		return 17;
	}
}

$preserve = new Deval\Setup ();
$preserve->style = 'preserve';

// Parse exceptions
raise_parse ('{{ x y }}', 'but "y" found');
raise_parse ('{{ x ** y }}', 'but "*" found');

// Compile exceptions
raise_compile ('{{ x }}', array ('x' => array ()), 'cannot be converted to string');
raise_compile ('{% for i in x %}{{ i }}{% end %}', array ('x' => 1), 'is not iterable');
raise_compile ('{{ f() }}', array ('f' => 1), 'is not callable');
raise_compile ('{{ f() }}', array ('f' => 'i_do_not_exist'), 'is not callable');
raise_compile ('{{ ["SomeClass", "missing"](x) }}', array ('x' => 1), 'is not callable');
raise_compile ('{{ ["TestClass", "missing"](x) }}', array ('x' => 1), 'is not callable');

// Render exceptions
raise_render ('{{ a }}', array (), array (), 'undefined symbol(s) a');
raise_render ('{{ a }}{% let a = 1 %}{% end %}', array (), array (), 'undefined symbol(s) a');
raise_render ('{{ _deval_input }}', array (), array ('_deval_input' => 0), 'invalid symbol name _deval_input');
raise_render ('{{ _deval_state }}', array (), array ('_deval_state' => 0), 'invalid symbol name _deval_state');

// Render plain texts
render_code ('lol', make_empty (), 'lol');
render_code ('l{o}l', make_empty (), 'l{o}l');
render_code ('\\{', make_empty (), '{');
render_code ('{\\{', make_empty (), '{{');
render_code ('{\\%', make_empty (), '{%');
render_code ('a%{b', make_empty (), 'a%{b');
render_code ('<?php 1 ?>', make_empty (), '<?php 1 ?>');
render_code ('<? 2 ?>', make_empty (), '<? 2 ?>');
render_code ('<?= 3 ?>', make_empty (), '<?= 3 ?>');
render_code ('<% 4 %>', make_empty (), '<% 4 %>');
render_code ('<%= 5 %>', make_empty (), '<%= 5 %>');
render_code ('<script language="php"> 6 </script>', make_empty (), '<script language="php"> 6 </script>');

// Render interleaved blocks
render_code ("A {{ \"B\" }} C", make_empty (), "A B C", $preserve);
render_code ("A\n{{ \"B\" }}\nC", make_empty (), "A\nB\nC", $preserve);

// Render constants
render_code ('{{ null }}', make_empty (), '');
render_code ('{{ false }}', make_empty (), '');
render_code ('{{ true }}', make_empty (), '1');
render_code ('{{ 0 }}', make_empty (), '0');
render_code ('{{ "hello" }}', make_empty (), 'hello');
render_code ('{{ [1][0] }}', make_empty (), '1');
render_code ('{{ [1: 2][1] }}', make_empty (), '2');
render_code ('{{ ["x": 3]["x"] }}', make_empty (), '3');
render_code ('{{ ["y": [2: 4]]["y"][2] }}', make_empty (), '4');
render_code ('{{ [3 + 4: 2][7] }}', make_empty (), '2');
render_code ('{{ [x, 42][1] }}', make_empty (), '42');

// Render variables
render_code ('{{ bool }}', make_combinations (array ('bool' => true)), '1');
render_code ('{{ int }}', make_combinations (array ('int' => 3)), '3');
render_code ('{{ str }}', make_combinations (array ('str' => 'value')), 'value');

// Render binary expressions
render_code ('{{ 1 + 1 }}', make_empty (), '2');
render_code ('{{ x + 1 }}', make_combinations (array ('x' => '5')), '6');
render_code ('{{ 2 - 1 }}', make_empty (), '1');
render_code ('{{ 2 * 2 }}', make_empty (), '4');
render_code ('{{ 6 / 3 }}', make_empty (), '2');
render_code ('{{ 4 % 3 }}', make_empty (), '1');
render_code ('{{ 1 && 0 }}', make_empty (), '');
render_code ('{{ 1 && 2 }}', make_empty (), '1');
render_code ('{{ 0 || 0 }}', make_empty (), '');
render_code ('{{ 1 || 0 }}', make_empty (), '1');
render_code ('{{ 0 == 1 }}', make_empty (), '');
render_code ('{{ 0 == 0 }}', make_empty (), '1');
render_code ('{{ 0 == "0" }}', make_empty (), '');
render_code ('{{ 0 != 1 }}', make_empty (), '1');
render_code ('{{ 0 != 0 }}', make_empty (), '');
render_code ('{{ 0 != "0" }}', make_empty (), '1');
render_code ('{{ 0 > 0 }}', make_empty (), '');
render_code ('{{ 1 > 0 }}', make_empty (), '1');
render_code ('{{ 0 >= 0 }}', make_empty (), '1');
render_code ('{{ 1 >= 0 }}', make_empty (), '1');
render_code ('{{ 0 < 0 }}', make_empty (), '');
render_code ('{{ 0 < 1 }}', make_empty (), '1');
render_code ('{{ 0 <= 0 }}', make_empty (), '1');
render_code ('{{ 0 <= 1 }}', make_empty (), '1');
render_code ('{{ (x - 1) * 10 }}', make_combinations (array ('x' => '1')), '0');

// Render invoke expressions
render_code ('{{ one() }}', make_combinations (array ('one' => function () { return 1; })), '1');
render_code ('{{ strlen("Hello, World!") }}', make_combinations (array ('strlen' => 'strlen')), '13');
render_code ('{{ implode(":", [1, 2, 3]) }}', make_combinations (array ('implode' => 'implode')), '1:2:3');
render_code ('{{ two()() }}', make_combinations (array ('two' => function () { return function () { return 2; }; })), '2');
render_code ('{{ strlen(x) }}', make_combinations (array ('strlen' => 'strlen', 'x' => 'something')), '9');
render_code ('{{ inc(x) }}', make_slices (array ('inc' => function ($x) { return $x + 1; }, 'x' => 1)), '2');
render_code ('{{ [class, "static_method"]() }}', make_combinations (array ('class' => 'TestClass')), '17');
render_code ('{{ ["TestClass", method]() }}', make_combinations (array ('method' => 'static_method')), '17');
render_code ('{{ method() }}', make_combinations (array ('method' => array ('TestClass', 'static_method'))), '17');
render_code ('{{ obj[method]() }}', make_slices (array ('method' => 'instance_method', 'obj' => new TestClass ())), '42');
render_code ('{{ obj.instance_method() }}', make_combinations (array ('obj' => new TestClass ())), '42');

// Render lambda expressions
render_code ('{{ ((i) => i + 1)(2) }}', make_empty (), '3');
render_code ('{{ ((x, y) => x * y)(3, 5) }}', make_empty (), '15');
render_code ('{{ ((x) => x + y)(2) }}', make_combinations (array ('y' => 3)), '5');

// Render member expressions
render_code ('{{ [1][0] }}', make_empty (), '1');
render_code ('{{ a[0] }}', make_combinations (array ('a' => array (7))), '7');
render_code ('{{ [2, 9, 3][x] }}', make_combinations (array ('x' => 1)), '9');
render_code ('{{ a[x][y] }}', make_combinations (array ('a' => array (0, 0, array (0, 5)), 'x' => 2, 'y' => 1)), '5');
render_code ('{{ obj.instance_field }}', make_combinations (array ('obj' => new TestClass ())), '1');

// Render unary expressions
render_code ('{{ -3 }}', make_empty (), '-3');
render_code ('{{ +3 }}', make_empty (), '3');
render_code ('{{ !2 }}', make_empty (), '');
render_code ('{{ !0 }}', make_empty (), '1');
render_code ('{{ ~0 }}', make_empty (), '-1');
render_code ('{{ ~2 }}', make_empty (), '-3');

// Render mixed expressions
render_code ('{{ 5 + -3 }}', make_empty (), '2');
render_code ('{{ 5 + +3 }}', make_empty (), '8');
render_code ('{{ f[0](s) }}', make_combinations (array ('f' => array ('strlen'), 's' => 'test')), '4');
render_code ('{{ f(s)[0] }}', make_combinations (array ('f' => 'str_split', 's' => 'test')), 't');

// Render for command
render_code ('{% for v in [1, 2, 3] %}{{ v }}{% end %}', make_empty (), '123');
render_code ('{% for k, v in [1, 2, 3] %}{{ k }}:{{ v }}{% end %}', make_empty (), '0:11:22:3');
render_code ('{% for k, v in [1] %}x{% empty %}y{% end %}', make_empty (), 'x');
render_code ('{% for k, v in [] %}x{% empty %}y{% end %}', make_empty (), 'y');

// Render if command
render_code ('{% if 3 %}x{% end %}', make_empty (), 'x');
render_code ('{% if 3 %}x{% else %}y{% end %}', make_empty (), 'x');
render_code ('{% if 4 %}x{% else if 8 %}y{% end %}', make_empty (), 'x');
render_code ('{% if 0 %}x{% else if 4 %}y{% end %}', make_empty (), 'y');
render_code ('{% if 1 %}x{% else if 0 %}y{% else %}z{% end %}', make_empty (), 'x');
render_code ('{% if 0 %}x{% else if 1 %}y{% else %}z{% end %}', make_empty (), 'y');
render_code ('{% if 0 %}x{% else if 0 %}y{% else %}z{% end %}', make_empty (), 'z');
render_code ('{% if 0 %}{% for x in null %}{% end %}{% end %}', make_empty (), '');
render_code ('{% if 1 %}{% else %}{% for x in null %}{% end %}{% end %}', make_empty (), '');

// Render import command
render_code ('{% import template/import_inner.deval %}{% block first %}1{% block second %}2{% end %}', make_combinations (array ('a' => 'A', 'b' => 'B', 'c' => 'C')), 'A1B2C');
render_code ('{% import template/import_outer.deval %}{% end %}', make_combinations (array ('first' => 'x', 'second' => 'y')), '1x2y3');

// Render include command
render_code ('{% include template/include_inner.deval %}', make_combinations (array ('inner_x' => 'x', 'inner_y' => 'y')), 'xy');
render_code ('{% include template/include_outer.deval %}', make_combinations (array ('outer_x' => 'x', 'outer_y' => 'y')), 'xy');
render_code ('{% include ' . dirname (__FILE__) . '/template/include_inner.deval %}', make_combinations (array ('inner_x' => '1', 'inner_y' => '2')), '12');

// Render let command
render_code ('{% let a = 5 %}{{ a }}{% end %}', make_empty (), '5');
render_code ('{% let a = 5, b = 7 %}{{ a }}{{ b }}{% end %}', make_empty (), '57');
render_code ('{% let a = x %}{{ a }}{% end %}', make_combinations (array ('x' => 'test')), 'test');
render_code ('{% let a = x, b = a %}{{ b }}{% end %}', make_combinations (array ('x' => 'test')), 'test');
render_code ('{% let a = b, b = x %}{{ a }}{{ b }}{% end %}', make_combinations (array ('b' => '1', 'x' => '2')), '12');
render_code ('{% let a = x %}{{ a }}{% let a = y %}{{ a }}{% end %}{{ a }}{% end %}', make_combinations (array ('x' => 1, 'y' => 2)), '121');
render_code ('{% let x = a %}{{ x }}{% for x in [a: 2] %}{{ x }}{% end %}{{ x }}{% end %}', make_combinations (array ('a' => 1)), '121');
render_code ('{% for i in [[1, a], [2, b]] %}{{ i[0] }}{% end %}', make_empty (), '12');
render_code ('{% let x = [[1, a], [2, b]] %}{% for i in x %}{{ i[0] }}{% end %}{% end %}', make_empty (), '12');

// Render wrap command
render_code ('{% wrap length %}{{ "Hello!" }}{% end %}', make_builtins ('length'), '6');
render_code ('{% wrap length %}{% wrap group %}{{ [1, 1, 2, 3, 3] }}{% end %}{% end %}', make_builtins ('group', 'length'), '3');
render_code ('{% wrap php ("strtoupper") %}{{ "World" }}{% end %}', make_builtins ('php'), 'WORLD');

// Render files
render_file ('template/member.deval', 'template', make_combinations (array ('x' => 0)), '1337');
render_file ('template/symbol.deval', 'template', make_combinations (array ('x' => 1, 'y' => 2, 'z' => 3)), "123");

// Setup style
$tests = array
(
	'collapse'			=> '1 X2 3Y 4 5',
	'deindent'			=> '1X2  3Y45',
	'preserve'			=> "1\n  X2  3Y\n  4\n5",
	'deindent,collapse'	=> '1X2 3Y45'
);

foreach ($tests as $style => $expect)
{
	$setup = new Deval\Setup ();
	$setup->style = $style;

	render_code ("{{ 1 }}\n  X{{ 2 }}  {{ 3 }}Y\n  {{ 4 }}\n{{ 5 }}", make_empty (), $expect, $setup);
}

// Invoke builtins
render_code ('{{ cat(1, 2) }}', make_builtins ('cat'), '12');
render_code ('{{ cat(1, 2, 3) }}', make_builtins ('cat'), '123');
render_code ('{{ cat("AB", "CD") }}', make_builtins ('cat'), 'ABCD');
render_code ('{{ default(null, 5) }}', make_builtins ('default'), '5');
render_code ('{{ default(0, 5) }}', make_builtins ('default'), '0');
render_code ('{{ default(1, 6) }}', make_builtins ('default'), '1');
render_code ('{{ join(",", cat([1, 2], [3, 4])) }}', make_builtins ('cat', 'join'), '1,2,3,4');
render_code ('{{ join(",", filter([1, 2, 3, 4], (v) => v % 2 == 0)) }}', make_builtins ('filter', 'join'), '2,4');
render_code ('{{ join(",", filter(["a": 1, "b": 2, "c": 3, "d": 4], (v, k) => k < "c")) }}', make_builtins ('filter', 'join'), '1,2');
render_code ('{% let pair = find([2: "two", 4: "four", 6: "six"]) %}{{ pair[0] }}:{{ pair[1] }}{% end %}', make_builtins ('find'), '2:two');
render_code ('{% let pair = find([2: "two", 4: "four", 6: "six"], (v) => v == "four") %}{{ pair[0] }}:{{ pair[1] }}{% end %}', make_builtins ('find'), '4:four');
render_code ('{% let pair = find(["a": 1, "b": 2, "c": 3], (v, k) => k == "b") %}{{ pair[0] }}:{{ pair[1] }}{% end %}', make_builtins ('find'), 'b:2');
render_code ('{{ join(",", flip(["a": 0, "b": 1, "c": 2])) }}', make_builtins ('flip', 'join'),  'a,b,c');
render_code ('{{ join(",", group([1, 1, 3, 3])) }}', make_builtins ('group', 'join'), '1,3');
render_code ('{{ join(",", group([1, 2, 3, 4], (v) => v % 2, (v) => v * 2)) }}', make_builtins ('group', 'join'), '2,4');
render_code ('{{ join(",", group([1, 2, 3, 4], (v) => v % 2, (v) => v * 2, (v1, v2) => v1 + v2)) }}', make_builtins ('group', 'join'), '8,12');
render_code ('{{ join(",", keys([1: 0, 2: 0, 3: 0])) }}', make_builtins ('join', 'keys'), '1,2,3');
render_code ('{{ length("Hello!") }}', make_builtins ('length'), '6');
render_code ('{{ length([7, 8, 9]) }}', make_builtins ('length'), '3');
render_code ('{{ join(",", map([1, 2, 3, 4], (i) => i * 2)) }}', make_builtins ('join', 'map'), '2,4,6,8');
render_code ('{{ php("implode")(",", [1, 2]) }}', make_builtins ('php'), '1,2');
render_code ('{{ php("$_SERVER")["PHP_SELF"] }}', make_builtins ('php'), $_SERVER['PHP_SELF']);
render_code ('{{ php("#PHP_VERSION") }}', make_builtins ('php'), PHP_VERSION);
render_code ('{{ slice("1234", 1) }}', make_builtins ('slice'), '234');
render_code ('{{ slice("1234", 1, 2) }}', make_builtins ('slice'), '23');
render_code ('{{ slice("1234", -3) }}', make_builtins ('slice'), '234');
render_code ('{{ slice("1234", -3, 2) }}', make_builtins ('slice'), '23');
render_code ('{{ join(",", slice([1, 2, 3, 4], 1)) }}', make_builtins ('join', 'slice'), '2,3,4');
render_code ('{{ join(",", slice([1, 2, 3, 4], 1, 2)) }}', make_builtins ('join', 'slice'), '2,3');
render_code ('{{ join(",", slice([1, 2, 3, 4], -3)) }}', make_builtins ('join', 'slice'), '2,3,4');
render_code ('{{ join(",", slice([1, 2, 3, 4], -3, 2)) }}', make_builtins ('join', 'slice'), '2,3');
render_code ('{{ join(",", sort([4, 2, 3, 1])) }}', make_builtins ('join', 'sort'), '1,2,3,4');
render_code ('{{ join(",", split("1:2:3:4", ":")) }}', make_builtins ('join', 'split'), '1,2,3,4');
render_code ('{% let x = [1: "a", 2: "b", 3: "c"] %}{{ join(",", keys(values(x))) }}:{{ join(",", values(x)) }}{% end %}', make_builtins ('join', 'keys', 'values'), '0,1,2:a,b,c');
render_code ('{{ void() }}', make_builtins ('void'), '');
render_code ('{{ when(1 == 1, "OK", "KO") }}', make_builtins ('when'), 'OK');
render_code ('{{ when("A" == "B", "KO", "OK") }}', make_builtins ('when'), 'OK');
render_code ('{% let x = zip(["a", "b", "c"], [0, 1, 2]) %}{{ join(",", keys(x)) }}:{{ join(",", values(x)) }}{% end %}', make_builtins ('join', 'keys', 'values', 'zip'), 'a,b,c:0,1,2');

?>
