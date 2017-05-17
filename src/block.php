<?php

namespace Deval;

abstract class Block
{
	abstract function compile (&$variables);
	abstract function inject ($variables);
}

?>
