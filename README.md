# PHP Template

A very basic template library that uses PHP as the templating language.


## Installation

Add the following to the requires section in your `composer.json` file and then run `composer install`.

	"torohill/php-template": "2.*"


## Requirements

PHP >= 5.3


## Usage

1. Create a `Template` object.
1. Assign template variables as member variables.
1. Call `Template->execute()` to retrieve the rendered template.

It is also possible to use the static `Template::render()` method to assign variables and execute in a single call.

###  Example Usage

example.php

	:::php
		<?php
		require_once 'vendor/autoload.php'; // Require composer autoloader.

		$t = new \PhpTemplate\Template('hello.php');
		$t->greeting = 'Hello';
		$t->who = 'world';

		echo $t->execute();

		// Alternatives:
		// echo $t->execute(array('greeting' => 'Hello', 'who' => 'world'));
		// echo $t->set(array('greeting' => 'Hello', 'who' => 'world'))->execute();
		// echo \PhpTemplate\Template::render('hello.php', array('greeting' => 'Hello', 'who' => 'world'));

hello.php

	:::php
		<?= $greeting ?>, <?= $who ?>!

Output:

	:::text
		Hello, world!

### Configuration

The following configurations options are available:

* `path` - the default base path to the template files.
* `suffix` - the default suffix for template files.
* `escape` - an array of objects, which implement `\PhpTemplate\Escape\EscapeInterface`, that will be used for escaping values.

Configuration options can be set by passing an associative array of options to the static `Template::setConfig()` method. The options will then be applied to all `Template` objects that are instantiated.

### Escaping Values

Objects which implement `\PhpTemplate\Escape\EscapeInterface` are added to the configuration using `Template::addEscape()` (or with the `Template::setConfig()`). Then `$this->escape()` is then called from within a template to escape a value. The `\PhpTemplate\Escape\HtmlEntitesEscape` class can be used for escaping HTML entities.

For example:

example.php

	:::php
		<?php
		require_once 'vendor/autoload.php'; // Require composer autoloader.

		use \PhpTemplate\Template;
		use \PhpTemplate\Escape\HtmlEntitiesEscape;

		Template::addEscape(new HtmlEntitiesEscape);

		$t = new \PhpTemplate\Template('hello.html.php');
		$t->greeting = '<b>Hello<b>';
		$t->who = 'world';

		echo $t->execute();

hello.html.php

	:::php
		<?= $this->escape($greeting) ?>, <i><?= $who ?></i>!

Output:

	:::html
		&lt;b&gt;Hello&lt;b&gt;, <i>world</i>!

### Including Sub-Templates

The following approaches can be used to include another template from within a template:

	<?= static::render('foo.php', array('foo'=>'bar')) ?>

With this approach only the variables passed as the second parameter are available within `foo.php`. The use of the `static` keyword means that `render()` is called on class that the original `execute()` call was made against. Alternatively, you can use `self::render()` which will call `render()` on the class which included the template file (this would normally be the base `Template` class).

	<?= $this->subRender('foo.php') ?>

Using `subRender()` passes all the template variables from the current template to the next template. It doesn't pass any local variables that were defined within the current template file.

	<?= static::objRender('Template', array('foo.php'), array('foo'=>'bar')) ?>

Using `objRender()` instantiates a template of the class specified by the first argument, passes the second argument to the class constructor and assigns the third argument as template variables.

	<?= include $this->getFileName('foo.php') ?>

This approach includes the next template in the same scope as the current template. This means that template variables and also variables defined locally within the current template will be available. The call to `$this->getFileName()` ensures that the correct path and suffix (see Configuration above) are used when including the next template.


## Testing

Unit tests not yet implemented ...


## Contributing

Report bugs and submit pull request to <https://bitbucket.org/torohill/php-template/>.


## License

PHP Templates is released under the MIT License, see the `LICENSE` file for details.
