<?php

namespace Deval;

class ForBlock implements Block
{
	public function __construct ($source, $key, $value, $body, $fallback)
	{
		if ($key !== null)
			Generator::assert_symbol ($key);

		Generator::assert_symbol ($value);

		$this->body = $body;
		$this->fallback = $fallback;
		$this->key = $key;
		$this->source = $source;
		$this->value = $value;
	}

	public function compile ($generator, &$volatiles)
	{
		$output = new Output ();

		// Write loop control
		$loop = $generator->make_local ();

		$output->append_code ('$' . $loop . '=0;');
		$output->append_code ('foreach(' . $this->source->generate ($volatiles) . ' as ');

		if ($this->key !== null)
			$output->append_code ('$' . $this->key . '=>$' . $this->value);
		else
			$output->append_code ('$' . $this->value);

		$output->append_code (')');

		// Write body and merge inner volatiles into parent
		$volatiles_inner = array ();

		$output->append_code ('{');
		$output->append ($this->body->compile ($generator, $volatiles_inner));
		$output->append_code ('++$' . $loop . ';');
		$output->append_code ('}');

		if ($this->key !== null)
			unset ($volatiles_inner[$this->key]);

		unset ($volatiles_inner[$this->value]);

		foreach (array_keys ($volatiles_inner) as $name)
			$volatiles[$name] = true;

		// Write fallback block if any
		if ($this->fallback !== null)
		{
			$output->append_code ('if($' . $loop . '==0)');
			$output->append_code ('{');
			$output->append ($this->fallback->compile ($generator, $volatiles));
			$output->append_code ('}');
		}

		return $output;
	}

	public function inject ($constants)
	{
		$source = $this->source->inject ($constants);

		// Source can't be evaluated, rebuild block with injected children
		if (!$source->evaluate ($result))
		{
			// Inject all constants to fallback block
			$fallback = $this->fallback !== null ? $this->fallback->inject ($constants) : null;

			// Inject all constants but key (optional) and value to body block
			if ($this->key !== null)
				unset ($constants[$this->key]);

			unset ($constants[$this->value]);

			$body = $this->body->inject ($constants);

			// Rebuild block
			return new self ($source, $this->key, $this->value, $body, $fallback);
		}

		// Source was evaluated, unroll loop and generate result blocks
		$blocks = array ();

		// FIXME: verify $result is iterable
		foreach ($result as $key => $value)
		{
			$constants_inner = $constants;

			// Inject all constants after overriding key (optional) and value
			if ($this->key !== null)
				$constants_inner[$this->key] = $key;

			$constants_inner[$this->value] = $value;

			$blocks[] = $this->body->inject ($constants_inner);
		}

		// Some blocks were generated, return them
		if (count ($blocks) > 0)
			return ConcatBlock::create ($blocks);

		// No block was generated (empty source), generate fallback block if any
		if ($this->fallback !== null)
			return $this->fallback->inject ($constants);

		// Otherwise generate empty block
		return new VoidBlock ();
	}

	public function resolve ($blocks)
	{
		$body = $this->body->resolve ($blocks);
		$fallback = $this->fallback !== null ? $this->fallback->resolve ($blocks) : null;

		return new self ($this->source, $this->key, $this->value, $body, $fallback);
	}
}

?>
