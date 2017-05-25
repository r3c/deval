<?php

$constants = isset ($_GET['constants']) ? (array)json_decode ($_GET['constants'], true) : array ();
$template = isset ($_GET['template']) ? $_GET['template'] : '';
$volatiles = isset ($_GET['volatiles']) ? (array)json_decode ($_GET['volatiles'], true) : array ();

?>
<form method="GET">
	<textarea name="template" rows="8" style="width: 100%;"><?php echo htmlspecialchars ($template); ?></textarea>
	<input name="constants" style="width: 100%;" value="<?php echo htmlspecialchars (json_encode ((object)$constants)); ?>" />
	<input name="volatiles" style="width: 100%;" value="<?php echo htmlspecialchars (json_encode ((object)$volatiles)); ?>" />
	<input type="submit" value="OK" />
</form>
<?php

require '../src/deval.php';

if ($template !== '')
{
	echo '<pre>';

	try
	{
		$renderer = new Deval\BasicRenderer ($template);

		echo "original:\n";
		echo htmlspecialchars ($renderer->source) . "\n";
		echo "\n";

		$renderer = new Deval\BasicRenderer ($template, $constants);

		echo "injected:\n";
		echo htmlspecialchars ($renderer->source) . "\n";
		echo "\n";

		echo "executed:\n";
		echo $renderer->render ($volatiles) . "\n";
	}
	catch (Deval\CompileException $exception)
	{
		echo $exception->getMessage ();
	}
	catch (Deval\RuntimeException $exception)
	{
		echo $exception->getMessage ();
	}

	echo '</pre>';
}

?>
