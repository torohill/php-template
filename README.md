# PHP Template

A very basic template library that uses PHP as the templating language.

## Usage

Works like this:

1. Create a `Template` object.
1. Assign template variables as member variables.
1. Call `Template->execute()` to retrieve the rendered template.

It is also possible to use the static `Template::render()` method to assign variables and execute in a single call.

example.php

	:::php
		<?php
		require_once 'PhpTemplate/Template.php';
		use PhpTemplate\Template;

		$t = new Template('hello.php');
		$t->greeting = 'Hello';
		$t->who = 'world';

		echo $t->execute();

		// Alternatives:
		// echo $t->execute(array('greeting' => 'Hello', 'who' => 'world'));
		// echo $t->set(array('greeting' => 'Hello', 'who' => 'world'))->execute();

hello.php

	:::php
		<?= $greeting ?>, <?= $who ?>!

## Installation

Add the following to the requires section in your `composer.json` file and then run `composer install`.

	"torohill/php-template": "2.*"

## Requirements

PHP >= 5.3

## Contributing

Report bugs and submit pull request at <https://bitbucket.org/torohill/php-template/>.

## Testing

Unit tests not yet implemented ...

## License

PHP Templates is released under the MIT License, see the `LICENSE` file for details.
