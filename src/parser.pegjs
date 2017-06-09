
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
	= chars:PlainCharacter+
	{
		return implode ('', $chars);
	}

PlainCharacter
	= brace:"{" char:([^\\{%] / PlainEscape)
	{
		return $brace . $char;
	}
	/ [^\\{]
	/ PlainEscape

PlainEscape
	= "\\" char:("\\" / "{" / "%")
	{
		return $char;
	}

// Command tree

Command "command block"
	= CommandFor
	/ CommandIf
	/ CommandImport
	/ CommandInclude
	/ CommandLabel
	/ CommandLet

CommandImport "import command"
	= "import" _ path:Path _ BlockCommandEnd _ blocks:CommandImportBlock* BlockCommandBegin _ "end"
	{
		$bodies = array_map (function ($b) { return $b[1]; }, $blocks);
		$names = array_map (function ($b) { return $b[0]; }, $blocks);

		return \Deval\Compiler::parse_file ($path, array_combine ($names, $bodies));
	}

CommandImportBlock
	= BlockCommandBegin _ "block" _ name:Symbol _ BlockCommandEnd body:Content
	{
		return array ($name, $body);
	}

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

CommandInclude "include command"
	= "include" _ path:Path
	{
		return \Deval\Compiler::parse_file ($path);
	}

CommandLabel
	= "label" _ name:Symbol
	{
		return new \Deval\LabelBlock ($name);
	}

CommandLet "let command"
	= "let" _ assignments:CommandLetAssignments _ BlockCommandEnd body:Content BlockCommandBegin _ "end"
	{
		return new \Deval\LetBlock ($assignments, $body);
	}

CommandLetAssignments "assignments"
	= head:CommandLetAssignmentsPair _ tail:("," _ token:CommandLetAssignmentsPair { return $token; })*
	{
		return array_merge (array ($head), $tail);
	}

CommandLetAssignmentsPair "assignment"
	= name:Symbol _ "=" _ value:Expression
	{
		return array ($name, $value);
	}

Path "path"
	= chars:PathChar+ { return implode ('', $chars); }

PathChar
	= [!-~]
	/ "\\" sequence:(
		  "\\"
		/ "u" digits:$(Hex Hex Hex Hex) { return chr_unicode (hexdec ($digits)); }
    )
	{
		return $sequence;
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
	= lhs:ExpressionCompare _ "&&" _ rhs:ExpressionBooleanAnd { return new \Deval\BinaryExpression ($lhs, $rhs, '&&'); }
	/ ExpressionCompare

ExpressionCompare
	= lhs:ExpressionStringCat _ op:ExpressionCompareOperator _ rhs:ExpressionCompare { return new \Deval\BinaryExpression ($lhs, $rhs, $op); }
	/ ExpressionStringCat

ExpressionCompareOperator
	= "=="
	{
		return "===";
	}
	/ "!="
	{
		return "!==";
	}
	/ ">="
	/ ">"
	/ "<="
	/ "<"

ExpressionStringCat
	= lhs:ExpressionMathAdd _ "." _ rhs:ExpressionStringCat { return new \Deval\BinaryExpression ($lhs, $rhs, '.'); }
	/ ExpressionMathAdd

ExpressionMathAdd
	= lhs:ExpressionMathMul _ op:ExpressionMathAddOperator _ rhs:ExpressionMathAdd { return new \Deval\BinaryExpression ($lhs, $rhs, $op); }
	/ ExpressionMathMul

ExpressionMathAddOperator
	= "+"
	/ "-"

ExpressionMathMul
	= lhs:ExpressionUnary _ op:ExpressionMathMulOperator _ rhs:ExpressionMathMul { return new \Deval\BinaryExpression ($lhs, $rhs, $op); }
	/ ExpressionUnary

ExpressionMathMulOperator
	= "*"
	/ "/"
	/ "%"

ExpressionUnary
	= op:ExpressionUnaryOperator _ value:ExpressionUnary { return new \Deval\UnaryExpression ($value, $op); }
	/ ExpressionInvoke

ExpressionUnaryOperator
	= "~"
	/ "!"
	/ "+"
	/ "-"

ExpressionInvoke
	= caller:ExpressionMember _ arguments_list:ExpressionInvokeArguments+
	{
		$expression = $caller;

		foreach ($arguments_list as $arguments)
			$expression = new \Deval\InvokeExpression ($expression, $arguments);

		return $expression;
	}
	/ ExpressionMember

ExpressionInvokeArguments
	= "(" _ ")"
	{
		return array ();
	}
	/ "(" head:Expression _ tail:("," _ token:Expression _ { return $token; })* ")"
	{
		return array_merge (array ($head), $tail);
	}

ExpressionMember
	= source:ExpressionPrimary _ indices:ExpressionMemberIndex+
	{
		$expression = $source;

		foreach ($indices as $index)
			$expression = new \Deval\MemberExpression ($expression, $index);

		return $expression;
	}
	/ ExpressionPrimary

ExpressionMemberIndex
	= "[" _ index:Expression _ "]"
	{
		return $index;
	}
	/ "." index:Symbol
	{
		return new \Deval\ConstantExpression ($index);
	}

ExpressionPrimary
	= names:LambdaNames _ "=>" _ body:Expression
	{
		return new \Deval\LambdaExpression ($names, $body);
	}
	/ value:Array
	{
		return new \Deval\ArrayExpression ($value);
	}
	/ value:Scalar
	{
		return new \Deval\ConstantExpression ($value);
	}
	/ value:Symbol
	{
		return new \Deval\SymbolExpression ($value);
	}
	/ "(" _ value:Expression _ ")"
	{
		return $value;
	}

Array
	= "[" _ "]"
	{
		return array ();
	}
	/ "[" _ head:ArrayElement _ tail:("," _ token:ArrayElement _ { return $token; })* "]"
	{
		return array_merge (array ($head), $tail);
	}

ArrayElement
	= key:Scalar _ ":" _ value:Expression
	{
		return array ($key, $value);
	}
	/ value:Expression
	{
		return array (null, $value);
	}

LambdaNames
	= "(" _ ")"
	{
		return array ();
	}
	/ "(" _ head:Symbol _ tail:("," _ token:Symbol _ { return $token; })* ")"
	{
		return array_merge (array ($head), $tail);
	}

Scalar
	= Boolean
	/ Integer
	/ Null
	/ String

Boolean "boolean"
	= "false"
	{
		return false;
	}
	/ "true"
	{
		return true;
	}

Integer "integer"
	= digits:$[0-9]+
	{
		return intval ($digits);
	}

Null "null"
	= "null"
	{
		return null;
	}

String "string"
	= '"' chars:StringChar* '"'
	{
		return implode ('', $chars);
	}

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
