<?php

$dynamic_variables = isset ($_GET['dynamic_variables']) ? (array)json_decode ($_GET['dynamic_variables'], true) : array ();
$static_variables = isset ($_GET['static_variables']) ? (array)json_decode ($_GET['static_variables'], true) : array ();
$template = isset ($_GET['template']) ? $_GET['template'] : '';

?>
<form method="GET">
	<textarea name="template" rows="8" style="width: 100%;"><?php echo htmlspecialchars ($template); ?></textarea>
	<input name="static_variables" style="width: 100%;" value="<?php echo htmlspecialchars (json_encode ((object)$static_variables)); ?>" />
	<input name="dynamic_variables" style="width: 100%;" value="<?php echo htmlspecialchars (json_encode ((object)$dynamic_variables)); ?>" />
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

		$renderer = new Deval\BasicRenderer ($template, $static_variables);

		echo "injected:\n";
		echo htmlspecialchars ($renderer->source) . "\n";
		echo "\n";

		echo "executed:\n";
		echo $renderer->render ($dynamic_variables) . "\n";
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
