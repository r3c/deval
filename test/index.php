<?php

require '../src/language/deval.php';
require '../src/block.php';

function assert_string ($source, $result)
{
	$parser = new PhpPegJs\Parser ();

	//assert ((string)$parser->parse ($source) === $result);
	(string)$parser->parse ($source);
}

assert_string ('lol', 'Plain(lol)');
assert_string ('l{o}l', 'Plain(l{o}l)');
assert_string ('{{ 1 }}', 'Echo(1)');
assert_string ('{{ buffer name }}x{{ end }}', '');
assert_string ('{{ echo 5 }}', '');
assert_string ('{{ if 3 }}x{{ end }}', '');
assert_string ('{{ if 3 }}x{{ else }}y{{ end }}', '');
assert_string ('{{ if 3 }}x{{ else if 4 }}y{{ end }}', '');
assert_string ('{{ if 3 }}x{{ else if 4 }}y{{ else }}z{{ end }}', '');
assert_string ('{{ for k in 3 }}x{{ end }}', '');
assert_string ('{{ for k, v in 3 }}x{{ end }}', '');
assert_string ('{{ for k, v in 3 }}x{{ empty }}y{{ end }}', '');
assert_string ('{{ let a as 5 }}x{{ end }}', '');
assert_string ('{{ let a as 5, b as 7 }}x{{ end }}', '');

?>
