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

			form .field {
				margin:		0 0 8px 0;
			}

			form input,
			form textarea {
				box-sizing:	border-box;
				width:		100%;
			}

			form input[type="submit"] {
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

			form input[type="text"],
			form select,
			form textarea {
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

$builtin = isset($_GET['builtin']) ? (string)$_GET['builtin'] : '';
$constants = isset($_GET['constants']) ? (array)json_decode($_GET['constants'], true) : array('greeting' => 'Hello');
$template = isset($_GET['template']) ? (string)$_GET['template'] : '{{ $ greeting }}, {{ $ subject }}!';
$variables = isset($_GET['variables']) ? (array)json_decode($_GET['variables'], true) : array('subject' => 'World');

?>
		<div class="window">
			<h1>Input template</h1>
			<form class="body" method="GET">
				<div class="field">
					<label for="template">Deval template source code:</label>
					<textarea name="template" rows="8"><?php echo htmlspecialchars($template); ?></textarea>
				</div>
				<div class="field">
					<label for="constants">Constant key/value pairs (as a JSON object):</label>
					<input type="text" name="constants" value="<?php echo htmlspecialchars(json_encode((object)$constants)); ?>" />
				</div>
				<div class="field">
					<label for="variables">Variable key/value pairs (as a JSON object):</label>
					<input type="text" name="variables" value="<?php echo htmlspecialchars(json_encode((object)$variables)); ?>" />
				</div>
				<div class="field">
					<label for="builtin">Inject additional builtin functions:</label>
					<select name="builtin">
						<option value="">None</option>
						<option value="deval"<?php if ($builtin === 'deval') {
    echo ' selected';
} ?>>Deval functions</option>
						<option value="php"<?php if ($builtin === 'php') {
    echo ' selected';
} ?>>PHP functions</option>
					</select>
				</div>
				<input type="submit" value="OK" />
			</form>
		</div>
<?php

require '../src/deval.php';

if ($template !== '') {
    $exception = null;

    try {
        $renderer = new Deval\StringRenderer($template);
        $source1 = $renderer->source();

        try {
            switch ($builtin) {
                case 'deval':
                    $renderer->inject(Deval\Builtin::deval());

                    break;

                case 'php':
                    $renderer->inject(Deval\Builtin::php());

                    break;
            }

            $renderer->inject($constants);
            $source2 = $renderer->source();

            try {
                $output = $renderer->render($variables);
            } catch (Exception $exception) {
                $output = 'n/a (rendering failed)';
            }
        } catch (Exception $exception) {
            $source2 = 'n/a (injection failed)';
            $output = 'n/a (injection failed)';
        } ?>
		<div class="window">
			<h1>Output code</h1>
			<div class="body">
				<p>Generated code before injection:</p>
				<pre><?php echo htmlspecialchars($source1); ?></pre>
				<p>Generated code after injecting constants:</p>
				<pre><?php echo htmlspecialchars($source2); ?></pre>
				<p>Execution result with injected variables:</p>
				<pre><?php echo htmlspecialchars($output); ?></pre>
			</div>
		</div>
<?php
    } catch (Exception $exception) {
        $source1 = 'n/a (parsing failed)';
        $source2 = 'n/a (parsing failed)';
        $output = 'n/a (parsing failed)';
    }

    if ($exception !== null) {
        ?>
		<div class="window">
			<h1 class="fail">Error</h1>
			<div class="body">
				<p><?php echo htmlspecialchars($exception->getMessage()); ?></p>
			</div>
		</div>
<?php
    }
}

?>
	</body>
</html>
