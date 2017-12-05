
// Document tree

document
	= content

content
	= blocks:block*
	{
		switch (count ($blocks))
		{
			case 0:
				return new \Deval\PlainBlock ('');

			case 1:
				return $blocks[0];

			default:
				return new \Deval\ConcatBlock ($blocks);
		}
	}

// Block tree

block
	= "{{" _ command:command _ "}}" { return $command; }
	/ plain:plain { return new \Deval\PlainBlock ($plain); }

_ "whitespace"
	= [ \t\n\r]*

// Plain text tree

plain
	= chars:char_raw+ { return implode ('', $chars); }

char_raw
	= brace:"{" char:char_safe { return $brace . $char; }
	/ char_safe

char_safe
	= [^\\{]
	/ "\\" sequence:(
		  "\\"
		/ "{"
	) { return $sequence; }

// Command tree

command "command block"
	= command:command_buffer
	/ command:command_echo
	/ command:command_for
	/ command:command_if
	/ command:command_let

command_buffer "buffer command"
	= "buffer" _ name:symbol _ "}}" _ body:content _ "{{" _ "end"
	{
		return new \Deval\BufferBlock ($name, $body);
	}

command_echo "echo command"
	= "echo" _ value:expression
	{
		return new \Deval\EchoBlock ($value);
	}
	/ value:expression
	{
		return new \Deval\EchoBlock ($value);
	}

command_for "for command"
	= "for" _ key:(symbol _ "," _)? value:symbol _ "in" _ source:expression _ "}}" body:content empty:command_for_empty? "{{" _ "end"
	{
		return new \Deval\ForBlock ($source, $key !== null ? $key[0] : null, $value, $body, $empty);
	}

command_for_empty
	= "{{" _ "empty" _ "}}" body:content
	{
		return $body;
	}

command_if "if command"
	= "if" _ condition:expression _ "}}" body:content branches:command_if_elseif* fallback:command_if_else? "{{" _ "end"
	{
		return new \Deval\IfBlock (array_merge (array (array ($condition, $body)), $branches), $fallback);
	}

command_if_elseif
	= "{{" _ "else" _ "if" _ condition:expression _ "}}" body:content
	{
		return array ($condition, $body);
	}

command_if_else
	= "{{" _ "else" _ "}}" body:content
	{
		return $body;
	}

command_let "let command"
	= "let" _ assignments:assignments _ "}}" _ body:content _ "{{" _ "end"
	{
		return new \Deval\LetBlock ($assignments, $body);
	}

symbol "symbol"
	= $ ([_A-Za-z] [_0-9A-Za-z]*)

assignments
	= head:assignment tail:(_ "," _ token:assignment { return $token; })*
	{
		return array_merge (array ($head), $tail);
	}

assignment
	= name:symbol _ "as" _ value:expression
	{
		return array ($name, $value);
	}

// Expression tree

expression
	= value:additive

additive
	= lhs:multiplicative _ "+" _ rhs:additive { return $lhs + $rhs; }
	/ multiplicative

multiplicative
	= lhs:primary _ "*" _ rhs:multiplicative { return $lhs * $rhs; }
	/ primary

primary
	= integer
	/ "(" additive:additive ")" { return $additive; }

integer "integer"
	= digits:$[0-9]+ { return intval($digits); }
