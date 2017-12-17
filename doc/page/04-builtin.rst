=================
Builtin functions
=================

.. _functional:

Functional constraint
=====================

Deval's ability to evaluate code at compilation or delay it to runtime relies on template code not having any side-effect. Side-effects would make evaluation not predictable by depending on inputs that Deval have no control on, and therefore could break your templates by producing different results depending on when or in which order functions are evaluated. This is why the template language and all :ref:`flavor_deval` are :ref:`pure`.

.. _pure: https://en.wikipedia.org/wiki/Pure_function

Deval has no way to make sure functions you inject are pure, so this responsibility is left to the developer. Remember it's technically possible to inject non-pure functions into a Deval template but you'll most probably break something if you do so, unless you really know what you're doing.


List of builtin functions
=========================

Deval offers two predefined "flavors" of builtin functions you can inject:

- Preferred "deval" flavor: a minimal set of pure functions you can safely use in your templates without any risk of unexpected behavior ;
- Alternative "php" flavor: all standard PHP functions made directly available in your templates. Make sure you only use the pure ones, otherwise you may experience unreliable results as explained above.

To inject builtin functions into your template, use the same :php:meth:`Deval\\Renderer::inject` method we saw in previous sections:

.. code-block:: php

	// Inject "deval" flavor builtin functions into your template
	$renderer->inject(Deval\Builtin::deval());

	// or

	// Inject "php" flavor builtin functions into your template
	$renderer->inject(Deval\Builtin::php());

.. _`flavor_deval`:

Deval flavor functions
----------------------

Following functions are available in :php:meth:`Deval\\Builtin::deval` flavor:

