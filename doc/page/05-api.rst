=============
API reference
=============

Renderer
========

Common interface
----------------

.. php:namespace:: Deval

.. php:class:: Renderer

	Main Deval class used to compile and render template after injecting values to it.

	.. php:method:: inject($constants)

		Inject values into template. **Only serializable values can be injected** as Deval may need to store them in intermediate document. If you need to inject functions, give them a name (do not use anonymous functions) and inject this name into Deval.

		:param array $constants: key-value array to inject into template, each value will be injected with name taken from its associated key

	.. php:method:: render($variables = array())

		Evaluate and render template after optionally specifying runtime values. Any value, serializable or not, can be passed to this method. If you need to inject method, pass an array with class name (for static methods) or instance (for instance methods) as first item and method name as second item.

		:param array $variables: key-value array to inject into template, similar to the one from :php:meth:`Deval\\Renderer::inject`
		:returns: evaluated and rendered template as a string

Implementations
---------------

.. php:namespace:: Deval

.. php:class:: CacheRenderer

	Implementation for production usage, using an intermediate cache for pre-evaluated template code.

	.. php:method:: _construct($path, $directory, $setup = null)

		Create a new :php:class:`Deval\\CacheRenderer` instance.

		:param string $path: path to input template file
		:param string $directory: directory where cached files will be stored
		:param Deval\\Setup $setup: renderer configuration

.. php:class:: FileRenderer

	Implementation for development usage, perform rendering from template file on each request with no caching.

	.. php:method:: _construct($path, $setup = null)

		Create a new :php:class:`Deval\\FileRenderer` instance.

		:param string $path: path to input template file
		:param Deval\\Setup $setup: renderer configuration

.. php:class:: StringRenderer

	Implementation for development usage, perform rendering from template string on each request with no caching.

	.. php:method:: _construct($source, $setup = null)

		Create a new :php:class:`Deval\\StringRenderer` instance.

		:param string $source: template source code
		:param Deval\\Setup $setup: renderer configuration


Builtin
=======

.. php:namespace:: Deval

.. php:class:: Builtin

	Utility class providing different "flavors" of builtin functions.

	.. php:method:: deval()

		Returns Deval favor builtin functions, see :ref:`flavor_deval` for
		details.

		:returns: functions array

	.. php:method:: php()

		Returns PHP  favor builtin functions, see :ref:`flavor_php` for details.

		:returns: functions array


Setup
=====

.. php:namespace:: Deval

.. php:class:: Setup

	Configuration class for :php:class:`Deval\\Renderer`.

	.. php:attr:: plain_text_processor

		Defines how plain text is processed (most useful for whitespace control), see :ref:`plain_text_processor` for details.

	.. php:attr:: undefined_variable_fallback

		Defines how undefined variables are handled, see :ref:`undefined_variable_behavior` for details.

	.. php:attr:: version

		PHP compatibility option, see :ref:`version` for details.
