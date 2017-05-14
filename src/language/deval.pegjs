
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
	= "let" _ assignments:command_let_assignments _ "}}" _ body:content _ "{{" _ "end"
	{
		return new \Deval\LetBlock ($assignments, $body);
	}

command_let_assignments
	= head:command_let_assignments_variable _ tail:("," _ token:command_let_assignments_variable { return $token; })*
	{
		return array_merge (array ($head), $tail);
	}

command_let_assignments_variable
	= name:symbol _ "as" _ value:expression
	{
		return array ($name, $value);
	}

symbol "symbol"
	= $ ([_A-Za-z] [_0-9A-Za-z]*)

// Expression tree

expression
	= value:expression_boolean_or

expression_boolean_or
	= lhs:expression_boolean_and _ "||" _ rhs:expression_boolean_or { return new \Deval\BinaryExpression ($lhs, $rhs, '||'); }
	/ expression_boolean_and

expression_boolean_and
	= lhs:expression_math_additive _ "&&" _ rhs:expression_boolean_and { return new \Deval\BinaryExpression ($lhs, $rhs, '&&'); }
	/ expression_math_additive

expression_math_additive
	= lhs:expression_math_multiplicative _ "+" _ rhs:expression_math_additive { return new \Deval\BinaryExpression ($lhs, $rhs, '+'); }
	/ lhs:expression_math_multiplicative _ "-" _ rhs:expression_math_additive { return new \Deval\BinaryExpression ($lhs, $rhs, '-'); }
	/ expression_math_multiplicative

expression_math_multiplicative
	= lhs:expression_unary _ "*" _ rhs:expression_math_multiplicative { return new \Deval\BinaryExpression ($lhs, $rhs, '*'); }
	/ lhs:expression_unary _ "/" _ rhs:expression_math_multiplicative { return new \Deval\BinaryExpression ($lhs, $rhs, '/'); }
	/ lhs:expression_unary _ "%" _ rhs:expression_math_multiplicative { return new \Deval\BinaryExpression ($lhs, $rhs, '%'); }
	/ expression_unary

expression_unary
	= "~" _ value:expression_unary { return new \Deval\UnaryExpression ($value, '~'); }
	/ "!" _ value:expression_unary { return new \Deval\UnaryExpression ($value, '!'); }
	/ "+" _ value:expression_unary { return new \Deval\UnaryExpression ($value, '+'); }
	/ "-" _ value:expression_unary { return new \Deval\UnaryExpression ($value, '-'); }
	/ expression_invoke

expression_invoke
	= caller:expression_unit _ "(" head:expression _ tail:("," _ token:expression _ { return $token; })* ")"
	{
		return new \Deval\InvokeExpression ($caller, array_merge (array ($head), $tail));
	}
	/ expression_unit

expression_unit
	= "(" _ value:expression _ ")" { return $value; }
	/ value:constant { return new \Deval\ConstantExpression ($value); }
	/ value:construct { return new \Deval\ArrayExpression ($value); }
	/ value:symbol { return new \Deval\SymbolExpression ($value); }

constant
	= integer
	/ string

construct
	= "[" _ head:expression _ tail:("," _ token:expression _ { return $token; })* "]"
	{
		return array_merge (array ($head), $tail);
	}

integer "integer"
	= digits:$[0-9]+ { return intval ($digits); }

string "string"
	= '"' chars:string_char* '"' { return implode ('', $chars); }

string_char
	= [^\0-\x1F\x22\x5C]
	/ "\\" sequence:(
		  '"'
		/ "\\"
		/ "/"
		/ "b" { return "\b"; }
		/ "f" { return "\f"; }
		/ "n" { return "\n"; }
		/ "r" { return "\r"; }
		/ "t" { return "\t"; }
		/ "u" digits:$(hex hex hex hex) { return chr_unicode (hexdec ($digits)); }
    )
	{
		return $sequence;
	}

hex
	= [0-9a-f]i
