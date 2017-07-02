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

	public function compile ($generator)
	{
		$output = new Output ();

		// Generate loop counter if empty block contains instructions
		$empty = $this->empty->compile ($generator);

		if ($empty->has_data ())
		{
			$counter = $generator->emit_unique ();

			$output->append_code ($counter . '=0;');
		}

		// Backup key and value variables
		$provides = array ($this->value_name => null);

		if ($this->key_name !== null)
			$provides[$this->key_name] = null;

		$output->append_code ($generator->emit_scope_push (array_keys ($provides)));

		// Generate for control loop
		$output->append_code ('foreach(' . $this->source->generate ($generator) . ' as ');

		if ($this->key_name !== null)
			$output->append_code (Generator::emit_symbol ($this->key_name) . '=>' . Generator::emit_symbol ($this->value_name));
		else
			$output->append_code (Generator::emit_symbol ($this->value_name));

		$output->append_code (')');

		// Compile inner loop
		$requires = array ();

		$output->append_code ('{');
		$output->append ($this->loop->compile ($generator, $requires));

		if ($empty->has_data ())
			$output->append_code ('++' . $counter . ';');

		$output->append_code ('}');

		// Restore key and value variables
		$output->append_code ($generator->emit_scope_pop (array_keys ($provides)));

		// Write empty block if it contains instructions
		if ($empty->has_data ())
		{
			$output->append_code ('if(' . $counter . '==0)');
			$output->append_code ('{');
			$output->append ($empty);
			$output->append_code ('}');
		}

		return $output;
	}

	public function get_symbols ()
	{
		$requires = $this->loop->get_symbols ();
		$symbols = array ();

		if ($this->key_name !== null)
			unset ($requires[$this->key_name]);

		unset ($requires[$this->value_name]);

		Generator::merge_symbols ($symbols, $requires);
		Generator::merge_symbols ($symbols, $this->empty->get_symbols ());
		Generator::merge_symbols ($symbols, $this->source->get_symbols ());

		return $symbols;
	}

	public function inject ($invariants)
	{
		$source = $this->source->inject ($invariants);

		// Unroll loop if elements can be enumerated
		if ($source->try_enumerate ($elements))
		{
			$loops = array ();

			foreach ($elements as $key => $element)
			{
				// Inject key as constant if specified
				if ($this->key_name !== null)
					$invariants[$this->key_name] = new ConstantExpression ($key);

				// Inject value expression
				$invariants[$this->value_name] = $element;

				// Inject and save current iteration
				$loops[] = $this->loop->inject ($invariants);
			}

			if (count ($loops) === 0)
				return $this->empty->inject ($invariants);

			return new ConcatBlock ($loops);
		}

		// Or generate dynamic loop code otherwise
		else
		{
			// Inject all invariants into inner empty block
			$empty = $this->empty->inject ($invariants);

			// Remove loop variables from invariants and inject into inner loop
			if ($this->key_name !== null)
				unset ($invariants[$this->key_name]);

			unset ($invariants[$this->value_name]);

			$loop = $this->loop->inject ($invariants);

			// Rebuild command with injected source, loop and empty blocks
			return new self ($source, $this->key_name, $this->value_name, $loop, $empty);
		}
	}

	public function resolve ($blocks)
	{
		$empty = $this->empty->resolve ($blocks);
		$loop = $this->loop->resolve ($blocks);

		return new self ($this->source, $this->key_name, $this->value_name, $loop, $empty);
	}

	public function wrap ($caller)
	{
		$empty = $this->empty->wrap ($caller);
		$loop = $this->loop->wrap ($caller);

		return new self ($this->source, $this->key_name, $this->value_name, $loop, $empty);
	}
}

?>
