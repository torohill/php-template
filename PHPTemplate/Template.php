<?php
/**
 * PHP Template - A very simple template class that uses PHP as the template language.
 *
 * Basic usage: 
 * 	Assign template variables as member variables then call execute().
 * 	Can also use the static render() method to assign variables and execute in a single call.
 * 	Includes support for sub-rendering where all the variables from the current template are 
 * 	passed to the sub-template.
 *
 * Gotchas:
 * 	- Invalid variable names will be prefixed with self::PREFIX plus an _ in templates. eg.
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
 * 	- isset() on a template variable will return TRUE for a NULL value, which is different 
 * 		to how isset normally works in PHP. eg.
 * 		$t = new Template('foo.html'); 
 * 		$t->foo = NULL;
 * 		isset($t->foo); // TRUE
 *
 * @author	Toro Hill
 * @link	https://bitbucket.org/torohill/php-template/
 * @license MIT
 */

namespace PHPTemplate;

/**
 * Main template class.
 */

class Template{
	/**
	 * Prefix that will be added to invalid template variable names (eg. numbers).
	 */
	const PREFIX = 'PHPTemplate';

	/**
	 * Filename of template.
	 */
	protected $file;

	/**
	 * Template variables.
	 */
	protected $vars = array();

	/**
	 * Default path to templates. Static so used for all template objects.
	 */
	protected static $path = '';

	/**
	 * Default template suffix. Static so used for all template objects.
	 */
	protected static $suffix = '';

	/**
	 * Create a new template.
	 *
	 * @param	string	$file	Path and name of template file.
	 */
	public function __construct($file){
		$this->file = $file;
	}
	/**
	 * Magic getter for template variables.
	 *
	 * Executed when an inaccessible property is accessed.
	 * eg. $bar = $template->foo;
	 *
	 * @param	string	$key	Name of template variable being accessed.
	 * @return	mixed			Value of template variable or NULL if not set.
	 */
	public function __get($key){
		return $this->__isset($key) ? $this->vars[$key] : NULL;
	}
	/**
	 * Magic setter for template variables.
	 *
	 * Executed when an inaccessible property is assigned a value.
	 * eg. $template->foo = 'bar';
	 *
	 * @param	string	$key	Name of template variable being set.
	 * @param	mixed	$value	Value of template variable.
	 * @return	void
	 */
	public function __set($key, $value){
		$this->vars[$key] = $value;
	}
	/**
	 * Magic method for checking if template variables are set.
	 *
	 * Executed when isset or empty is called on an inaccessible property.
	 * eg. isset($template->foo);
	 * eg. empty($template->foo);
	 *
	 * Note that this will return TRUE for a value that is set to NULL, 
	 * which is different to how isset normally works in PHP.
	 *
	 * @param	string	$key	Name of template variable.
	 * @param	mixed	$value	Value of template variable.
	 * @return	bool			Whether the template variable is set.
	 */
	public function __isset($key){
		return array_key_exists($key, $this->vars);
	}
	/**
	 * Magic method for unsetting template variables.
	 * Execute when unset is called on an inaccessible property.
	 * eg. unset($template->foo);
	 *
	 * @param	string	$key	Name of template variable to unset.
	 * @return	void
	 */
	public function __unset($key){
		unset($this->vars[$key]);
	}

	/**
	 * Set multiple template variables at the same time.
	 *
	 * @param	array	$vars	An associtive array of template variable names to values.
	 * @return	Template		Returns $this to enable method chaining.
	 */
	public function set(array $vars){
		$this->vars = self::mergeVars($this->vars, $vars);
		return $this;
	}

	/**
	 * Execute the template with the current template variables and return the output.
	 *
	 * Note that the template files can access $this if needed. 
	 * Doesn't define any other variables as they will pollute the scope in the template file.
	 * 
	 * @param	array	$vars 	Optional associative array of template variables.
	 * @return	string			Output from executing template file.
	 */
	public function execute(array $vars=array()){
		$this->set($vars);
		// Remove $vars from template scope.
		unset($vars);

		$this->preprocessVars();

		// Note that EXTR_PREFIX_INVALID automatically puts an _ between the prefix and the variable name.
		extract($this->vars, EXTR_PREFIX_INVALID, self::PREFIX);
		ob_start();
		include(self::$path . $this->file . self::$suffix);
		$content = ob_get_contents();
		ob_end_clean();
		return $content;
	}

