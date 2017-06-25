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
		$output = new Output ();

		// Unroll loop if elements can be enumerated
		if ($this->source->get_elements ($elements))
		{
			$empty = true;

			foreach ($elements as $key => $element)
			{
				$constants = array ();
				$empty = false;

				// Inject key as constant if specified
				if ($this->key_name !== null)
					$constants[$this->key_name] = $key;

				// Inject value as constant if evaluated
				if ($element->get_value ($value))
				{
					$constants[$this->value_name] = $value;
					$block = $this->loop->inject ($constants);
				}

				// Or wrap loop inside assignment otherwise
				else
				{
					$assignments = array (array ($this->value_name, $element));
					$block = new LetBlock ($assignments, $this->loop->inject ($constants));
				}

				// Append current block to output
				$output->append ($block->compile ($generator, $volatiles));
			}

			if ($empty)
				$output->append ($this->empty->compile ($generator, $volatiles));
		}

		// Or generate dynamic loop code otherwise
		else
		{
			$backups = $this->key_name !== null ? array ($this->key_name, $this->value_name) : array ($this->value_name);
			$counter = $this->empty->is_void () ? null : $generator->emit_local ();

			// Generate loop counter if needed
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
		}

		return $output;
	}

	public function inject ($constants)
	{
		// Inject constants into source and empty block
		$empty = $this->empty->inject ($constants);
		$source = $this->source->inject ($constants);

		// Remove key and value from constants before injecting into loop
		if ($this->key_name !== null)
			unset ($constants[$this->key_name]);

		unset ($constants[$this->value_name]);

		$loop = $this->loop->inject ($constants);

		// Rebuild block with injected constants
		return new self ($source, $this->key_name, $this->value_name, $loop, $empty);
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
