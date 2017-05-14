<?php

$source = isset ($_GET['source']) ? $_GET['source'] : '';

?>
<form method="GET">
	<textarea name="source" rows="8" style="width: 100%;"><?php echo htmlspecialchars ($source); ?></textarea>
	<input type="submit" value="OK" />
</form>
<?php

require '../src/deval.php';

if ($source !== '')
{
	$document = new Deval\Document ($source);

	try
	{
		echo '<pre>' . (string)$document . '</pre>';
	}
	catch (PhpPegJs\SyntaxError $error)
	{
		echo 'Syntax error: ' . $error->getMessage ();
	}
}

?>
