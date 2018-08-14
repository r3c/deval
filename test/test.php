<?php

/*
** Generate all possible combinations of $n elements where $k elements are
** equal to "true" and $n - $k are equal to "false".
** $k:		number of expected "true" elements
** $n:		total number of elements
** return:	array of combinations
*/
function combinations ($k, $n, $candidates = array ())
{
	if ($n === 0)
		return array ($candidates);
	else if ($k > $n)
		return array ();
	else if ($k === $n)
		return array (array_merge ($candidates, array_fill (0, $n, true)));
	else if ($k === 0)
		return array (array_merge ($candidates, array_fill (0, $n, false)));
	else
		return array_merge (combinations ($k - 1, $n - 1, array_merge ($candidates, array (true))), combinations ($k, $n - 1, array_merge ($candidates, array (false))));
}

/*
** Generate all possible (constants, variables) partitions pairs with given
** builtin functions.
** *:		builtin function names
** return:	(constants, variables) pairs array
*/
function make_builtins ()
{
	return make_combinations (array_intersect_key (Deval\Builtin::deval (), array_flip (func_get_args ())));
}

/*
** Generate all possible (constants, variables) partition pairs from given
** pairs array.
** $pairs:	key => value pairs array
** return:	(constants, variables) pairs array
*/
function make_combinations ($pairs)
{
	$results = array ();

	for ($i = count ($pairs); $i >= 0; --$i)
	{
		foreach (combinations ($i, count ($pairs)) as $combination)
		{
			$constants = array ();
			$variables = array ();

			foreach (array_keys ($pairs) as $j => $key)
			{
				if ($combination[$j])
					$constants[$key] = $pairs[$key];
				else
					$variables[$key] = $pairs[$key];
			}

			$results[] = array ($constants, $variables);
		}
	}

	return $results;
}

/*
** Return a single element array of (empty, empty) pair.
** return:	array with empty pair
*/
function make_empty ()
{
	return array (array (array (), array ()));
}

/*
** Wrap pairs in a ((constants, empty), (empty, variables)) array.
** $pairs:	key => value pairs array
** return:	two-pairs array
*/
function make_slices ($pairs)
{
	return array (array ($pairs, array ()), array (array (), $pairs));
}

/*
** Compare exception message and search for expected content.
** $exception:	raised exception
** $expect:		expected message
*/
function raise ($exception, $expect)
{
	$message = $exception->getMessage ();

	assert (strpos ($message, $expect) !== false, 'should have raised exception with message "' . $expect . '" but was "' . $message . '"');
}

/*
** Ensure source code throw compile exception when generated with given constants.
** $source:		template source code
** $constants:	injected constants
** $message:	expected exception message
*/
function raise_compile ($source, $constants, $message)
{
	$renderer = new Deval\StringRenderer ($source);

	try
	{
		$renderer->inject ($constants);
		$renderer->render ();

		assert (false, 'should have raised exception when compiling');
	}
	catch (Deval\CompileException $exception)
	{
		raise ($exception, $message);
	}
}

/*
** Ensure source code throw parse exception when scanned.
** $source:		template source code
** $line:		expected error line
** $column:		excepted error column
** $message:	expected exception message
*/
function raise_parse ($source, $line, $column, $message)
{
	try
	{
		$renderer = new Deval\StringRenderer ($source);

		assert (false, 'should have raised exception when parsing');
	}
	catch (Deval\ParseException $exception)
	{
		raise ($exception, $message);

		assert ($exception->location->line === $line, 'parse error should have been located at line ' . $line . ' but was ' . $exception->location->line);
		assert ($exception->location->column === $column, 'parse error should have been located at column ' . $column . ' but was ' . $exception->location->column);
	}
}

/*
** Ensure source code throw resolve exception after parsing.
** $source:		template source code
** $message:	expected exception message
*/
function raise_resolve ($source, $message)
{
	try
	{
		$renderer = new Deval\StringRenderer ($source);

		assert (false, 'should have raised exception when resolving');
	}
	catch (Deval\ResolveException $exception)
	{
		raise ($exception, $message);
	}
}

/*
** Ensure source code throw render exception when injected given constants and
** variables.
** $source:		template source code
** $constants:	injected constants
** $variables:	injected variables
** $message:	expected exception message
*/
function raise_render ($source, $constants, $variables, $message)
{
	$renderer = new Deval\StringRenderer ($source);
	$renderer->inject ($constants);

	try
	{
		$renderer->render ($variables);

		assert (false, 'should have raised exception when rendering');
	}
	catch (Deval\RenderException $exception)
	{
		raise ($exception, $message);
	}
}

/*
** Ensure source code throw setup exception when initialized.
** $setup:		compiler setup
** $message:	expected exception message
*/
function raise_setup ($setup, $message)
{
	$renderer = new Deval\StringRenderer ('', $setup);

	try
	{
		$renderer->render (array ());

		assert (false, 'should have raised exception when setting up');
	}
	catch (Deval\SetupException $exception)
	{
		raise ($exception, $message);
	}
}

/*
** Run tests using given renderers constructor and set of
** (constants, variables) pairs array.
** $constructor:	renderers constructor
** $pairs:			(constants, variables) pairs array
** $expect:			expected rendered string
*/
function render ($constructor, $pairs, $expect)
{
	foreach ($pairs as $pair)
	{
		list ($constants, $variables) = $pair;

		foreach ($constructor () as $renderer)
		{
			$renderer->inject ($constants);

			$names_expect = array_keys ($variables);
			$names_result = array ();

			$renderer->source ($names_result);

			assert (count (array_diff ($names_result, $names_expect)) === 0, 'invalid detected variables: ' . var_export ($names_result, true) . ' !== ' . var_export ($names_expect, true));

			$result = $renderer->render ($variables);

			assert ($result === $expect, 'invalid rendered output: ' . var_export ($result, true) . ' !== ' . var_export ($expect, true));
		}
	}
}

/*
** Run tests on code-based renderers using given source code and set of
** (constants, variables) pairs array.
** $source:	template source code
** $pairs:	(constants, variables) pairs array
** $expect:	expected rendered string
** $setup:	compiler setup
*/
function render_code ($source, $pairs, $expect, $setup = null)
{
	render (function () use ($setup, $source)
	{
		return array
		(
			new Deval\StringRenderer ($source, $setup)
		);
	}, $pairs, $expect, $setup);
}

/*
** Run tests on file-based renderers using given template path and set of
** (constants, variables) pairs array.
** $path:		template file path
** $directory:	cache directory
** $pairs:		(constants, variables) pairs array
** $expect:		expected rendered string
** $setup:	compiler setup
*/
function render_file ($path, $directory, $pairs, $expect, $setup = null)
{
	render (function () use ($directory, $path, $setup)
	{
		return array
		(
			new Deval\CacheRenderer ($path, $directory, $setup, true),
			new Deval\FileRenderer ($path, $setup)
		);
	}, $pairs, $expect, $setup);
}

function source ($constructor, $constants, $expects)
{
	foreach ($constructor () as $renderer)
	{
		$renderer->inject ($constants);
		$source = $renderer->source ($requires);

		foreach ($expects as $pattern => $expect)
		{
			$count = preg_match_all ($pattern, $source, $matches);

			assert ($count === $expect, 'expected ' . $expect . ' matches for pattern ' . $pattern . ' but found ' . $count . ' in ' . var_export ($source, true));
		}
	}
}

function source_code ($source, $constants, $expects, $setup = null)
{
	source (function () use ($setup, $source)
	{
		return array
		(
			new Deval\StringRenderer ($source, $setup)
		);
	}, $constants, $expects, $setup);
}

?>
