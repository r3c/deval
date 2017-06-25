<?php

namespace Deval;

class ForBlock implements Block
{
	public function __construct ($source, $key, $value, $loop, $empty)
	{
		$this->empty = $empty;
		$this->key = $key;
		$this->loop = $loop;
		$this->source = $source;
		$this->value = $value;
	}

	public function compile ($generator, &$volatiles)
	{
		$output = new Output ();

		// Generate loop counter if needed
		$counter = $this->empty->is_void () ? null : $generator->emit_local ();

		if ($counter !== null)
			$output->append_code ($counter . '=0;');

		$output->append_code ('foreach(' . $this->source->generate ($generator, $volatiles) . ' as ');

		if ($this->key !== null)
			$output->append_code (Generator::emit_symbol ($this->key) . '=>' . Generator::emit_symbol ($this->value));
		else
			$output->append_code (Generator::emit_symbol ($this->value));

		$output->append_code (')');

		// Write loop and merge inner volatiles into parent
		$requires = array ();

		$output->append_code ('{');
		$output->append ($this->loop->compile ($generator, $requires));

		if ($counter !== null)
			$output->append_code ('++' . $counter . ';');

		$output->append_code ('}');

		if ($this->key !== null)
			unset ($requires[$this->key]);

		unset ($requires[$this->value]);

		foreach (array_keys ($requires) as $name)
			$volatiles[$name] = true;

		// Write empty block if any
		if ($counter !== null)
		{
			$output->append_code ('if(' . $counter . '==0)');
			$output->append_code ('{');
			$output->append ($this->empty->compile ($generator, $volatiles));
			$output->append_code ('}');
		}

		return $output;
	}

	public function inject ($constants)
	{
		$source = $this->source->inject ($constants);

		// Source can't be evaluated, rebuild block with injected children
		if (!$source->get_value ($result))
		{
			// Inject all constants to empty block
			$empty = $this->empty->inject ($constants);

			// Inject all constants but key (optional) and value to loop block
			if ($this->key !== null)
				unset ($constants[$this->key]);

			unset ($constants[$this->value]);

			$loop = $this->loop->inject ($constants);

			// Rebuild block
			return new self ($source, $this->key, $this->value, $loop, $empty);
		}

		// Source was evaluated, unroll loop and generate result blocks
		if (!is_array ($result) && !($result instanceof \Traversable))
			throw new InjectException ($source, 'is not iterable');

		$blocks = array ();

		foreach ($result as $key => $value)
		{
			$constants_inner = $constants;

			// Inject all constants after overriding key (optional) and value
			if ($this->key !== null)
				$constants_inner[$this->key] = $key;

			$constants_inner[$this->value] = $value;

			$blocks[] = $this->loop->inject ($constants_inner);
		}

		// Some blocks were generated, return them
		if (count ($blocks) > 0)
			return new ConcatBlock ($blocks);

		// No block was generated (empty source), generate empty block
		return $this->empty->inject ($constants);
	}

	public function is_void ()
	{
		return $this->empty->is_void () && $this->loop->is_void ();
	}

	public function resolve ($blocks)
	{
		$empty = $this->empty->resolve ($blocks);
		$loop = $this->loop->resolve ($blocks);

		return new self ($this->source, $this->key, $this->value, $loop, $empty);
	}
}

?>
