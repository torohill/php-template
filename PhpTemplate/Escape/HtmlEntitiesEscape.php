<?php
/**
 * PHP Template HTML Entitites class.
 *
 * Class for escaping html entities.
 *
 * @author	Toro Hill
 * @link	https://github.com/torohill/php-template/
 * @license MIT
 */
namespace PhpTemplate\Escape;

class HtmlEntitiesEscape implements EscapeInterface{
	/**
	 * The encoding to use when escaping html entities.
	 */
	protected $encoding;

	/**
	 * Flags that determine how to handle quotes, invalid code units and document types when escaping entities.
	 *
	 * More details at:
	 * http://php.net/manual/en/function.htmlentities.php
	 */
	protected $flags;

	/**
	 * Create a new HtmlEntitiesEscape object.
	 *
	 * By default encodes single and double quotes, replaces any invalid code sequences with
	 * U+FFFD and handles code as HTML5. Also, input string is treated as UTF-8.
	 *
	 * @param	string	$encoding	The encoding to use when escaping html entities.
	 * @param	int		$flags		Bitmask of ENT_* constants.
	 */
	public function __construct($encoding='UTF-8', $flags=NULL){
		$this->encoding = $encoding;
		$this->flags = is_null($flags) ? ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5 : $flags;
	}
	/**
	 * Escape html entities in a string.
	 *
	 * @param	string	$value	The html string to be escaped.
	 * @return	string			Escaped html input.
	 */
	public function escape($value){
		return htmlentities($value, $this->flags, $this->encoding);
	}
}
