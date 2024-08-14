# Deval: Deferred Evaluation Templates

[![Build Status](https://img.shields.io/github/actions/workflow/status/r3c/deval/verify.yml?branch=master)](https://github.com/r3c/deval/actions/workflows/verify.yml)
[![license](https://img.shields.io/github/license/r3c/deval.svg)](https://opensource.org/licenses/MIT)

## Overview

Deval is a PHP template engine with support for partial evaluation at
compilation to enable early error detection, optimize generated code and
improve execution performance.

While most PHP template engines follow a “load variables and render” workflow
Deval introduces an intermediate pre-compilation injection step to specify
variables that you know won’t change on every rendering. By doing so, Deval
will pre-evaluate your template and generate specialized code where all these
invariants have been evaluated.

This sample template:

```
{{ $ locale(language, "users.list") }}
{{ for user in users }}
    - {{ $ user.login }}
{{ empty }}
    {{ $ locale(language, "no.users") }}
{{ end }}
```

Will compile a PHP snippet similar to this one after injecting a `locale`
function and a `language` variable:

```
Registered user:
<?php $_counter = 0; foreach ($users as $user) { ?>
    - <?php echo $user->login; ?>
<?php ++$_counter; } if ($_counter === 0) { ?>
    No users registered.
<?php } ?>
```

As you can see all statements depending on variables locale and language have
been evaluated in generated code, as their value was known at compile time.
Other variables have been left untouched and Deval expects you to specify their
value when rendering the template (and will raise an error if you don’t).

## Instructions

Either install Deval with [Composer](https://getcomposer.org/) and autoload it:

```
composer require r3c/deval
```

Or download [latest release](https://github.com/r3c/deval/releases/latest) from
GitHub then unpack & require manually:

```
require 'path/to/deval/deval.php';
```

Full documentation is available
[on Read the Docs](http://deval.readthedocs.io/).

## Resource

- Contact: v.github.com+deval [at] mirari [dot] fr
- License: [license.md](license.md)
