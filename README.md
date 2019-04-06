Deval: Deferred Evaluation Templates
====================================

[![Build Status](https://travis-ci.org/r3c/deval.svg?branch=master)](https://travis-ci.org/r3c/deval)
[![license](https://img.shields.io/github/license/r3c/deval.svg)](https://opensource.org/licenses/MIT)

Overview
--------

Deval is a PHP template engine with support for partial evaluation at
compilation to enable early error detection, optimize generated code and
improve execution performance.

While most PHP template engines follow a “load variables and render” workflow
Deval introduces an intermediate pre-compilation injection step to specify
variables that you know won’t change on every rendering. By doing so, Deval
will pre-evaluate your template and generate specialized code where all these
invariants have been evaluated.

This sample template:

<pre>
{{ $ locale(language, "users.list") }}
{{ for user in users }}
    - {{ $ user.login }}
{{ empty }}
    {{ $ locale(language, "no.users") }}
{{ end }}
</pre>

Will compile a PHP snippet similar to this one after injecting a `locale`
function and a `language` variable:

<pre>
Registered user:
&lt;?php $_counter = 0; foreach ($users as $user) { ?&gt;
    - &lt;?php echo $user-&gt;login; ?&gt;
&lt;?php ++$_counter; } if ($_counter === 0) { ?&gt;
    No users registered.
&lt;?php } ?&gt;
</pre>    

As you can see all statements depending on variables locale and language have
been evaluated in generated code, as their value was known at compile time.
Other variables have been left untouched and Deval expects you to specify their
value when rendering the template (and will raise an error if you don’t).


Instructions
------------

Download latest release [from GitHub](https://github.com/r3c/deval/releases/download/1.1.0/deval-1.1.0.zip).

Full documentation is available [on Read the Docs](http://deval.readthedocs.io/).


Licence
-------

This project is open-source, released under MIT licence. See file `LICENSE.md`
for details.


Author
------

[Rémi Caput](http://remi.caput.fr/) (github.com+deval [at] mirari [dot] fr)
