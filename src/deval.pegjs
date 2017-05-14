
// Document tree

Document
	= Content

Content
	= blocks:Block*
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

Block
	= "{{" _ command:Command _ "}}" { return $command; }
	/ plain:Plain { return new \Deval\PlainBlock ($plain); }

_ "whitespace"
	= [ \t\n\r]*

// Plain text tree

Plain
	= chars:CharRaw+ { return implode ('', $chars); }

CharRaw
	= brace:"{" char:CharSafe { return $brace . $char; }
	/ CharSafe

CharSafe
	= [^\\{]
	/ "\\" sequence:(
		  "\\"
		/ "{"
	) { return $sequence; }

// Command tree

Command "command block"
	= command:CommandBuffer
	/ command:CommandEcho
	/ command:CommandFor
	/ command:CommandIf
	/ command:CommandLet

CommandBuffer "buffer command"
	= "buffer" _ name:Symbol _ "}}" _ body:Content _ "{{" _ "end"
	{
		return new \Deval\BufferBlock ($name, $body);
	}

CommandEcho "echo command"
	= "$" _ value:Expression
	{
		return new \Deval\EchoBlock ($value);
	}

CommandFor "for command"
	= "for" _ key:(token:Symbol _ "," _ { return $token; })? value:Symbol _ "in" _ source:Expression _ "}}" body:Content empty:CommandForEmpty? "{{" _ "end"
	{
		return new \Deval\ForBlock ($source, $key, $value, $body, $empty);
	}

CommandForEmpty
	= "{{" _ "empty" _ "}}" body:Content
	{
		return $body;
	}

CommandIf "if command"
	= "if" _ condition:Expression _ "}}" body:Content branches:CommandIfElseif* fallback:CommandIfElse? "{{" _ "end"
	{
		return new \Deval\IfBlock (array_merge (array (array ($condition, $body)), $branches), $fallback);
	}

CommandIfElseif
	= "{{" _ "else" _ "if" _ condition:Expression _ "}}" body:Content
	{
		return array ($condition, $body);
	}

CommandIfElse
	= "{{" _ "else" _ "}}" body:Content
	{
		return $body;
	}

CommandLet "let command"
	= "let" _ assignments:CommandLetAssignments _ "}}" _ body:Content _ "{{" _ "end"
	{
		return new \Deval\LetBlock ($assignments, $body);
	}

CommandLetAssignments
	= head:CommandLetAssignmentsVariable _ tail:("," _ token:CommandLetAssignmentsVariable { return $token; })*
	{
		return array_merge (array ($head), $tail);
	}

CommandLetAssignmentsVariable
	= name:Symbol _ "as" _ value:Expression
	{
		return array ($name, $value);
	}

Symbol "symbol"
	= $ ([_A-Za-z] [_0-9A-Za-z]*)

// Expression tree

Expression
	= value:ExpressionBooleanOr

ExpressionBooleanOr
	= lhs:ExpressionBooleanAnd _ "||" _ rhs:ExpressionBooleanOr { return new \Deval\BinaryExpression ($lhs, $rhs, '||'); }
	/ ExpressionBooleanAnd

ExpressionBooleanAnd
	= lhs:ExpressionMathAdd _ "&&" _ rhs:ExpressionBooleanAnd { return new \Deval\BinaryExpression ($lhs, $rhs, '&&'); }
	/ ExpressionMathAdd

ExpressionMathAdd
	= lhs:ExpressionMathMul _ "+" _ rhs:ExpressionMathAdd { return new \Deval\BinaryExpression ($lhs, $rhs, '+'); }
	/ lhs:ExpressionMathMul _ "-" _ rhs:ExpressionMathAdd { return new \Deval\BinaryExpression ($lhs, $rhs, '-'); }
	/ ExpressionMathMul

ExpressionMathMul
	= lhs:ExpressionUnary _ "*" _ rhs:ExpressionMathMul { return new \Deval\BinaryExpression ($lhs, $rhs, '*'); }
	/ lhs:ExpressionUnary _ "/" _ rhs:ExpressionMathMul { return new \Deval\BinaryExpression ($lhs, $rhs, '/'); }
	/ lhs:ExpressionUnary _ "%" _ rhs:ExpressionMathMul { return new \Deval\BinaryExpression ($lhs, $rhs, '%'); }
	/ ExpressionUnary

ExpressionUnary
	= "~" _ value:ExpressionUnary { return new \Deval\UnaryExpression ($value, '~'); }
	/ "!" _ value:ExpressionUnary { return new \Deval\UnaryExpression ($value, '!'); }
	/ "+" _ value:ExpressionUnary { return new \Deval\UnaryExpression ($value, '+'); }
	/ "-" _ value:ExpressionUnary { return new \Deval\UnaryExpression ($value, '-'); }
	/ ExpressionInvoke

ExpressionInvoke
	= caller:ExpressionMember _ "(" head:Expression _ tail:("," _ token:Expression _ { return $token; })* ")"
	{
		return new \Deval\InvokeExpression ($caller, array_merge (array ($head), $tail));
	}
	/ ExpressionMember

ExpressionMember
	= source:ExpressionPrimary _ indices:ExpressionMemberIndex*
	{
		return new \Deval\MemberExpression ($source, $indices);
	}
	/ ExpressionPrimary

ExpressionMemberIndex
	= "[" _ index:Expression _ "]" { return $index ; }
	/ "." index:Symbol { return new \Deval\ConstantExpression ($index); }

ExpressionPrimary
	= "(" _ value:Expression _ ")" { return $value; }
	/ value:Array { return new \Deval\ArrayExpression ($value); }
	/ value:Constant { return new \Deval\ConstantExpression ($value); }
	/ value:Symbol { return new \Deval\SymbolExpression ($value); }

Array
	= "[" _ head:Expression _ tail:("," _ token:Expression _ { return $token; })* "]"
	{
		return array_merge (array ($head), $tail);
	}

Constant
	= Integer
	/ String

Integer "integer"
	= digits:$[0-9]+ { return intval ($digits); }

String "string"
	= '"' chars:StringChar* '"' { return implode ('', $chars); }

StringChar
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
		/ "u" digits:$(Hex Hex Hex Hex) { return chr_unicode (hexdec ($digits)); }
    )
	{
		return $sequence;
	}

Hex
	= [0-9a-f]i
