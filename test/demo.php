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
	$parser = new PhpPegJs\Parser ();

	try
	{
		echo '<pre>' . (string)$parser->parse ($source) . '</pre>';
	}
	catch (PhpPegJs\SyntaxError $error)
	{
		echo 'Syntax error: ' . $error->getMessage ();
	}
}

?>
