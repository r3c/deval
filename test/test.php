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
** Wrap variables in a single-pair (constants, empty) array.
** $variables:	key => value variables array
** return:		(constants, empty) single-pair array
*/
function make_constants ($variables)
{
	return array (array ($variables, array ()));
}

/*
** Run tests on code-based renderers using given source code and set of
** (constants, volatiles) variable pairs.
** $source:	template source code
** $pairs:	(constants, volatiles) variable pairs
** $expect:	expected rendered string
*/
function render_code ($source, $pairs, $expect)
{
	render (function () use ($source)
	{
		return array
		(
			new Deval\StringRenderer ($source)
		);
	}, $pairs, $expect);
}

/*
** Run tests on file-based renderers using given template path and set of
** (constants, volatiles) variable pairs.
** $directory:	caching directory
** $path:		template file path
** $pairs:		(constants, volatiles) variable pairs
** $expect:		expected rendered string
*/
function render_file ($directory, $path, $pairs, $expect)
{
	render (function () use ($directory, $path)
	{
		return array
		(
			new Deval\CachedRenderer ($directory . DIRECTORY_SEPARATOR . $path, $directory, true),
			new Deval\FileRenderer ($directory . DIRECTORY_SEPARATOR . $path)
		);
	}, $pairs, $expect);
}

/*
** Run tests using given renderers builder and set of (constants, volatiles)
** variable pairs.
** $create:	renderers builder
** $pairs:	(constants, volatiles) variable pairs
** $expect:	expected rendered string
*/
function render ($create, $pairs, $expect)
{
	foreach ($pairs as $pair)
	{
		list ($constants, $volatiles) = $pair;

		foreach ($create () as $renderer)
		{
			$renderer->inject ($constants);

			$result = $renderer->render ($volatiles, 'collapse');

			assert ($result === $expect, var_export ($result, true) . ' !== ' . var_export ($expect, true));
		}
	}
}

?>
