<?php

$executes = isset ($_GET['executes']) ? (array)json_decode ($_GET['executes'], true) : array ();
$injects = isset ($_GET['injects']) ? (array)json_decode ($_GET['injects'], true) : array ();
$template = isset ($_GET['template']) ? $_GET['template'] : '';

?>
<form method="GET">
	<textarea name="template" rows="8" style="width: 100%;"><?php echo htmlspecialchars ($template); ?></textarea>
	<input name="injects" style="width: 100%;" value="<?php echo htmlspecialchars (json_encode ((object)$injects)); ?>" />
	<input name="executes" style="width: 100%;" value="<?php echo htmlspecialchars (json_encode ((object)$executes)); ?>" />
	<input type="submit" value="OK" />
</form>
<?php

require '../src/deval.php';

if ($template !== '')
{
	try
	{
		$compiler = new Deval\Compiler ($template);
		$variables = array ();

		echo '<pre>';
		echo "original:\n";
		echo '  - source = ' . htmlspecialchars ($compiler->compile ($variables)) . "\n";
		echo '  - variables = ' . htmlspecialchars (implode (', ', $variables)) . "\n";

		$compiler->inject ($injects);
		$variables = array ();

		echo "injected:\n";
		echo '  - source = ' . htmlspecialchars ($compiler->compile ($variables)) . "\n";
		echo '  - variables = ' . htmlspecialchars (implode (', ', $variables)) . "\n";

		echo "executed:\n";
		echo '  - output = ' . Deval\Executor::code ($compiler->compile (), $executes) . "\n";
		echo '</pre>';
	}
	catch (PhpPegJs\SyntaxError $error)
	{
		echo 'Syntax error: ' . $error->getMessage ();
	}
}

?>
