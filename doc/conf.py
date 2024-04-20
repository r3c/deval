# -*- coding: utf-8 -*-

"""
Resources:

https://pythonhosted.org/an_example_pypi_project/sphinx.html
https://sublime-and-sphinx-guide.readthedocs.io/en/latest/code_blocks.html
https://docutils.sourceforge.net/docs/user/rst/quickref.html
"""

from sphinx.highlighting import lexers
from pygments.lexers.web import PhpLexer

extensions = ["sphinxcontrib.phpdomain"]

# Add any paths that contain templates here, relative to this directory.
templates_path = ['_templates']

# The suffix of source filenames.
source_suffix = '.rst'

# The master toctree document.
master_doc = 'index'

# General information about the project.
project = u'Deval Documentation'
copyright = u'2017, Rémi Caput'

# The short X.Y version.
version = '1.0'
# The full version, including alpha/beta/rc tags.
release = '1.0'

# List of patterns, relative to source directory, that match files and
# directories to ignore when looking for source files.
exclude_patterns = ['_build']

# The name of the Pygments (syntax highlighting) style to use.
pygments_style = 'sphinx'

# See: https://sphinx-rtd-theme.readthedocs.io/en/stable/configuring.html
html_theme = 'sphinx_rtd_theme'
html_theme_path = ['_themes']
html_theme_options = {
    'style_external_links': True
}

# Add any paths that contain custom static files (such as style sheets) here,
# relative to this directory. They are copied after the builtin static files,
# so a file named "default.css" will overwrite the builtin "default.css".
html_static_path = ['_static']

# Output file base name for HTML help builder.
htmlhelp_basename = 'DevalDocumentation'

# Grouping the document tree into LaTeX files. List of tuples
# (source start file, target name, title,
#  author, documentclass [howto, manual, or own class]).
latex_documents = [
    ('index', 'Deval.tex', u'Deval Documentation',
     u'Deval', 'manual'),
]

# One entry per manual page. List of tuples
# (source start file, name, description, authors, manual section).
man_pages = [
    ('index', 'deval', u'Deval Documentation',
     [u'Deval'], 1)
]

# Grouping the document tree into Texinfo files. List of tuples
# (source start file, target name, title, author,
#  dir menu entry, description, category)
texinfo_documents = [
    ('index', 'Deval', u'Deval Documentation',
     u'Deval', 'Deval', 'Deval Documentation.',
     'Miscellaneous'),
]

# Set up PHP syntax highlights
lexers["php"] = PhpLexer(startinline=True, linenos=1)
lexers["php-annotations"] = PhpLexer(startinline=True, linenos=1)

highlight_language = "php"
primary_domain = "php"
