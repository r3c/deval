<?php

namespace Deval;

class MemberExpression extends Expression
{
	public function __construct ($source, $indices)
	{
		$this->indices = $indices;
		$this->source = $source;
	}

	public function __toString ()
	{
		return $this->source . implode ('', array_map (function ($i) { return '[' . $i . ']'; }, $this->indices));
	}

	public function generate (&$variables)
	{
		$indices = array ();

		foreach ($this->indices as $index)
			$indices[] = $index->generate ($variables);

		return State::emit_member (array ($this->source->generate ($variables), 'array(' . implode (',', $indices) . ')'));
	}

	public function inject ($variables)
	{
		$indices = array ();
		$ready = true;
		$source = $this->source->inject ($variables);
		$values = array();

		foreach ($this->indices as $index)
		{
			$index = $index->inject ($variables);

			if ($index->evaluate ($result))
				$values[] = $result;
			else
				$ready = false;

			$indices[] = $index;
		}

		// Resolve indices and pass final value if source and indices were evaluated
		if ($ready && $source->evaluate ($result))
			return new ConstantExpression (State::member ($result, $values));

		// Otherwise return injected source and indices
		return new self ($source, $indices);
	}
}

?>
