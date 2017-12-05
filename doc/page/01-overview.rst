========
Overview
========


What is Deval?
==============

Deval is a PHP template engine with support for partial evaluation at compilation to enable early error detection, optimize generated code and improve execution performance.

This user manual assumes you already know what a template engine is. Read more about `template processors on Wikipedia`__ if you want detailed information on this topic.

.. __: https://en.wikipedia.org/wiki/Template_processor


What is partial evaluation?
===========================

While most PHP template engines follow a "load variables and render" workflow Deval introduces an intermediate pre-compilation injection step to specify variables that you know won't change on every rendering. By doing so, Deval will pre-evaluate your template and generate specialized code where all these invariants have been evaluated.

Template rendering workflow with Deval looks like this:

.. code-block:: php

   <?php

   // Inject compile-time constants that don't change from one request to
   // another. After this step Deval can pre-evaluate your template and store
   // compiled result in its cache to save some processing power on next
   // requests that will depend on the same set of constants
   $renderer->inject($constants);

   // Inject render-time variables into pre-processed template
   $output = $renderer->render($variables);

   // Print rendered result
   echo $output;

Deval will maintain a pre-evaluated version of each template file you use for every combination of constants you specify. For example if you chose to inject a ``$language`` constant into your template and use it to build localized strings Deval will cache a specialized version of your template for each language you support where strings have been pre-resolved.

Passing a value at compilation or rendering is a decision you'll take based on how many different values it can take and by how much pre-evaluating it improves performance. Deval offers a unified template syntax for all variables regardless of whether they've been specified at compilation or rendering, so you can change your mind without touching tour template code.


A simple template example
=========================

Let's keep out last example and pass current language as a constant to remove cost of strings localization from rendering. Out first simple template could look like this:

.. code-block:: deval

	{{ $ locale(language, "users.list") }}
	{{ for user in users }}
	    - {{ $ user.login }}
	{{ empty }}
	    {{ $ locale(language, "no.users") }}
	{{ end }}

We'll see about template syntax later and for now you only need to know about these two constructs:

- ``{{ $ expression }}`` prints the result of ``expression`` after evaluating
  it ;
- ``{{ for i in c }}...{{ empty }}...{{ end }}`` is a foreach-like loop.

Ater injecting a ``locale`` function and a ``language`` string as constants,
Deval will compile and cache a PHP snippet similar to this one:

.. code-block:: php

	Registered user:
	<?php $_counter = 0; foreach ($users as $user) { ?>
	    - <?php echo $user->login; ?>
	<?php ++$_counter; } if ($_counter === 0) { ?>
	    No users registered.
	<?php } ?>

As you can see all statements depending on variables ``locale`` and ``language`` have been evaluated in generated code, as their value was known at compile time. Other variables have been left untouched and Deval expects you to specify their value when rendering the template (and will raise an error if you don't).


Safety & performance
====================

Deval keeps track of variables referenced in your template and will detect uninitialized ones before rendering anything, as opposed to the usual runtime error detection you get with native PHP or some template engines. While this is not a perfect static error detection solution (for example Deval can't detect runtime type errors) it's a good compromise between safety and ease of writing.

The compile-time evaluation mentioned in previous section also applies to every constant expression declared in your templates, meaning you won't get any performance penalty for factorizing code. In the following template code example:

.. code-block:: deval

	{{ for i in range(0, 4) }}
	    Rank {{ $ i + 1 }} / 5: {{ $ players[i].name }}
	{{ end }}

Deval will unroll the "for" loop as it depends only on known values and compile a PHP snippet equivalent to this one:

.. code-block:: php

	Rank 1 / 5: <?php echo $players[0]->name; ?>
	Rank 2 / 5: <?php echo $players[1]->name; ?>
	Rank 3 / 5: <?php echo $players[2]->name; ?>
	Rank 4 / 5: <?php echo $players[3]->name; ?>
	Rank 5 / 5: <?php echo $players[4]->name; ?>

You shouldn't worry about this when writing template code as Deval will take care of pre-evaluating as much code as possible with the information it as been given.
