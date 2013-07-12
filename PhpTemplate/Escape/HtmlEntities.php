<?php
/**
 * PHP Template HTML Entitites class.
 *
 * Class for escaping html entities.
 *
 * @author	Toro Hill
 * @link	https://bitbucket.org/torohill/php-template/
 * @license MIT
 */
namespace PhpTemplate\Escape;

class HtmlEntities implements EscapeInterface{
	/**
	 * Escape html entities in a string.
	 *
	 * Encodes single and double quotes, replaces any invalid code sequences with U+FFFD
	 * and handles code as HTML5.
	 *
	 * @param	string	$value	The html string to be escaped.
	 * @return	string			Escaped html input.
	 */
	public function escape($value){
		return htmlentities($value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
	}
}
