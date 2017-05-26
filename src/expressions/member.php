<?php

namespace Deval;

class MemberExpression implements Expression
{
	public function __construct ($source, $index)
	{
		$this->index = $index;
		$this->source = $source;
	}

	public function __toString ()
	{
		return $this->source . '[' . $this->index . ']';
	}

	public function evaluate (&$result)
	{
		return false;
	}

	public function generate (&$volatiles)
	{
		return State::emit_member ($this->source->generate ($volatiles), $this->index->generate ($volatiles));
	}

	public function inject ($constants)
	{
		$index = $this->index->inject ($constants);
		$source = $this->source->inject ($constants);

		// Resolve to constant value if both source and index were evaluated
		if ($index->evaluate ($index_result) && $source->evaluate ($source_result))
			return new ConstantExpression (State::member ($source_result, $index_result));

		// Otherwise return injected source and index
		return new self ($source, $index);
	}
}

?>
