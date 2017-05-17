let fs = require('fs');
let pegjs = require ('pegjs');
let phppegjs = require ('php-pegjs');

let input = 'src/parser.pegjs';
let output = 'src/parser.php';

fs.readFile (input, 'utf8', function (err, data)
{
	if (err)
		return console.log (err);

	var parser = pegjs.buildParser (data, {
		cache: true,
		plugins: [phppegjs]
	});

	fs.writeFile (output, parser, function (err)
	{
		if (err)
			return console.log (err);

		console.log ('built ' + output);
	}); 
});
