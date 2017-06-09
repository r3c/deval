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
** return:	(constants, volatiles) pairs array
*/
function make_builtins ()
{
	return make_combinations (array_intersect_key (Deval\Builtin::deval (), array_flip (func_get_args ())));
}

/*
** Generate all possible (constants, volatiles) partition pairs from given
** variables array.
** $variables:	key => value variables array
** return:		(constants, volatiles) pairs array
*/
function make_combinations ($variables)
{
	$results = array ();

	for ($i = count ($variables); $i >= 0; --$i)
	{
		foreach (combinations ($i, count ($variables)) as $combination)
		{
			$constants = array ();
			$volatiles = array ();

			foreach (array_keys ($variables) as $j => $key)
			{
				if ($combination[$j])
					$constants[$key] = $variables[$key];
				else
					$volatiles[$key] = $variables[$key];
			}

			$results[] = array ($constants, $volatiles);
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
** Wrap variables in a ((constants, empty), (empty, volatiles)) array.
** $variables:	key => value variables array
** return:		two-pairs array
*/
function make_slices ($variables)
{
	return array (array ($variables, array ()), array (array (), $variables));
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
** Ensure source code throw compile exception when compiled.
** $source:		template source code
** $message:	expected exception message
*/
function raise_compile ($source, $message)
{
	try
	{
		$renderer = new Deval\StringRenderer ($source);

		assert (false, 'should have raised exception');
	}
	catch (Deval\CompileException $exception)
	{
		raise ($exception, $message);
	}
}

/*
** Ensure source code throw inject exception when injected given constants.
** $source:		template source code
** $constants:	injected constants
** $message:	expected exception message
*/
function raise_inject ($source, $constants, $message)
{
	$renderer = new Deval\StringRenderer ($source);

	try
	{
		$renderer->inject ($constants);

		assert (false, 'should have raised exception');
	}
	catch (Deval\InjectException $exception)
	{
		raise ($exception, $message);
	}
}

/*
** Run tests using given renderers constructor and set of
** (constants, volatiles) variable pairs.
** $constructor:	renderers constructor
** $pairs:			(constants, volatiles) variable pairs
** $expect:			expected rendered string
*/
function render ($constructor, $pairs, $expect)
{
	foreach ($pairs as $pair)
	{
		list ($constants, $volatiles) = $pair;

		foreach ($constructor () as $renderer)
		{
			$renderer->inject ($constants);

			$names_expect = array_keys ($volatiles);
			$names_result = array ();

			$renderer->source ($names_result);

			assert (count (array_diff ($names_result, $names_expect)) === 0, 'invalid detected volatiles: ' . var_export ($names_result, true) . ' !== ' . var_export ($names_expect, true));

			$result = $renderer->render ($volatiles);

			assert ($result === $expect, 'invalid rendered output: ' . var_export ($result, true) . ' !== ' . var_export ($expect, true));
		}
	}
}

/*
** Run tests on code-based renderers using given source code and set of
** (constants, volatiles) variable pairs.
** $source:	template source code
** $pairs:	(constants, volatiles) variable pairs
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
** (constants, volatiles) variable pairs.
** $path:		template file path
** $directory:	caching directory
** $pairs:		(constants, volatiles) variable pairs
** $expect:		expected rendered string
** $setup:	compiler setup
*/
function render_file ($path, $directory, $pairs, $expect, $setup = null)
{
	render (function () use ($directory, $path, $setup)
	{
		return array
		(
			new Deval\CachedRenderer ($path, $directory, $setup, true),
			new Deval\FileRenderer ($path, $setup)
		);
	}, $pairs, $expect, $setup);
}

?>
