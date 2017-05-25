<?php

namespace Deval;

class Compiler
{
	private $block;

	public function __construct ($block)
	{
		$this->block = $block;
	}

	public function compile ($style = null)
	{
		static $trims;

		if (!isset ($trims))
		{
			$trims = array
			(
				'collapse'	=> function ($s) { return preg_replace ('/\\s+/', ' ', $s); },
				'html'		=> function ($s) { return preg_replace (array ('/(^|>)\\s+/m', '/\\s+(<|$)/m'), array ('$1 ', ' $1'), $s); }
			);
		}

		if (is_string ($style) && isset ($trims[$style]))
			$trim = $trims[$style];
		else if (is_callable ($style))
			$trim = $style;
		else
			$trim = function ($s) { return $s; };

		$variables = array ();
		$source = $this->block->compile ($trim, $variables);
		$names = array_keys ($variables);

		$output = new Output ();
		$output->append_code (State::emit_create ($names));
		$output->append ($source);

		return $output->source ();
	}

	public function inject ($variables)
	{
		$this->block = $this->block->inject ($variables);
	}
}

?>
