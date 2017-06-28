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
			list ($source, $is_code) = $snippet;

			if ($count > 0 && $is_code === $last)
				$this->snippets[$count - 1][0] .= $source;
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
		if ($code === '')
			return $this;

		$other = new self ();
		$other->snippets[] = array ($code, true);

		return $this->append ($other);
	}

	public function append_text ($text)
	{
		if ($text === '')
			return $this;

		$other = new self ();
		$other->snippets[] = array ($text, false);

		return $this->append ($other);
	}

	public function has_data ()
	{
		return count ($this->snippets) !== 0;
	}

	public function source ()
	{
		$is_code = false;
		$source = '';

		foreach ($this->snippets as $snippet)
		{
			list ($block_source, $block_is_code) = $snippet;

			// Escape PHP tags in plain text
			if (!$block_is_code)
			{
				$tags = array ('<\\?php(?![[:alpha:]])', '<\\?=', '\\?>', '<script\\s+language\\s*=\\s*["\']?php["\']?\\s*>', '</script\\s*>');

				if (ini_get ('asp_tags'))
					$tags = array_merge ($tags, array ('<%', '<%=', '%>'));

				if (ini_get ('short_open_tag'))
					$tags = array_merge ($tags, array ('<\\?(?![[:alpha:]])'));

				$block_source = preg_replace_callback ('@' . implode ('|', $tags) . '@', function ($match)
				{
					return '<?php echo ' . var_export ($match[0], true) . '; ?>';
				}, $block_source);
			}

			// Append content to source stream
			if ($block_is_code === $is_code)
				$source .= $block_source;
			else if ($is_code)
				$source .= " ?>\n" . $block_source . '<?php ';
			else
				$source .= '<?php ' . $block_source . " ?>\n";
		}

		return $source;
	}
}

?>
