<?php
/**
 * PHP Template Escape Interface definition.
 *
 * The interface which all objects that are used for escaping content must implement.
 *
 * @author	Toro Hill
 * @link	https://github.com/torohill/php-template/
 * @license MIT
 */
namespace PhpTemplate\Escape;

interface EscapeInterface{
	/**
	 * Escape a value and return the escaped value.
	 *
	 * @param	string	$value	The value to be escaped.
	 * @return	string			Escaped value.
	 */
	public function escape($value);
}
