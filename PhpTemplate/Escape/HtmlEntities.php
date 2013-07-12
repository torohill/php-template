<?php

namespace PhpTemplate\Escape;

class HtmlEntities implements EscapeInterface{
	public function escape($value){
		return htmlentities($value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
	}
}
