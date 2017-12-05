let fs = require('fs');
let pegjs = require ('pegjs');
let phppegjs = require ('php-pegjs');

let input = process.argv[2];
let output = process.argv[3];

fs.readFile (input, 'utf8', function (err, data)
{
	if (err)
		return console.log (err);

	var parser = pegjs.buildParser (data, {
		plugins: [phppegjs]
	});

	fs.writeFile (output, parser, function (err)
	{
		if (err)
			return console.log (err);

		console.log ("The file was saved!");
	}); 
});
