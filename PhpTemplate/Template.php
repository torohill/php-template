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
 * 	* Member variables (eg. $file, $vars etc) can be used as template variables, but not 
 * 		from within child classes. The reason they can be used as template variables is that
 * 		__set() is called when assignment is done to a inaccessible property (eg. protected).
 * 		However, if $this->file = 'foo' is called from within a child class then the code
 * 		will have access to the protected $file member variable so __set() won't be called.
 *
 * The namespace is PhpTemplate rather than PHPTemplate to follow the recommendation that
 * acronyms that are longer than two characters should use studly caps rather than all capitals.
 * More details at the links below:
 * 		http://msdn.microsoft.com/en-us/library/141e06ef%28v=vs.71%29.aspx
 *		http://stackoverflow.com/a/15526526
 *
 * @author	Toro Hill
 * @link	https://bitbucket.org/torohill/php-template/
 * @license MIT
 */

namespace PhpTemplate;

/**
 * Main template class.
 */

class Template{
	/**
	 * Filename of template.
	 */
	protected $file;

	/**
	 * Template variables.
	 */
	protected $vars = array();

	/**
	 * Static configuration options that are used for all template objects.
	 *
	 * The options are:
	 * 	* path - Default path to templates.  
	 * 		Will only be prepended to relative template names, absolute names (will be left alone)
	 * 		Trailing slash is optional. Set path to NULL to unset the current path.
	 * 	* suffix - Default template suffix.
	 * 		Will be appended to template file names for all template objects unless the 
	 * 		suffix is already present. Should contain joining . (eg. .html.php)
	 * 		Set suffix to NULL to unset the current suffix.
	 * 	* escape - Array of objects which implement the EscapeInterface
	 * 		Will used for escaping values passed to the $this->esc() method.
	 * 		The addEscape() and clearEscape() for managing the list of escape objects.
	 *
	 * Uses an associative array of configuration options instead of individual
	 * static properties as it's easier to add new options.
	 */
	protected static $config = array(
		'path' => NULL
		, 'suffix' => NULL
		, 'escape' => array()
	);

	/**
	 * Local configuration options for instantiated template objects.
	 * Will be intialised with global options from static::$config by default.
	 */
	protected $template_config = array();

	/**
	 * Create a new template.
	 *
	 * @param	string	$file	Path and name of template file.
	 */
	public function __construct($file){
		$this->file = (string) $file;
		$this->setTemplateConfig(static::$config);
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
		return $this->exists($key) ? $this->vars[$key] : NULL;
	}

	/**
	 * Magic setter for template variables.
	 *
	 * Executed when an inaccessible property is assigned a value.
	 * eg. $template->foo = 'bar';
	 * Will throw an exception if $key is not a valid var name (as per checkValidVar()).
	 *
	 * @param	string	$key	Name of template variable being set.
	 * @param	mixed	$value	Value of template variable.
	 * @return	void
	 */
	public function __set($key, $value){
		$this->checkValidVar($key);
		$this->vars[$key] = $value;
	}

	/**
	 * Magic method for checking if a template variable is set.
	 *
	 * Executed when isset or empty is called on an inaccessible property.
	 * eg. isset($template->foo);
	 * eg. empty($template->foo);
	 *
	 * @param	string	$key	Name of template variable.
	 * @return	bool			Whether the template variable is set.
	 * Returns FALSE if value exists but is NULL, this is the same as the default behaviour for isset in PHP.
	 * Use $this->exists() to check if a value exists, including NULL.
	 */
	public function __isset($key){
		return isset($this->vars[$key]);
	}

	/**
	 * Magic method for unsetting a template variable.
	 *
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
	 * Check whether a template variable exists.
	 *
	 * unset($this->foo) will return FALSE if foo is set to NULL or if foo doesn't exist.
	 * $this->exists('foo') will return TRUE is foo is set to NULL and FALSE if foo doesn't exist.
	 *
	 * @param	string	$key	Name of template variable.
	 * @return	bool			Whether the template exists. 
	 * Returns TRUE for a variable that exists and is set to NULL.
	 */
	public function exists($key){
		return array_key_exists($key, $this->vars);
	}

	/**
	 * Set multiple template variables at the same time.
	 *
	 * Each key in the $vars array must be a valid PHP variable name.
	 * This means numerical keys will not work. Will throw an exception 
	 * if a var name is not valid (as per checkValidVar()).
	 *
	 * @param	array	$vars	An associative array of template variable names to values.
	 * @return	Template		Returns $this to enable method chaining.
	 */
	public function set(array $vars){
		// We could use mergeVars() with $this->vars but instead call __set() directly to
		// so that each var is passed to checkValidVar()
		foreach($vars as $key => $value){
			$this->__set($key, $value);
		}
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

		// There shouldn't be any variables to overwrite because there are no variables
		// in this scope other than $this which is not allowed, but use EXTR_SKIP just to be safe.
		// Note that PHP will not extract over superglobals.
		extract($this->vars, EXTR_SKIP);

		ob_start();
		include($this->getFileName($this->file));
		$content = ob_get_contents();
		ob_end_clean();

		return $content;
	}