	/**
	 * Set the default base path for all templates.
	 *
	 * Saves having to specify the path for every template.
	 * Will be prepended to all template names regardless of whether they are relative or absolute.
	 * Set path to empty string to unset the current path.
	 *
	 * @param	string	$path	Default path to template files. 
	 * Will be prepended to the template file name for all template objects. 
	 * Should contain a trailing slash as will not be modified before prepending.
	 *
	 * @return	void
	 */
	public static function setPath($path){
		self::$path = (string) $path;
	}

	/**
	 * Set the default file suffix for all templates.
	 *
	 * Saves having to specify the suffix for every template.
	 * Will be appended to all template names.
	 *
	 * Set suffix to empty string to unset the current suffix.
	 *
	 * @param	string	$suffix	Default file suffix. 
	 * Will be appended to template file names for all template objects.
	 * Should contain joining . (eg. .html.php)
	 *
	 * @return	void
	 */
	public static function setSuffix($suffix){
		self::$suffix = (string) $suffix;
	}

	/**
	 * Load, execute and return the output from a template in a single method call.
	 *
	 * @param	string	$file	Template file name.
	 * @param	array	$vars	Optional associative array of template variables.
	 * @return	string			Output from executing the template file.
	 */
	public static function render($file, array $vars=array()){
		// Use new static() (instead of new self() or new Template()) as this uses the class 
		// that the render method was called on rather than this class.
		$template = new static($file);
		$template->set($vars);
		return $template->execute();
	}

	/**
	 * Load, execute and return the output from a template by calling a specific class constructor.
	 *
	 * Does not set() any variables so all parameters must be passed through constructor.
	 *
	 * @param	string	$class	Name of class for template object.
	 * @param	mixed	...		Unlimited arguments to be passed to class constructor.
	 * All additional parameters after $class are passed to the constructor.
	 *
	 * @return	string			Output from executing the template object.
	 */
	public static function objRender($class){
		$args = func_get_args();
		array_shift($args);
		$ref = new \ReflectionClass($class);
		$object = $ref->newInstanceArgs($args);
		return $object->execute();
	}

	/**
	 * Load, execute and return the output from a template while passing all the current templates variables to the sub-template.
	 *
	 * @param	string	$file	Template file name.
	 * @param	array	$vars	Optional associative array of additional template variables.
	 * @return	string			Output from executing the template file.
	 */ 
	protected function subRender($file, array $vars=array()){
		return self::render($file, self::mergeVars($this->vars, $vars));
	}

	/**
	 * Internal method for pre-processing vars before executing a template.
	 *
	 * $this is the only variable assigned in the template scope, let's prefix it with self::PREFIX.
	 * There doesn't appear to be a way to do this with extract() and also prefix invalid variables.
	 *
	 * @return	void
	 */
	protected function preprocessVars(){
		// Call magic methods explicitly as it makes it a bit clearer what is going on,
		// and avoids problems with __set() not getting called.
		if($this->__isset('this')){
			$this->__set(self::PREFIX . '_this', $this->__get('this'));
			$this->__unset('this');
		}
	}

	/**
	 * Internal static method for merging two arrays of variables without renumbering numerical keys.
	 *
	 * @param	array	$vars1	First array of vars to merge.
	 * @param	array	$vars2	Second array of vars to merge, will overwrite vars of the same name in $vars1.
	 * @return	array			Results from merging $vars1 with $vars2.
	 *
	 */
	protected static function mergeVars(array $vars1, array $vars2){
		// Don't use array_merge() because then numerical keys get renumbered.
		// With the union operator the left hand operand is used if there are conflicting keys.
		return $vars2 + $vars1;
	}
}
