<?php
/*
 * A very simple template class. 
 * Assign template variables as member variables then call execute or use the static render method.
 */
class Template{
	// Filename of template.
    protected $file;

	//Template variables.
    protected $vars = array();


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
	 * Set multiple template variables at the same time.
	 * $value should be an associative array.
	 */
    public function set(array $values){
        $this->vars = array_merge($this->vars, $values);
    }

	/*
	 * Execute the template with the current template variables and return the output.
	 * Note that the template files can access $this if needed.
	 */
    public function execute(){
        extract($this->vars);
        ob_start();
        include($this->file);
        $content = ob_get_contents();
        ob_end_clean();
        return $content;
    }

	/*
	 * Execute a template and echo the results.
	 */
	public function output(){
		echo $this->execute();
	}

	/*
	 * Load and execute a template and return the results.
	 */
	public static function render($file, array $args=array()){
		$template = new Template($file);
		$template->set($args);
		return $template->execute();
	}

	/*
	 * Load and echo a template.
	 */
	public static function display($file, array $args=array()){
		echo self::render($file, $args);
	}
}
