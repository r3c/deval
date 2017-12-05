=================
Template language
=================

Template syntax
===============

Template language in Deval consists in mixed text literal and special statements enclosed within a pair of double braces:

- Everything you type outside from these braces will be rendered without modification except the backslash ``\`` character used for escaping.
- Anything you type within a pair of ``{{`` and ``}}`` will be processed by Deval and produce an output depending on the inner contents.

Most statements start a block that must be ended with a ``{{ end }}`` marker. The contents you put between such block and its corresponding end marker is called "body" and can contain both text literal and other nested statements.

Backslashes can be used to prevent special characters from being interpreted. Write ``\{{`` in a template to print a literal pair of opening braces without having them interpreted as the beginning of a Deval statement, or ``\\`` to print a literal backslash.

C-style comments (between a ``/*`` and ``*/`` pair) are also allowed anywhere in Deval statements.


Statements
==========

Print an expression
-------------------

To print result of an expression ``expr``, use the "$" statement:

.. code-block:: deval

	{{ $ variable }}
	{{ $ "literal" }}
	{{ $ 3 * 5 }}
	{{ $ f(x) + y }}

Printed value will be inserted without modification into current document, meaning you may need to enclose it with an escaping function (e.g. HTML escaping if you're printing an HTML document) to avoid leaking unescaped characters. The :ref:`wrap` statement may be useful in this context.

As expression ``expr`` is transformed to a string before being inserted into current document, make sure it's a primitive type (e.g. boolean, integer) or an object that implements :php:meth:`__toString()` method.

Variable assignment
-------------------

Use "let" statement to declare and assign variables:

.. code-block:: deval

	{{ let name = expr * expr }}{{ $ name }}{{ end }}

	{{ let a = f(x), b = g(a) }}
	    {{ $ a /* will print the result of f(x) */ }}
	    {{ $ b /* will print the result of g(f(x)) */ }}
	{{ end }}

Variables created this way are accessible within the statement body and override any pre-existing variable with the same name until the end of the "let" block. You can declare as many variables as you want with a single "let" statement, and each one of them can depend on the ones created before it.

Conditional branches
--------------------

To conditionally hide a block use the "if" statement:

.. code-block:: deval

	{{ if expr1 }}
	    expr1 is true
	{{ end }}

	{{ if expr2 }}
	    expr2 is true
	{{ elseif expr3 }}
	    expr2 is false but expr3 is true
	{{ else }}
	    both expr2 and expr3 are false
	{{ end }}

Expressions used as conditions in "if" or "elseif" statements are converted to boolean just like in PHP, see `boolean casting`_ in PHP manual for details.

.. _`boolean casting`: http://php.net/manual/language.types.boolean.php#language.types.boolean.casting

Collection enumeration
----------------------

Iterate through a collection's keys and values using the "for" statement:

.. code-block:: deval

	{{ for value in ["a", "b", "c"] }}
	    {{ $ value }}
	{{ end }}

	{{ for key, value in pairs }}
	    key = {{ $ key }}, value = {{ $ value }}
	{{ end }}

Objects can be enumerated as well, as long as they implement the :ref:`traversable` interface. An optional "empty" clause can be added and will be displayed if enumerated collection was empty:

.. _traversable: http://php.net/manual/class.traversable.php

.. code-block:: deval

	{{ for value in collection }}
	    {{ $ value }}
	{{ empty }}
	    Collection is empty.
	{{ end }}

Inclusion & extension
---------------------

Deval offers two styles of template composition mechanism: inclusion and extension.

Inclusion through "include" statement can be used to import contents of another template into current one ; Deval will act as if contents from imported template was copy-pasted to replace the "include" statement itself:

.. code-block:: deval

	{{ include path/to/other/template.deval }}

Extension is a bit more complex and allow reusing the layout of a template while replacing parts of its content. Start by defining an outer template and insert a few "label" statements where you'll want to replace contents:

.. code-block:: deval

	<html>
	    <head>
	        <title>{{ label title }}</title>
	    </head>
	    <body>
	        {{ label body }}
	    </body>
	</html>

Then write another template that will extend first one and define contents for each "label" block:

.. code-block:: deval

	{{ extend outer.deval }}
	    {{ block title }}
	        This is my page title!
	    {{ block body }}
	        And here is some text contents.
	{{ end }}

Contents specified after each block will replace matching "label" block from extended template.

Path specified in both "include" or "extend" blocks are relative to current template. Use backslash ``\`` character to escape any special character or whitespace in your path.

.. _wrap:

Expression wrapping
-------------------

You'll most probably want to escape unsafe values (e.g. user input) before printing their contents from your templates. While this can easily be done by injecting an escaping function and using it to wrap all the expressions you want to print with "$" statements, the "wrap" block provides a nice solution to factorize some code:

.. code-block:: deval

	{{ wrap html }}
	    Every {{ $ expression }} printed within this wrap {{ $ block }} will
	    be passed {{ $ through }} an escaping function.
	{{ end }}

Is equivalent to:

.. code-block:: deval

	Every {{ $ html(expression) }} printed within this wrap {{ $ html(block) }}
	will be passed {{ $ html(through) }} an escaping function.

As long as you inject a function such as :ref:`htmlentities` as variable ``html`` you can be sure nothing inside your "wrap" block is left unescaped.

.. _htmlentities: http://php.net/manual/function.htmlentities.php

Expressions
===========

Literal constants
-----------------

Deval supports following literals in expressions:

- Boolean values e.g. ``true`` or ``false``
- Floating point numbers e.g. ``5.32`` or ``.17``
- Integer numbers e.g. ``0`` or ``42``
- Character strings e.g. ``""`` or ``"hello"`` (double quotes only)
- Values arrays e.g. ``["a", "b", "c"]`` or ``[[1, 2], [3, 4]]``
- Key-values arrays e.g. ``["i": 3, "j": 7]``
- Undefined value aka ``null``

Note only double quotes are accepted for strings, single quotes have no defined meaning in Deval.

Symbol references
-----------------

Variables can be referenced by their name without the usual "$" character found in PHP scripts. Remember "$" in Deval is used to write a print construct instead!

Any variable you inject for compile-time or runtime evaluation is referenced using the same unified syntax.

Function calls
--------------

Invoke functions the same way you do in PHP, passing arguments (if any) between a pair of parenthesis:

.. code-block:: deval

	{{ $ join(":", [1, 2, 3]) }}

Note there is no syntactic difference between variables and functions in Deval as there is in PHP, both don't require a "$" character. Functions behave as variables and can be assigned to symbols, passed as arguments or used as array items just like any regular variable.

Member access
-------------

Access array items or object properties by appending index or property name between square brackets to your expression:

.. code-block:: deval

	{{ $ array[i] }}
	{{ $ dictionary["key"] }}
	{{ $ matrix[x * 5][y + 3] }}

When accessing property with name as a constant string you can replace square brackets and quotes by a single dot:

.. code-block:: deval

	{{ $ dictionary.key }}

Accessing a non-existent member in Deval won't trigger in an error but evaluates to ``null`` instead.

Due to the fact Deval handles names in a different way than PHP it can't make the difference between properties and functions having the same name within some class, nor it cal make the difference between static and instance properties. This means if your obect ``o`` has both a property ``$o->member`` and a method ``$o->member()`` then writing ``o.member`` in a template will lead to undefined result.

Mathematical & logical
----------------------

Following operators can be used in Deval, by order of precedence:

- ``-value``, ``!value``: negate or apply boolean "not" on given value
- ``a * b``, ``a / b``, ``a % b``: multiply, divide or get modulo of operands
- ``a + b``, ``a - b``: add or substract operands
- ``a == b``, ``a != b``, ``a < b``, ``a <= b``, ``a > b``, ``a >= b``: compare operands
- ``a && b``: return ``a`` if ``a`` was equivalent to ``true`` or ``b`` otherwise
- ``a || b``: return ``a`` if ``a`` was equivalent to ``false`` or ``b`` otherwise

Comparison operators are always strict, meaning ``==`` and ``!=`` are equivalent to triple-equal operator in PHP and will consider two values as different when they're of different types. If you want to test equality between a number and its string representation you first need to cast one of the operands (e.g. with one of the :ref:`flavor_deval`).

Boolean "and" and "or" operators are different from the ones found in PHP as they don't return a ``true`` or ``false`` value but one of their operand instead, after checking for their equivalence to true or false using PHP `boolean casting`_ rules. This property allows more than just boolean arithmetics:

.. _`boolean casting`: http://php.net/manual/language.types.boolean.php#language.types.boolean.casting

.. code-block:: deval

	You have {{ $ i }} new message{{ $ i > 1 && "s" }}

Boolean test in the above code will result in ``false`` if ``i`` is lower or equal to ``1`` or ``"s"`` otherwise, printing an "s" after "message" only when needed. Another nice example is this one:

.. code-block:: deval

	{{ $ test && x || y }}

Due to operator precedence the above statement will print the value of ``x`` if both ``test`` and ``x`` are true or ``y`` otherwise. This makes it a close equivalent to a ternary operator having same result except when ``test`` is true but ``x`` is not.

Moment control
--------------

In some rare situations you may want to control whether an expression must be evaluated at compile-time or at runtime. Moment operators ``(+)`` and ``(-)`` offer a solution to this problem by either forcing an expression to be evaluated at compile-time (or raising an error if it can't) or delaying its evaluation to runtime:

.. code-block:: deval

	{{ if (+)state == 1 }}
	    do something
	{{ end }}
	{{ $ ((-)lookup)("something") }}

In this example we force ``state`` to be evaluated at compile-time, meaning its value cannot be left unknown and Deval will be able to either eliminate the test when generating code (because it's known to be successful) or the entire branch otherwise.

In the following statement we prevent the ``lookup`` function from being invoked even if its value is known at compile-time, maybe because it depends on some global context being setup first. Note the use of parenthesis: removing them would cause the operator to apply on the result of the function call instead (because operator precedence is lower than function invoke), which is not what we wanted here.

Lambda definition
-----------------

Deval supports definition of lambda functions:

.. code-block:: deval

	{{ let return_three = () => 3 }}
	    {{ $ return_three() /* will print 3 */ }}
	{{ end }}
	{{ let absolute = (i) => i < 0 && -i || i }}
	    {{ $ absolute(-5) /* will print 5 */ }}
	    {{ $ absolute(7) /* will print 7 */ }}
	{{ end }}
	{{ let sum = (a, b) => a + b }}
	    {{ $ sum(3, 8) /* will print 11 */ }}
	{{ end }}

Lambda functions can also capture variables from surrounding context (closure):

.. code-block:: deval

	{{ let
	    upper_limit = 3,
	    pair = find (items, (i) => i < upper_limit) }}
	    index = {{ $ pair[0] }}, value = {{ $ pair[1] }}
	{{ end }}

Given a ``find`` function like the one available in :ref:`flavor_deval` and an ``items`` array of integers, this code would search for the first item lower than 3 and print its index and value.

Options
=======

Deval configuration can be modified through an optional :php:class:`Deval\\Setup` parameter passed when creating an instance of :php:class:`Deval\\CacheRenderer` or equivalent:

.. code-block:: php

	<?php

	require 'lib/deval/deval.php';

	$setup = Deval\Setup();
	$setup->style = 'deindent'; // Change some option

	$renderer = new Deval\CacheRenderer('template/users.deval', 'cache/', $setup);

Following sections list available options.

.. _whitespace:

Whitespace control
------------------

The way Deval handles whitespaces in the literal text parts of your templates can be modified through the :php:attr:`Deval\\Setup::$style` property. You can use some of Deval predefined styles or pass a PHP function to define your own. Use ',' as a separator between names if you want to apply more than one Deval predefined style:

.. code-block:: php

	<?php

	[ ... ]

	$setup->style = 'deindent'; // Use predefined style 'deindent'
	// or
	$setup->style = 'deindent,collapse'; // Use 'deindent' then 'collapse' ; good choice when generating HTML
	// or
	$setup->style = function ($s) { return trim ($s); }; // Use custom function

Predefined styles are:

- deindent: remove a line break character (\\n) followed by whitespaces at the beginning and end of each literal text block. This style allows you to indent your template code without adding unwanted blank characters into generated code. As a side-effect you may experience blocks being collapsed when you want to preserve some whitespaces ; this can be fixed by either adding additional line breaks or inserting spaces through Deval statements e.g. ``{{ $ " " }}`` that won't be removed.
- collapse: replace any sequence of one or more whitespaces by a single one. This setting is useful to generate a more compact output when targeting a language that ignores repeated whitespaces such as HTML.
- preserve: do not remove nor replace any whitespace from template literals.

Default value of style option is 'deindent'.

.. _version:

PHP compatibility
-----------------

Use the :php:attr:`Deval\\Setup::$version` property to change target PHP compatibility version. All PHP versions above 7 unlocked some constructs that were not possible with previous versions, meaning some less effective workarounds need to be use when targeting one of these.

Default value of this option is current executing PHP version (obtained through ``PHP_VERSION`` constant), meaning you should not have to touch this option except in rare situations if you use Deval to generate and execute code that don't run using the same PHP version.
