
// Document tree

Document
	= Content

Content
	= blocks:Block*
	{
		return \Deval\ConcatBlock::create ($blocks);
	}

// Block tree

Block
	= BlockCommandBegin _ command:Command _ BlockCommandEnd { return $command; }
	/ BlockEchoBegin _ expression:Expression _ BlockEchoEnd { return new \Deval\EchoBlock ($expression); }
	/ plain:Plain { return new \Deval\PlainBlock ($plain); }

BlockCommandBegin
	= "{%"

BlockCommandEnd
	= "%}"

BlockEchoBegin
	= "{{"

BlockEchoEnd
	= "}}"

_ "whitespace"
	= [ \t\n\r]*

// Plain text tree

Plain
	= chars:PlainCharacter+ { return implode ('', $chars); }

PlainCharacter
	= brace:"{" char:PlainSafe { return $brace . $char; }
	/ PlainSafe

PlainSafe
	= [^\\{%]
	/ "\\" sequence:("\\" / "{" / "%")
	{
		return $sequence;
	}

// Command tree

Command "command block"
	= command:CommandFor
	/ command:CommandIf
	/ command:CommandLet

CommandFor "for command"
	= "for" _ key:CommandForKey? value:Symbol _ "in" _ source:Expression _ BlockCommandEnd body:Content fallback:CommandForEmpty? BlockCommandBegin _ "end"
	{
		return new \Deval\ForBlock ($source, $key, $value, $body, $fallback);
	}

CommandForEmpty
	= BlockCommandBegin _ "empty" _ BlockCommandEnd body:Content
	{
		return $body;
	}

CommandForKey
	= key:Symbol _ "," _
	{
		return $key;
	}

CommandIf "if command"
	= "if" _ condition:Expression _ BlockCommandEnd body:Content branches:CommandIfElseif* fallback:CommandIfElse? BlockCommandBegin _ "end"
	{
		return new \Deval\IfBlock (array_merge (array (array ($condition, $body)), $branches), $fallback);
	}

CommandIfElseif
	= BlockCommandBegin _ "else" _ "if" _ condition:Expression _ BlockCommandEnd body:Content
	{
		return array ($condition, $body);
	}

CommandIfElse
	= BlockCommandBegin _ "else" _ BlockCommandEnd body:Content
	{
		return $body;
	}

CommandLet "let command"
	= "let" _ assignments:CommandLetVariables _ BlockCommandEnd body:Content BlockCommandBegin _ "end"
	{
		return new \Deval\LetBlock ($assignments, $body);
	}

CommandLetAssignment
	= name:Symbol _ "as" _ value:Expression
	{
		return array ($name, $value);
	}

CommandLetVariables
	= head:CommandLetAssignment _ tail:("," _ token:CommandLetAssignment { return $token; })*
	{
		return array_merge (array ($head), $tail);
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
	= caller:ExpressionMember _ "(" _ ")"
	{
		return new \Deval\InvokeExpression ($caller, array ());
	}
	/ caller:ExpressionMember _ "(" head:Expression _ tail:("," _ token:Expression _ { return $token; })* ")"
	{
		return new \Deval\InvokeExpression ($caller, array_merge (array ($head), $tail));
	}
	/ ExpressionMember

ExpressionMember
	= source:ExpressionPrimary _ indices:ExpressionMemberIndex+
	{
		return new \Deval\MemberExpression ($source, $indices);
	}
	/ ExpressionPrimary

ExpressionMemberIndex
	= "[" _ index:Expression _ "]" _ { return $index ; }
	/ "." index:Symbol { return new \Deval\ConstantExpression ($index); }

ExpressionPrimary
	= "(" _ value:Expression _ ")" { return $value; }
	/ value:Array { return new \Deval\ArrayExpression ($value); }
	/ value:Constant { return new \Deval\ConstantExpression ($value); }
	/ value:Symbol { return new \Deval\SymbolExpression ($value); }

Array
	= "[" _ "]"
	{
		return array ();
	}
	/ "[" _ head:Expression _ tail:("," _ token:Expression _ { return $token; })* "]"
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
