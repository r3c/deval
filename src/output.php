<?php

namespace Deval;

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
				$output .= " ?>\n" . $block_output . '<?php ';
			else
				$output .= '<?php ' . $block_output . " ?>\n";
		}

		return $output;
	}
}

?>
