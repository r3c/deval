<!DOCTYPE html>
<html>
	<head>
		<style type="text/css">
			body {
				padding:	32px;
				margin:		0;
			}

			body,
			input,
			textarea {
				font:	normal normal normal 12px tahoma;
			}

			.window {
				padding:		2px;
				margin:			0 auto 16px auto;
				background:		white;
				border:			1px solid #9AADBE;
				border-radius:	6px;
				window-shadow:		0 3px 10px #C0C0C0;  
			}

			.window h1 {
				padding:		2px 8px;
				margin:			0;
				background:		#75A3D7;
				border-color:	#BCC3CB #6E8DAA #6E8DAA #B8C4D2;
				border-radius:	4px;
				border-style:	solid;
				border-width:	1px;
				font-size:		140%;
				font-variant:	small-caps;
				font-weight:	bold;
				color:			#FFFFFF;
			}

			.window h1.fail {
				background:		#F0A0A0;
				border-color:	#FFD0D0 #D08080 #D08080 #FFD0D0;
			}

			.window .body {
				padding:	6px 8px;
				margin:		2px 0 0 0;
				background:	#C3D3E5;
			}

			form {
				margin:		0;
				padding:	0;
			}

			input,
			textarea {
				box-sizing:	border-box;
				width:		100%;
				margin:		0 0 8px 0;
			}

			input[type="submit"] {
				width:			100px;
				padding:		4px 8px;
				margin:			4px 4px 4px 0;
				background:		#E0E0E0;
				border-color:	#FFFFFF #C0C0C0 #C0C0C0 #FFFFFF;
				border-radius:	3px;
				border-style:	solid;
				border-width:	1px;
				line-height:	100%;
				color:			#404040;
			}

			input[type="text"],
			textarea {
				padding:		2px 4px;
				border-color:	#8CA0AE #C0C0C0 #C0C0C0 #8CA0AE;
				border-radius:	3px;
				border-style:	solid;
				border-width:	1px;
				color: 			#39728C;
			}

			label,
			p {
				display:		block;
				margin:			0 0 4px 0;
				font-weight:	bold;
			}

			pre {
				white-space:	pre-line;
			}
		</style>
		<title>Deval Demo</title>
	</head>
	<body>
<?php

$constants = isset ($_GET['constants']) ? (array)json_decode ($_GET['constants'], true) : array ('greeting' => 'Hello');
$template = isset ($_GET['template']) ? $_GET['template'] : '{{ greeting }}, {{ subject }}!';
$volatiles = isset ($_GET['volatiles']) ? (array)json_decode ($_GET['volatiles'], true) : array ('subject' => 'World');

?>
		<div class="window">
			<h1>Input template</h1>
			<form class="body" method="GET">
				<label for="template">Deval template source code:</label>
				<textarea name="template" rows="8"><?php echo htmlspecialchars ($template); ?></textarea>
				<label for="constants">Constant key/value pairs (as a JSON object):</label>
				<input type="text" name="constants" value="<?php echo htmlspecialchars (json_encode ((object)$constants)); ?>" />
				<label for="volatiles">Volatile key/value pairs (as a JSON object):</label>
				<input type="text" name="volatiles" value="<?php echo htmlspecialchars (json_encode ((object)$volatiles)); ?>" />
				<input type="submit" value="OK" />
			</form>
		</div>
<?php

require '../src/deval.php';

if ($template !== '')
{
	try
	{
		$renderer = new Deval\BasicRenderer ($template);
		$output1 = $renderer->source;
		$renderer = new Deval\BasicRenderer ($template, $constants);
		$output2 = $renderer->source;
		$output3 = $renderer->render ($volatiles);

?>
		<div class="window">
			<h1>Output code</h1>
			<div class="body">
				<p>Generated code before injection:</p>
				<pre><?php echo htmlspecialchars ($output1); ?></pre>
				<p>Generated code after injecting constants:</p>
				<pre><?php echo htmlspecialchars ($output2); ?></pre>
				<p>Execution result with injected volatiles:</p>
				<pre><?php echo htmlspecialchars ($output3); ?></pre>
			</div>
		</div>
<?php

	}
	catch (Deval\CompileException $exception)
	{

?>
		<div class="window">
			<h1 class="fail">Compile error</h1>
			<div class="body">
				<p><?php echo htmlspecialchars ($exception->getMessage ()); ?></p>
			</div>
		</div>
<?php

	}
	catch (Deval\RuntimeException $exception)
	{

?>
		<div class="window">
			<h1 class="fail">Runtime error</h1>
			<div class="body">
				<p><?php echo htmlspecialchars ($exception->getMessage ()); ?></p>
			</div>
		</div>
<?php

	}
}

?>
	</body>
</html>
