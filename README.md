# PHP Template

__Project Status__: stable


## Overview

A very basic template class that uses PHP as the templating language.

Works like this:

1. Create a `Template` object.
1. Assign template variables as member variables.
1. Call `Template->execute()` to retrieve the rendered template.

It is also possible to use the static `Template::render()` method to assign variables and execute in a single call.


## Example Usage

example.php

	:::php
		<?php
		require_once 'PHPTemplate/Template.php';
		use PHPTemplate\Template;

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