.. py:function:: array(value1[, value2[, ...]])

	Convert parameters into arrays and merge them together. This function can be used to cast any value to an array (equivalent to PHP's ``(array)`` cast) and/or concatenate several values as a single array.

	:param mixed valueN: any value
	:return: concatenated parameters $valueN after converting them to arrays

.. py:function:: bool(value)

	Convert input value into boolean, just like a PHP boolean cast would do.

	:param mixed value: any value
	:return: input value converted to boolean

.. py:function:: cat(value1[, value2[, ...]])

	Concatenate arrays or strings (all arguments will be treated as arrays or strings depending on the type of first argument).

	:param mixed valueN: string or array
	:return: concatenated values

.. py:function:: default(value, fallback)

	Shorthand function to test whether a value is defined and not null.

	:param mixed value: input value
	:return: ``value`` if defined and not null, ``fallback`` otherwise

.. py:function:: filter(items[, predicate])

	Filter items from an array based on a predicate. If predicate is not specified then ``(item) => bool(item)`` is used, meaning function will return an array with all items which are equivalent to true using PHP `boolean casting`_ rules.

	:param any_array items: input items
	:param function predicate: predicate callback
	:return: array of all items for which ``predicate(item)`` is true

.. _`boolean casting`: http://php.net/manual/language.types.boolean.php#language.types.boolean.casting

.. py:function:: find(items[, predicate])

	Find first item from an array matching given predicate. If predicate is not specified then ``(item) => true`` is used, meaning function will return first item from the array.

	:param any_array items: input items
	:param function predicate: predicate callback
	:return: first item from array for which ``predicate(item)`` is true

.. py:function:: flip(items)

	Return an array where keys and values have been swapped (similar to PHP function `array_flip`_).

	:param any_array items: input items
	:return: array with swapped keys and values

.. _`array_flip`: http://php.net/manual/function.array-flip.php

.. py:function:: float(value)

	Convert input value into floading point number, just like a PHP float cast would do.

	:param mixed value: any value
	:return: input value converted to floating point number

.. py:function:: group(items[, get_key[, get_value[, merge]]])

	Group array items together, optionally transforming keys and values and handling key collisions using callback functions. This function will process every key and value from input array and apply specified ``get_key`` and ``get_value`` callbacks on them, passing them ``value`` and ``key`` as arguments. Resulting key and value are inserted into output array, using ``merge`` callback to resolve conflict when two values share the same key and passing it both previous and current value as arguments.

	This very versatile function can be used in multiple situations depending on the callback you specify. For example when used with default callbacks it will act as a "unique" function and remove duplicates, by using values as keys and solving conflicts by keeping first encountered value.

	:param any_array items: input items
	:param function get_key: key transform callback, returns ``value`` if not specified
	:param function get_value: value transform callback, returns ``value`` if not specified
	:param function merge: merge conflict handling callback, returns previous value if not specified
	:return: grouped array

.. py:function:: int(value)

	Convert input value into integer number, just like a PHP int cast would do.

	:param mixed value: any value
	:return: input value converted to integer number

.. py:function:: join(items[, separator])

	Join array items together in a string using an optional separator (similar to PHP function implode_).

	:param any_array items: input items
	:param string separator: separator, empty string is used if undefined
	:return: joined array items as a single string

.. _implode: http://php.net/manual/function.implode.php

.. py:function:: keys(items)

	Extract keys from array and make another array out of them (similar to PHP function `array_keys`_).

	:param any_array items: input items
	:return: input item keys

.. _`array_keys`: http://php.net/manual/function.array-keys.php

.. py:function:: length(value)

	Return length of an array (number of items) or a string (number of characters).

	:param mixed value: input array or string
	:return: length of input value

.. py:function:: map(items, apply)

	Returns an array after applying given callback to all its values, leaving keys unchanged (similar to PHP function `array_map`_).

	:param any_array items: input items
	:return: array of (key, apply(value)) pairs

.. _`array_map`: http://php.net/manual/function.array-map.php

.. py:function:: max(value1[, value2[, ...]])

	Returns highest value in given array when given a single argument, or highest argument when given more than one (similar to PHP function `max <http://php.net/manual/function.max.php>`_).

	:param mixed valueN: array (if one argument) or scalar value (if more)
	:return: greatest value or argument

.. py:function:: min(value1[, value2[, ...]])

	Returns lowest value in given array when given a single argument, or lowest argument when given more than one (similar to PHP function `min <http://php.net/manual/function.min.php>`_).

	:param mixed valueN: array (if one argument) or scalar value (if more)
	:return: lowest value or argument

.. py:function:: php(symbol)

	Access PHP global variable, constant or function by name. Prepend "#" to name to access a constant or "$" to access a variable. Class members can be accessed by prepending their namespace followed by "::" to the symbol name. This function allows you to escape from a safe pure context, so all precautions listed in :ref:`functional` section apply to it.

	:param string symbol: name of the symbol to access
	:return: symbol value

.. code-block:: deval

	{{ $ php("implode")(",", [1, 2]) /* access PHP function */ }}
	{{ $ php("#PHP_VERSION") /* access PHP constant */ }}
	{{ $ php("$_SERVER")["PHP_SELF"] /* access PHP variable */ }}
	{{ $ php("My\\SomeClass::$field") /* access class variable */ }}
	{{ $ php("OtherClass::#VALUE") /* access class constant */ }}

.. py:function:: range(start, stop[, step])

	Build a sequence of numbers between given boundaries (inclusive), using a step increment between each value (similar to PHP function range_).

	:param integer start: first value of the sequence
	:param integer stop: last value of the sequence
	:param integer step: increment between numbers, 1 will be used in not specified
	:return: sequence array

.. _range: http://php.net/manual/function.range.php

.. py:function:: reduce(items, callback[, initial])

	Reduce array items to a scalar value using a callback function (similar to PHP function `array_reduce`_).

	:param any_array items: input items
	:param function callback: callback function producing result from aggregated value and current item value
	:param mixed initial: value used as initial aggregate, ``null`` if not specified
	:return: final aggregated value

.. _`array_reduce`: http://php.net/manual/function.array-reduce.php

.. py:function:: replace(value, replacements)

	Replace all occurrences of ``replacements`` keys by corresponding values (similar to PHP function `str_replace`_ but takes a single key-value array for replacements instead of two separate arrays).

	:param string value: original string
	:param any_array replacements: replacements key-value pairs
	:return: string with all keys from ``replacements`` replaced

.. _`str_replace`: http://php.net/manual/function.str-replace.php

.. py:function:: slice(value, offset[, count])

	Extract delimited slice from given array or string starting at given offset.

	:param mixed value: input array or string
	:param integer offset: beginning offset of extracted slice
	:param integer count: length of extracted slice, or extract to the end if not specified
	:return: extracted array or string slice

.. py:function:: sort(items[, compare])

	Sort input array using optional comparison callback.

	:param any_array items: input items
	:param function callback: items comparison function, see usort_ for specification
	:return: sorted array

.. _usort: http://php.net/manual/function.usort.php

.. py:function:: split(string, separator[, limit])

	Split string into array using a separator string (similar to PHP function explode_).

	:param string string: input string
	:param string separator: separator string
	:param integer limit: maximum number of items in output array
	:return: array of split strings

.. _explode: http://php.net/manual/function.explode.php

.. py:function:: str(value)

	Convert input value into string, just like a PHP string cast would do.

	:param mixed value: any value
	:return: input value converted to string

.. py:function:: values(items)

	Extract values from array and make another array out of them (similar to PHP function `array_values`_).

	:param any_array items: input items
	:return: input item values

.. _`array_values`: http://php.net/manual/function.array-values.php

.. py:function:: void()

	Empty function which always returns ``null``, for use as a default placeholder in Deval statements.

	:return: null

.. py:function:: zip(keys, values)

	Create a key-value array from given list of keys and values (similar to PHP function `array_combine`_). Input arrays ``keys`` and ``values`` must have the same length for this function to work properly.

	:param any_array keys: items to be used as array keys
	:param any_array values: items to be used as array values
	:return: key-value array

.. _`array_combine`: http://php.net/manual/function.array-combine.php

.. _`flavor_php`:

PHP flavor functions
--------------------

If you chose to use :php:meth:`Deval\\Builtin::php` flavor, all standard PHP functions are available in your templates. Proceed with caution! Using any non-pure function e.g. rand_ could make your template unreliable as you don't control when exactly it's going to be called nor how many times.

.. _rand: http://php.net/manual/function.rand.php

.. code-block:: deval

	{{ if strlen(input) == 0 }}
	    Please enter a non-empty value!
	{{ end }}
