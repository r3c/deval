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

	public function compile ($generator, $expressions, &$variables)
	{
		$output = new Output ();
		$source = $this->source->inject ($expressions);

		// Unroll loop if elements can be enumerated
		if ($source->get_elements ($elements))
		{
			$empty = true;

			foreach ($elements as $key => $element)
			{
				$empty = false;
				$iterations = $expressions;

				// Inject key as constant if specified
				if ($this->key_name !== null)
					$iterations[$this->key_name] = new ConstantExpression ($key);

				// Inject value expression
				$iterations[$this->value_name] = $element;

				// Compile and append current iteration to output
				$output->append ($this->loop->compile ($generator, $iterations, $variables));
			}

			if ($empty)
				$output->append ($this->empty->compile ($generator, $expressions, $variables));
		}

		// Or generate dynamic loop code otherwise
		else
		{
			// Generate loop counter if needed
			$counter = $this->empty->is_void () ? null : $generator->emit_local ();

			if ($counter !== null)
				$output->append_code ($counter . '=0;');

			// Backup key and value variables
			$provides = array ($this->value_name => null);

			if ($this->key_name !== null)
				$provides[$this->key_name] = null;

			$output->append_code (Generator::emit_scope_push (array_keys ($provides)));

			// Generate for control loop
			$output->append_code ('foreach(' . $source->generate ($generator, $variables) . ' as ');

			if ($this->key_name !== null)
				$output->append_code (Generator::emit_symbol ($this->key_name) . '=>' . Generator::emit_symbol ($this->value_name));
			else
				$output->append_code (Generator::emit_symbol ($this->value_name));

			$output->append_code (')');

			// Remove loop variables from expressions and compile inner loop
			$iterations = $expressions;
			$requires = array ();

			if ($this->key_name !== null)
				unset ($iterations[$this->key_name]);

			unset ($iterations[$this->value_name]);

			$output->append_code ('{');
			$output->append ($this->loop->compile ($generator, $iterations, $requires));

			if ($counter !== null)
				$output->append_code ('++' . $counter . ';');

			$output->append_code ('}');

			// Restore key and value variables
			$output->append_code (Generator::emit_scope_pop (array_keys ($provides)));

			// Write empty block if any
			if ($counter !== null)
			{
				$output->append_code ('if(' . $counter . '==0)');
				$output->append_code ('{');
				$output->append ($this->empty->compile ($generator, $expressions, $variables));
				$output->append_code ('}');
			}

			// Append required variables excepted key and value
			$variables += array_diff_key ($requires, $provides);
		}

		return $output;
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

	public function wrap ($caller)
	{
		$empty = $this->empty->wrap ($caller);
		$loop = $this->loop->wrap ($caller);

		return new self ($this->source, $this->key_name, $this->value_name, $loop, $empty);
	}
}

?>
