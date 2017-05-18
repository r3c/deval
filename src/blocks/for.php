<?php

namespace Deval;

class ForBlock extends Block
{
	public function __construct ($source, $key, $value, $body, $fallback)
	{
		if ($key !== null)
			State::assert_symbol ($key);

		State::assert_symbol ($value);

		$this->body = $body;
		$this->fallback = $fallback;
		$this->key = $key;
		$this->source = $source;
		$this->value = $value;
	}

	public function compile (&$variables)
	{
		$output = new Output ();

		// Write loop control
		$output->append_code (State::emit_loop_start() . ';');
		$output->append_code ('foreach(' . $this->source->generate ($variables) . ' as ');

		if ($this->key !== null)
			$output->append_code ('$' . $this->key . '=>$' . $this->value);
		else
			$output->append_code ('$' . $this->value);

		$output->append_code (')');

		// Write body and merge inner variables into parent
		$variables_inner = array ();

		$output->append_code ('{');
		$output->append ($this->body->compile ($variables_inner));
		$output->append_code (State::emit_loop_step() . ';');
		$output->append_code ('}');

		if ($this->key !== null)
			unset ($variables_inner[$this->key]);

		unset ($variables_inner[$this->value]);

		foreach (array_keys ($variables_inner) as $name)
			$variables[$name] = true;

		// Write fallback block if any
		if ($this->fallback !== null)
		{
			$output->append_code ('if(' . State::emit_loop_stop() . ')');
			$output->append_code ('{');
			$output->append ($this->fallback->compile ($variables));
			$output->append_code ('}');
		}
		else
			$output->append_code (State::emit_loop_stop() . ';');

		return $output;
	}

	public function inject ($variables)
	{
		$source = $this->source->inject ($variables);

		// Source can't be evaluated, rebuild block with injected children
		if (!$source->evaluate ($result))
		{
			// Inject all variables to fallback block
			$fallback = $this->fallback !== null ? $this->fallback->inject ($variables) : null;

			// Inject all variables but key (optional) and value to body block
			if ($this->key !== null)
				unset ($variables[$this->key]);

			unset ($variables[$this->value]);

			$body = $this->body->inject ($variables);

			// Rebuild block
			return new self ($source, $this->key, $this->value, $body, $fallback);
		}

		// Source was evaluated, unroll loop and generate result blocks
		$blocks = array ();

		// FIXME: verify $result is iterable
		foreach ($result as $key => $value)
		{
			$variables_inner = $variables;

			// Inject all variables after overriding key (optional) and value
			if ($this->key !== null)
				$variables_inner[$this->key] = $key;

			$variables_inner[$this->value] = $value;

			$blocks[] = $this->body->inject ($variables_inner);
		}

		// Some blocks were generated, return them
		if (count ($blocks) > 0)
			return ConcatBlock::create ($blocks);

		// No block was generated (empty source), generate fallback block if any
		if ($this->fallback !== null)
			return $this->fallback->inject ($variables);

		// Otherwise generate empty block
		return new VoidBlock ();
	}
}

?>