	/**
	 * Return the full file name of the current template based on the current configuration.
	 *
	 * $config['path'] will not be prepended if the template file name is already absolute.
	 * $config['suffix'] will not be appended if the template file name already ends in the suffix.
	 * This method can be used from within templates to convert template names to full file names.
	 *
	 * @param	strin	$file	Template file name.
	 * @return	string			Full file name of template, including path and suffix if appropriate.
	 */
	protected function getFileName($file){
		$path = '';
		if(!is_null($this->template_config['path']) && '/' !== substr($file, 0, 1)){
			$path .= $this->template_config['path'];

			if('/' !== substr($path, -1)){
				$path .= '/';
			}
		}
		$path .= $file;
		if(!is_null($this->template_config['suffix'])
			&& $this->template_config['suffix'] !== substr($path, -strlen($this->template_config['suffix']))){

			$path .= $this->template_config['suffix'];
		}
		return $path;
	}

	/**
	 * Set the default configuration options for all templates.
	 *
	 * 'escape' config values are passed to addEscape() to ensure they are validated correctly.
	 *
	 * @param	array	$config 	Associative array of configuration options.
	 * @return	void
	 */
	public static function setConfig(array $config){
		if(isset($config['escape'])){
			$escape = $config['escape'];
			unset($config['escape']);
			if(!is_array($escape)){
				$escape = array($escape);
			}
			foreach($escape as $esc){
				static::addEscape($esc);
			}
		}
		static::$config = array_merge(static::$config, $config);
	}

	/**
	 * Set the configuration options for this template object.
	 *
	 * @param	array	$config	Associative array of configuration options.
	 * return	void
	 */
	public function setTemplateConfig(array $config)
	{
		$this->template_config = array_merge($this->template_config, $config);
	}

	/**
	 * Adds an object to the end of the list of configured escape ojbects.
	 *
	 * @param	Escape\EscapeInterface	$escape	Object 
	 * return	void
	 */
	public static function addEscape(Escape\EscapeInterface $escape){
		static::$config['escape'][] = $escape;
	}

	/**
	 * Clears the list of configurated escape objects.
	 *
	 * @param	void
	 * @return	void
	 */
	public static function clearEscape(){
		static::$config['escape'] = array();
	}

	/**
	 * Escapes a value, using the list of configured escape objects.
	 * 
	 * @param	string	$value	String value to be escaped.
	 * @return	string			Escaped value.
	 */
	public function escape($value){
		foreach($this->template_config['escape'] as $escape){
			$value = $escape->escape($value);
		}
		return $value;
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
		return $template->execute($vars);
	}

	/**
	 * Load, execute and return the output from a template by calling a specific class constructor.
	 *
	 * Does not set() any variables so all parameters must be passed through constructor.
	 *
	 * @param	string	$class	Name of class for template object.
	 * @param	array	$args	Array of values to pass as arguments to the template object constructor.
	 * @param	array	$vars	Associative array of template variables to set on the template object.
	 * @return	string			Output from executing the template object.
	 */
	public static function objRender($class, array $args=array(), array $vars=array()){
		$ref = new \ReflectionClass($class);
		$object = $ref->newInstanceArgs($args);
		return $object->execute($vars);
	}

	/**
	 * Load, execute and return the output from a template while passing all the current 
	 * templates variables to the sub-template.
	 *
	 * @param	string	$file	Template file name.
	 * @param	array	$vars	Optional associative array of additional template variables.
	 * @return	string			Output from executing the template file.
	 */ 
	protected function subRender($file, array $vars=array()){
		return $this->render($file, static::mergeVars($this->vars, $vars));
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

	/**
	 * Check whether a template variable name is valid.
	 *
	 * Checks with the variable name is a valid PHP variable name
	 * (letter or underscore followed by any number of letters, numbers or underscores)
	 * and also checks against a list of reserved variable names (this and superglobals).
	 *
	 * @param	array	$key	Name of template variable to check.
	 * @throws	Exception		Throws an exception if the variable name is not valid.
	 * @return	void
	 *
	 */
	protected static function checkValidVar($key){
		// Don't allow variables with the same name as superglobals.
		// extract() won't actually overwrite superglobals in $this->execute() but 
		// explicitly throw an exception to make it harder to hurt yourself.
		$invalid_vars = array(
			'this'
			, 'GLOBALS'
			, '_SERVER'
			, '_GET'
			, '_POST'
			, '_FILES'
			, '_COOKIE'
			, '_SESSION'
			, '_REQUEST'
			, '_ENV'
		);

		// Regular expression for valid variable names take from:
		// http://php.net/manual/en/language.variables.basics.php
		$regexp_var = '/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/';

		if(!is_string($key) || !preg_match($regexp_var, $key) || in_array($key, $invalid_vars, TRUE)){
			throw new Exception('Invalid template variable name "' . $key .'".');
		}
	}
}
