===============
Getting started
===============


Install the library
===================

You can choose to download lastest Deval release or build it from source.

Option 1: download latest release
---------------------------------

Download `latest Deval release`_, unzip it somewhere inside your project directory and skip to next section to see how to import Deval into your project.

.. _`latest Deval release`: https://github.com/r3c/deval/releases/download/1.0.0/deval-1.0.0.zip

Option 2: build from source
---------------------------

You'll need following requirements to build Deval from source:

- PHP_ 5.6.0 or above
- Node.js_ 6.11.0 or above

.. _PHP: http://php.net/
.. _Node.js: https://nodejs.org/

Install Node.js & npm, clone Deval repository, browse to cloned directory and run ``npm install`` command to install dependencies and build:

.. code-block:: sh

	$ git checkout https://github.com/r3c/deval.git
	$ cd deval
	$ npm install

Once build, copy content of the ``src`` folder somwhere in your project and move to next section.


Import into your project
========================

Require file ``deval.php`` file to start using the library ; here is a minimal code example that shows most common features. Save the snippet below into some ``sample.php`` file, we'll dig into details about each line in next sections:

.. code-block:: php

	<?php

	require 'lib/deval/deval.php';

	// Create a new renderer for template file 'template/users.deval' and use
	// directory 'cache' to store pre-evaluated intermediate results (your web
	// user must have read/write permission on this directory)
	$renderer = new Deval\CacheRenderer('template/users.deval', 'cache/');

	// Inject some standard functions into template e.g. 'find', 'sort', etc.
	$renderer->inject(Deval\Builtin::deval());

	// Inject compile-time constants that will be used for pre-evaluation. Only
	// serializable values can be used here, see API documentation about the
	// inject method for more details
	$renderer->inject(array('language' => 'en-us'));

	// Inject render-time variables and print rendered output
	echo $renderer->render(array('users' => array('Jane', 'John')));

In this example we created an instance of :php:class:`Deval\\CacheRenderer` which is a production renderer able to cache pre-evaluated code for optimal performance. You may also want to put performance aside, for example when developing your project, and use a :php:class:`Deval\\FileRenderer` instead that won't cache anything and reprocess your templates on each call, or even a :php:class:`Deval\\StringRenderer` that will let you specify your template code as a string instead of reading it from a file:

.. code-block:: php

	// Create a simple file-based renderer with no caching optimization
	$renderer = new Deval\FileRenderer('template/users.deval');

	// Create a string-based renderer, still with no caching
	$renderer = new Deval\StringRenderer($my_template_code);

.. _builtin:

By default you don't have access to PHP functions from within a template (we'll explain why exactly in :ref:`functional` section), which is why we added a first call to method :php:meth:`Deval\\Renderer::inject` in previous example. It's here to inject a few common functions in our template and make them available before compilation to enable many early optimizations. A full list of functions injected from :php:meth:`Deval\\Builtin::deval` can be found in the
:ref:`functions` section.


Write a template
================

Now you're ready for writing a template. Create a new text file in your favorite editor and type in some contents:

.. code-block:: deval

	Note: this page should be displayed using {{ $ language }} locale.
	
	Users list: {{ $ join(", ", users) }}

Save this file as ``template/users.deval`` (relative to your previously created ``sample.php`` file) to match the name we used in previous example. Don't forget to create a ``cache/`` directory to store pre-evaluated results and browse to your ``sample.php`` file. Result should look like this:

.. code-block:: plain

	Note: this page should be displayed using en-us locale.
	
	Users list: Jane, John

As you can guess the ``join`` function we used in our template is one of the builtin ones we mentionned earlier, and is similar to PHP's standard implode_ function.

.. _implode: http://php.net/manual/function.implode.php

One last note before jumping into more details: if you're curious you can have a look at the content of your ``cache/`` folder, it should now contain a ``.php`` file generated from your template. Edit this file and see the note about page locale includes a literal ``en-us`` part which has been pre-evaluated since it was injected as a constant. The ``users`` variable however still exists and is expected to be provided at rendering. If you change your sample code and switch language value to ``"fr-fr"`` (or anything different from ``"en-us"``) then display the page again, you'll see a second generated file appearing in ``cache/`` folder to store this second pre-evaluated variant of your template.

Now you have all the basics, continue to next section to read about language syntax and how to write real-life templates.
