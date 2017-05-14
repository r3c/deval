<?php

namespace Deval;

class Document
{
	private $root;

	public function __construct ($source)
	{
		static $setup;

		if (!isset ($setup))
		{
			$path = dirname (__FILE__);

			require $path . '/generated/parser.php';
			require $path . '/block.php';
			require $path . '/expression.php';

			$setup = true;
		}

		$parser = new \PhpPegJs\Parser ();

		$this->root = $parser->parse ($source);
	}

	public function __toString ()
	{
		return (string)$this->root;
	}
}

?>
