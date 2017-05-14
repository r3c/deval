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

	public function inject ($variables)
	{
		$this->root = $this->root->inject ($variables);
	}

	public function render (&$requires)
	{
		$variables = array ();

		$source = $this->root->render ($variables)->source ();
		$requires = array_keys ($variables);

		return $source;
	}
}

class Output
{
	private $snippets = array ();

	public function append ($other)
	{
		if (count ($other->snippets) === 0)
			return;

		$count = count ($this->snippets);
		$last = $count > 0 && $this->snippets[$count - 1][1];

		foreach ($other->snippets as $snippet)
		{
			list ($output, $is_code) = $snippet;

			if ($count > 0 && $is_code === $last)
				$this->snippets[$count - 1][0] .= $output;
			else
			{
				$this->snippets[] = $snippet;

				$count++;
				$last = $is_code;
			}
		}

		return $this;
	}

	public function append_code ($code)
	{
		$other = new self ();
		$other->snippets[] = array ($code, true);

		return $this->append ($other);
	}

	public function append_text ($text)
	{
		$other = new self ();
		$other->snippets[] = array ($text, false);

		return $this->append ($other);
	}

	public function source ()
	{
		$output = '';
		$is_code = false;

		foreach ($this->snippets as $snippet)
		{
			list ($block_output, $block_is_code) = $snippet;

			if ($block_is_code === $is_code)
				$output .= $block_output;
			else if ($is_code)
				$output .= ' ?>' . $block_output . '<?php ';
			else
				$output .= '<?php ' . $block_output . ' ?>';
		}

		return $output;
	}
}

class State
{
	public static function loop_start ()
	{
		return '/* start loop */';
	}

	public static function loop_step ()
	{
		return '/* step loop */';
	}

	public static function loop_stop ()
	{
		return '/* stop loop */';
	}
}

?>
