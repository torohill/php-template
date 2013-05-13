<?php
/**
 * A very simple template class that uses PHP as the template language.
 * Assign template variables as member variables then call execute().
 * Can also use the static render() method to assign variables and execute in a single call.
 *
 * Gotchas:
 * 	- Invalid variable names be prefixed with self::PREFIX plus an _ in templates. eg.
 * 		$t = new Template('foo.html'); 
 * 		$t->set(array(1 => 'bar')); // $PHPTemplate_1 is available in the template as $1 is not valid
 *
 * 	- $this as a template variable will automatically be prefixed with self::PREFIX plus an _.
 * 		This is to avoid a clash with the $this reference to the object,
 * 		and avoids some weirdness in templates where echo $this outputs the template variable
 * 		but $this->foo() also works and calls the Template object. eg.
 * 		$t = new Template('foo.html'); 
 * 		$t->this = 'foo'; // $PHPTemplate_this is available in the template.
 *
 * 	- Member variables (eg. $file, $vars etc) can be used as template variables, but not 
 * 		from within child classes. The reason they can be used as template variables is that 
 * 		__set() is called when assignment is done to a inaccessible property (eg. protected). 
 * 		However, if $this->file = 'foo' is called from within a child class then the code
 * 		will have access to the protected $file member variable so __set() won't be called.
 *
 * 	- isset() on a template variable will return TRUE for a NULL value, 
 * 		which is different to how isset normally works in PHP. eg.
 * 		$t = new Template('foo.html'); 
 * 		$t->foo = NULL;
 * 		isset($t->foo); // TRUE
 */

namespace PHPTemplate;

class Template{
	// Prefix that will be added to invalid template variable names (eg. numbers).
	const PREFIX = 'PHPTemplate';

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
		return $this->__isset($key) ? $this->vars[$key] : NULL;
	}
	public function __set($key, $value){
		$this->vars[$key] = $value;
	}
	// Note that this will return TRUE for a value that is set to NULL, 
	// which is different to how isset normally works in PHP.
	public function __isset($key){
		return array_key_exists($key, $this->vars);
	}
	public function __unset($key){
		unset($this->vars[$key]);
	}

	/*
	 * Set multiple template variables at the same time.
	 * $vars should be an associative array.
	 */
	public function set(array $vars){
		$this->vars = self::merge($this->vars, $vars);
	}

	/*
	 * Execute the template with the current template variables and return the output.
	 * Note that the template files can access $this if needed (but it's probably not good form).
	 * Don't define any other variables as they will pollute the scope in the template file.
	 */
	public function execute(){
		// $this is the only variable assigned in this scope, let's prefix it with self::PREFIX.
		// There doesn't appear to be a way to do this with extract() and also prefix invalid variables.
		// Call magic methods explicitly as it makes it a bit clearer what is going on,
		// and avoids problems with __set() not getting called.
		if($this->__isset('this')){
			$this->__set(self::PREFIX . '_this', $this->__get('this'));
			$this->__unset('this');
		}
		// Note that EXTR_PREFIX_INVALID automatically puts an _ between the prefix and the variable name.
		extract($this->vars, EXTR_PREFIX_INVALID, self::PREFIX);
		ob_start();
		include(self::$path . $this->file . self::$suffix);
		$content = ob_get_contents();
		ob_end_clean();
		return $content;
	}

	/*
	 * Public static methods
	 */

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
	 * Load and execute a template and return the results.
	 * $vars should be an associative array of template variables.
	 */
	public static function render($file, array $vars=array()){
		$template = new Template($file);
		$template->set($vars);
		return $template->execute();
	}

	/*
	 * Protected methods
	 */

	/*
	 * Load and execute a template while passing all the current templates variables to the sub-template.
	 * Returns the results. 
	 * To be called from within a template file with $this->subRender($file, $vars);
	 */ 
	protected function subRender($file, array $vars=array()){
		return self::render($file, self::merge($this->vars, $vars));
	}

	/*
	 * Protected static methods
	 */

	/* 
	 * Merge two associative arrays of template variables and return the results.
	 * Values in $vars2 will overwrite values in $vars1.
	 * Numerical keys are not renumbered.
	 */
	protected static function merge(array $vars1, array $vars2){
		// Don't use array_merge() because then numerical keys get renumbered.
		// With the union operator the left hand operand is used if there are conflicting keys.
		return $vars2 + $vars1;
	}
}
