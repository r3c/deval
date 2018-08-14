
// Document tree

Document
	= Content

Content
	= blocks:Block*
	{
		return new \Deval\ConcatBlock ($blocks);
	}

// Block tree

Block
	= BlockBegin _ command:Command _ BlockEnd
	{
		return $command;
	}
	/ plain:Plain
	{
		return new \Deval\PlainBlock ($plain);
	}

BlockBegin
	= "{{"

BlockEnd
	= "}}"

_ "ignore"
	= (Comment / Blank)*

Comment
	= "/*" [^*]* "*"+ ([^/*] [^*]* "*"+)* "/"

Blank "blank"
	= [ \t\n\r]

// Plain text tree

Plain
	= chars:PlainCharacter+
	{
		return implode ('', $chars);
	}

PlainCharacter
	= brace:"{" char:[^{]
	{
		return $brace . $char;
	}
	/ brace:"{" !.
	{
		return $brace;
	}
	/ [^{]

// Command tree

Command "command block"
	= CommandEcho
	/ CommandExtend
	/ CommandFor
	/ CommandIf
	/ CommandInclude
	/ CommandLabel
	/ CommandLet
	/ CommandUnwrap
	/ CommandWrap

CommandEcho "echo command"
	= "$" _ expression:Expression
	{
		return new \Deval\EchoBlock ($expression);
	}

CommandExtend "extend command"
	= "extend" _ path:Path _ BlockEnd _ blocks:CommandExtendBlock* BlockBegin _ "end"
	{
		$bodies = array_map (function ($b) { return $b[1]; }, $blocks);
		$names = array_map (function ($b) { return $b[0]; }, $blocks);

		return \Deval\Compiler::parse_file ($path, array_combine ($names, $bodies));
	}

CommandExtendBlock
	= BlockBegin _ "block" _ name:Symbol _ BlockEnd body:Content
	{
		return array ($name, $body);
	}

CommandFor "for command"
	= "for" _ key:CommandForKey? value:Symbol _ "in" _ source:Expression _ BlockEnd loop:Content empty:CommandForEmpty? BlockBegin _ "end"
	{
		return new \Deval\ForBlock ($source, $key, $value, $loop, $empty ?: new \Deval\VoidBlock ());
	}

CommandForEmpty
	= BlockBegin _ "empty" _ BlockEnd body:Content
	{
		return $body;
	}

CommandForKey
	= key:Symbol _ "," _
	{
		return $key;
	}

CommandIf "if command"
	= "if" _ condition:Expression _ BlockEnd body:Content branches:CommandIfElseif* fallback:CommandIfElse? BlockBegin _ "end"
	{
		return new \Deval\IfBlock (array_merge (array (array ($condition, $body)), $branches), $fallback ?: new \Deval\VoidBlock ());
	}

CommandIfElseif
	= BlockBegin _ "else" _ "if" _ condition:Expression _ BlockEnd body:Content
	{
		return array ($condition, $body);
	}

CommandIfElse
	= BlockBegin _ "else" _ BlockEnd body:Content
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
	= "let" _ assignments:CommandLetAssignments _ BlockEnd body:Content BlockBegin _ "end"
	{
		return new \Deval\LetBlock ($assignments, $body);
	}

CommandLetAssignments "assignments"
	= head:CommandLetAssignmentsPair _ tail:("," _ token:CommandLetAssignmentsPair _ { return $token; })*
	{
		return array_merge (array ($head), $tail);
	}

CommandLetAssignmentsPair "assignment"
	= name:Symbol _ "=" _ value:Expression
	{
		return array ($name, $value);
	}

CommandUnwrap "unwrap command"
	= "unwrap" _ BlockEnd body:Content BlockBegin _ "end"
	{
		return new \Deval\UnwrapBlock ($body);
	}

CommandWrap "wrap command"
	= "wrap" _ value:Expression _ BlockEnd body:Content BlockBegin _ "end"
	{
		return $body->wrap ($value);
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
	= $([_A-Za-z] [_0-9A-Za-z]*)

// Expression tree

Expression
	= ExpressionBooleanOr

ExpressionBooleanOr
	= head:ExpressionBooleanAnd tail:(_ "||" _ rhs:ExpressionBooleanOr { return $rhs; })*
	{
		return array_reduce ($tail, function ($lhs, $rhs)
		{
			return new \Deval\BinaryExpression ('||', $lhs, $rhs);
		}, $head);
	}
	/ ExpressionBooleanAnd

ExpressionBooleanAnd
	= head:ExpressionCompare tail:(_ "&&" _ rhs:ExpressionBooleanAnd { return $rhs; })*
	{
		return array_reduce ($tail, function ($lhs, $rhs)
		{
			return new \Deval\BinaryExpression ('&&', $lhs, $rhs);
		}, $head);
	}
	/ ExpressionCompare

ExpressionCompare
	= head:ExpressionMathAdd tail:(_ operator:ExpressionCompareOperator _ next:ExpressionCompare { return array ($operator, $next); })*
	{
		return array_reduce ($tail, function ($lhs, $next)
		{
			return new \Deval\BinaryExpression ($next[0], $lhs, $next[1]);
		}, $head);
	}
	/ ExpressionMathAdd

ExpressionCompareOperator
	= "=="
	/ "!="
	/ ">="
	/ ">"
	/ "<="
	/ "<"

ExpressionMathAdd
	= head:ExpressionMathMul tail:(_ operator:ExpressionMathAddOperator _ next:ExpressionMathMul { return array ($operator, $next); })*
	{
		return array_reduce ($tail, function ($lhs, $next)
		{
			return new \Deval\BinaryExpression ($next[0], $lhs, $next[1]);
		}, $head);
	}
	/ ExpressionMathMul

ExpressionMathAddOperator
	= "+"
	/ "-"

ExpressionMathMul
	= head:ExpressionPrefix tail:(_ operator:ExpressionMathMulOperator _ next:ExpressionPrefix { return array ($operator, $next); })*
	{
		return array_reduce ($tail, function ($lhs, $next)
		{
			return new \Deval\BinaryExpression ($next[0], $lhs, $next[1]);
		}, $head);
	}
	/ ExpressionPrefix

ExpressionMathMulOperator
	= "*"
	/ "/"
	/ "%"

ExpressionPrefix
	= moment:ExpressionPrefixMoment _ operand:ExpressionPrefix
	{
		return new \Deval\DeferExpression ($moment, $operand);
	}
	/ operator:ExpressionPrefixOperator _ operand:ExpressionPrefix
	{
		return new \Deval\UnaryExpression ($operator, $operand);
	}
	/ ExpressionPostfix

ExpressionPrefixMoment
	= "(-)"
	{
		return false;
	}
	/ "(+)"
	{
		return true;
	}

ExpressionPrefixOperator
	= "~"
	/ "!"
	/ "+"
	/ "-"

ExpressionPostfix
	= expression:ExpressionPrimary operators:(_ token:ExpressionPostfixOperator { return $token; })*
	{
		foreach ($operators as $operator)
			$expression = $operator ($expression);

		return $expression;
	}

ExpressionPostfixOperator
	= "(" _ ")"
	{
		return function ($expression)
		{
			return new \Deval\InvokeExpression ($expression, array ());
		};
	}
	/ "(" _ head:Expression _ tail:("," _ token:Expression _ { return $token; })* ")"
	{
		$arguments = array_merge (array ($head), $tail);

		return function ($expression) use ($arguments)
		{
			return new \Deval\InvokeExpression ($expression, $arguments);
		};
	}
	/ "[" _ index:Expression _ "]"
	{
		return function ($expression) use ($index)
		{
			return new \Deval\MemberExpression ($expression, $index);
		};
	}
	/ "." _ index:Symbol
	{
		return function ($expression) use ($index)
		{
			return new \Deval\MemberExpression ($expression, new \Deval\ConstantExpression ($index));
		};
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
		return new \Deval\GroupExpression ($value);
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
	= key:Expression _ ":" _ value:Expression
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
	/ Float
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

Float "floating point number"
	= digits:$([0-9]* "." [0-9]+)
	{
		return (float)$digits;
	}

Integer "integer"
	= digits:$[0-9]+
	{
		return (int)$digits;
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
