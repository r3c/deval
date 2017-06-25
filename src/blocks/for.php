<?php

namespace Deval;

class ForBlock implements Block
{
	public function __construct ($source, $key_name, $value_name, $loop, $empty)
	{
		$this->empty = $empty;
		$this->key_name = $key_name;
		$this->loop = $loop;
		$this->source = $source;
		$this->value_name = $value_name;
	}

	public function compile ($generator, &$volatiles)
	{
		$backups = $this->key_name !== null ? array ($this->key_name, $this->value_name) : array ($this->value_name);
		$output = new Output ();

		// Generate loop counter if needed
		$counter = $this->empty->is_void () ? null : $generator->emit_local ();

		if ($counter !== null)
			$output->append_code ($counter . '=0;');

		// Backup key and value variables
		$output->append_code (Generator::emit_scope_push ($backups));

		// Generate for control loop
		$output->append_code ('foreach(' . $this->source->generate ($generator, $volatiles) . ' as ');

		if ($this->key_name !== null)
			$output->append_code (Generator::emit_symbol ($this->key_name) . '=>' . Generator::emit_symbol ($this->value_name));
		else
			$output->append_code (Generator::emit_symbol ($this->value_name));

		$output->append_code (')');

		// Write loop and merge inner volatiles into parent
		$requires = array ();

		$output->append_code ('{');
		$output->append ($this->loop->compile ($generator, $requires));

		if ($counter !== null)
			$output->append_code ('++' . $counter . ';');

		$output->append_code ('}');

		// Restore key and value variables
		$output->append_code (Generator::emit_scope_pop ($backups));

		// Write empty block if any
		if ($counter !== null)
		{
			$output->append_code ('if(' . $counter . '==0)');
			$output->append_code ('{');
			$output->append ($this->empty->compile ($generator, $volatiles));
			$output->append_code ('}');
		}

		// Append required volatiles excepted key and value
		$volatiles += array_diff_key ($requires, array_flip ($backups));

		return $output;
	}

	public function inject ($constants)
	{
		$empty = $this->empty->inject ($constants);
		$source = $this->source->inject ($constants);

		// Source can be enumerated, unroll loop and generate result blocks
		if ($source->get_elements ($elements))
		{
			$blocks = array ();

			foreach ($elements as $key => $element)
			{
				// Inject key as constant if specified
				if ($this->key_name !== null)
					$constants[$this->key_name] = $key;

				// Inject value as constant if evaluated or assignment otherwise
				$assignments = array ();

				if ($element->get_value ($value))
					$constants[$this->value_name] = $value;
				else
					$assignments[] = array ($this->value_name, $element);

				// Wrap loop inside assignment block
				$block = new LetBlock ($assignments, $this->loop);
				$blocks[] = $block->inject ($constants);
			}

			// Some blocks were generated, return them
			if (count ($blocks) > 0)
				return new ConcatBlock ($blocks);

			// No block was generated (empty source), generate empty block
			return $empty;
		}

		// Source can't be evaluated, rebuild block with injected children
		else
		{
			// Inject all constants but key (optional) and value to loop block
			if ($this->key_name !== null)
				unset ($constants[$this->key_name]);

			unset ($constants[$this->value_name]);

			$loop = $this->loop->inject ($constants);

			// Rebuild block
			return new self ($source, $this->key_name, $this->value_name, $loop, $empty);
		}
	}

	public function is_void ()
	{
		return $this->empty->is_void () && $this->loop->is_void ();
	}

	public function resolve ($blocks)
	{
		$empty = $this->empty->resolve ($blocks);
		$loop = $this->loop->resolve ($blocks);

		return new self ($this->source, $this->key_name, $this->value_name, $loop, $empty);
	}

	public function wrap ($name)
	{
		$empty = $this->empty->wrap ($name);
		$loop = $this->loop->wrap ($name);

		return new self ($this->source, $this->key_name, $this->value_name, $loop, $empty);
	}
}

?>
