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

	public function generate (&$requires)
	{
		$variables = array ();

		$source = $this->root->generate ($variables)->source ();
		$requires = array_keys ($variables);

		return $source;
	}

	public function inject ($variables)
	{
		$this->root = $this->root->inject ($variables);
	}
}

class Output
{
	private $snippets = array ();

	public function append ($other)
	{
		if (count ($other->snippets) === 0)
			return;

		$snippets_collapse = array ();
		$snippets_concat = array_merge ($this->snippets, $other->snippets);

		$count = 0;
		$last = null;

		foreach ($snippets_concat as $snippet)
		{
			if ($count > 0 && $snippet[1] === $last)
				$snippets_collapse[$count - 1][0] .= $snippet[0];
			else
			{
				$snippets_collapse[] = $snippet;

				$count++;
				$last = $snippet[1];
			}
		}

		$this->snippets = $snippets_collapse;

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

?>
