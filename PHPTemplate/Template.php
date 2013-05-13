<?php
/**
 * A very simple template class that uses PHP as the template language.
 * Assign template variables as member variables then call execute().
 * Can also use the static render() method to assign variables and execute in a single call.
 *
 * Limitations:
 * 	- Can't have a $this template variable (conflicts with reference to the current object).
 *
 * Gotchas:
 * 	- Invalid variable names for template variables will be prefixed with Template_, eg.
 * 		$t = new Template('foo.html'); 
 * 		$t->set(array(1 => 'bar'));
 * 		$Template_1 is the variable that is available in the template as $1 is not valid.
 * 	- Member variables (eg. $file, $vars etc) can be used as template variables, but not 
 * 		from within child classes. The reason they can be used as template variables is that 
 * 		__set() is called when assignment is done to a inaccessible property (eg. protected). 
 * 		However, if $this->file = 'foo' is called from within a child class then the code
 * 		will have access to the protected $file member variable so __set() won't be called.
 */

namespace PHPTemplate;

class Template{
	// Prefix that will be added to invalid template variable names (eg. numbers).
	const PREFIX = 'Template';

	// Filename of template.
	protected $file;

	//Template variables.
	protected $vars = array();

	//Default path to templates. Static so used for all template objects.
	protected static $path = '';

	//Default template suffix. Static so used for all template objects.
	protected static $suffix = '';

	/*
	 * Public methods
	 */
	public function __construct($file){
		$this->file = $file;
	}
	public function __get($key){
		return array_key_exists($this->vars, $key) ? $this->vars[$key] : NULL;
	}
	public function __set($key, $value){
		$this->vars[$key] = $value;
	}

	/*
	 * Set the default base path for all templates.
	 * Saves having to specify the path for every template.
	 * Should contain a trailing slash as will not be modified before prepending.
	 * Will be prepended to all template names regardless of whether they are relative or absolute.
	 * Set path to empty string to unset the current path.
	 */
	public static function setPath($path){
		self::$path = (string) $path;
	}

	/*
	 * Set the default file suffix for all templates.
	 * Saves having to specify the suffix for every template.
	 * Will be appended to all template names.
	 * Set suffix to empty string to unset the current suffix.
	 */
	public static function setSuffix($suffix){
		self::$suffix = (string) $suffix;
	}

	/*
	 * Set multiple template variables at the same time.
	 * $vars should be an associative array.
	 */
	public function set(array $vars){
		$this->vars = array_merge($this->vars, $vars);
	}

	/*
	 * Execute the template with the current template variables and return the output.
	 * Note that the template files can access $this if needed (but it's probably not good form).
	 * Don't define any other variables as they will pollute the scope in the template file.
	 */
	public function execute(){
		// Note that EXTR_PREFIX_INVALID automatically puts an _ between the prefix and the variable name.
		extract($this->vars, EXTR_PREFIX_INVALID, self::PREFIX);
		ob_start();
		include(self::$path . $this->file . self::$suffix);
		$content = ob_get_contents();
		ob_end_clean();
		return $content;
	}

	/*
	 * Load and execute a template and return the results.
	 * $vars should be an associative array of template variables.
	 */
	public static function render($file, array $vars=array()){
		$template = new Template($file);
		$template->set($vars);
		return $template->execute();
	}

	/*
	 * Load and execute a template while passing all the current templates variables to the sub-template.
	 * Returns the results. 
	 * To be called from within a template file with $this->subRender()
	 */ 
	protected function subRender($file, array $vars=array()){
		return self::render($file, array_merge($this->vars, $vars));
	}
}
