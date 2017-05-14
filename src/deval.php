<?php

namespace Deval;

function setup ()
{
	$path = dirname (__FILE__);

	require $path . '/generated/parser.php';
	require $path . '/block.php';
	require $path . '/expression.php';
}

setup ();

?>
