<?php

$source = isset ($_GET['source']) ? $_GET['source'] : '';
$variables = isset ($_GET['variables']) ? (array)json_decode ($_GET['variables'], true) : array ();

?>
<form method="GET">
	<textarea name="source" rows="8" style="width: 100%;"><?php echo htmlspecialchars ($source); ?></textarea>
	<input name="variables" style="width: 100%;" value="<?php echo htmlspecialchars (json_encode ((object)$variables)); ?>" />
	<input type="submit" value="OK" />
</form>
<?php

require '../src/deval.php';

if ($source !== '')
{
	try
	{
		$document = new Deval\Document ($source);
		$requires = array ();

		echo '<pre>';
		echo "original:\n";
		echo '  - document = ' . htmlspecialchars ($document->generate ($requires)) . "\n";
		echo '  - requires = ' . htmlspecialchars (implode (', ', $requires)) . "\n";

		$document->inject ($variables);
		$requires = array ();

		echo "injected:\n";
		echo '  - document = ' . htmlspecialchars ($document->generate ($requires)) . "\n";
		echo '  - requires = ' . htmlspecialchars (implode (', ', $requires)) . "\n";
		echo '</pre>';
	}
	catch (PhpPegJs\SyntaxError $error)
	{
		echo 'Syntax error: ' . $error->getMessage ();
	}
}

?>
