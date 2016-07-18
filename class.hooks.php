<?php
namespace Sleepy;

/**
 * Adds Hooks and Filters
 *
 * You can create modules to hooks by adding php files into the
 * *\app\modules\enabled* directory.
 *
 * ## Usage
 * <code>
 * 	// add a hook point
 * 	$content = Hook::addFilter('update_content', $_POST['content']);
 *
 * 	// Add a module to the hook point--in /modules/<moduleName.php>
 * 	function clean_html ($html) {
 * 		$c = htmlentities(trim($html), ENT_NOQUOTES, "UTF-8", false);
 * 		return $c;
 * 	}
 *
 * 	Hook::applyFilter("update_content", "clean_html");
 * </code>
 *
 * ## Changelog
 *
 * ### Version 1.2
 * * Updated privacy prefix (_) for consistency
 * * Fixed Hook::_load method for teamsite bug
 *
 * ### Version 1.1
 * * Added the date section to the documentation
 *
 * ### Version 1.0
 * * static class pattern fixes
 * * multiple module directories
 * * crawls subdirectories of module directories
 *
 * @date July 18, 2016
 * @author Jaime A. Rodriguez <hi.i.am.jaime@gmail.com>
 * @version 1.2
 * @license  http://opensource.org/licenses/MIT
 *
 * @todo devise a better way of passing multiple parameters to hooks, perhaps
 *       use objects instead of arrays
 */
class Hook {

	/**
	 * Has this been initialized?
	 *
	 * @var bool
	 * @private
	 */
	private static $_initialized = false;

	/**
	 * An array of filters
	 *
	 * @var _Filter[]
	 * @private
	 */
	private static $_filters = array();

	/**
	 * The directories where the modules are stored
	 *
	 * @var string
	 */
	public static $directories = array();

	/**
	 * Prevent class from being cloned
	 *
	 * @private
	 */
	private function __clone() {}

	/**
	 * The constructor is private to ensure we only have one instance
	 * 
	 * @private
	 */
	private function __construct() {}

	/**
	* Return instance or create initial instance
	*
	* @private
	* @static
	* @return object
	*/
	private static function _initialize() {
		if (!self::$_initialized) {
			self::$directories[] = DIRBASE . '/modules/';
			self::$_initialized = true;
			self::_load();
		}
	}

	/**
	 * Loads all the modules
	 *
	 * @private
	 * @static
	 * @return void
	 */
	private static function _load() {
		$directories = self::$directories;

		// get all subdirectories
		foreach (self::$directories as $directory) {
			$subdirectories = glob($directory . '/*' , GLOB_ONLYDIR);

			if (is_array($subdirectories)) {
				$directories = array_merge($directories, $subdirectories);
			}
		}

		// include all php files
		foreach ($directories as $directory) {
			$files = glob($directory . '/*.php');

			if (!is_array($files)) {
				continue;
			}
			
			foreach($files as $file) {
				if (strpos($file, '_test.php') !== false) {
					continue;
				}

				require_once($file);
			}
		}
	}

	/**
	 * Adds a new filter to a filter-type hook point
	 *
	 * @param  string $name     [description]
	 * @param  string $function [description]
	 * @param  int $args        [description]
	 * @static
	 * @return void
	 */
	public static function applyFilter($name, $function) {
		self::_initialize();

		$args = func_get_args();

		array_shift($args);
		array_shift($args);

		if (!isset(self::$_filters[$name])) {
			self::$_filters[$name] = new _Filter ($name);
		}

		// add the function to the filter
		self::$_filters[$name]->add($function, $args);
	}

	/**
	 * Adds a new filter-type hook point
	 *
	 * @param mixed  $name  [description]
	 * @param string $value [description]
	 * @static
	 * @return void
	 */
	public static function addFilter($name, $value) {
		self::_initialize();

		// If there are no functions to run
		if (!isset(self::$_filters[$name])) {
			if (is_array($value)) {
				return $value[0];
			} else {
				return $value;
			}
		}

		foreach (self::$_filters[$name]->functions as $function => $args) {
			if (is_array($value)) {
				$returned = call_user_func_array($function, $value);
			} else {
				$returned = call_user_func($function, $value);
			}
		}

		return $returned;
	}

	/**
	 * Adds a new function to a action-type hook point
	 *
	 * @param  string $name     Name of filter
	 * @param  string $function Function to call
	 * @static
	 * @return void
	 */
	public static function doAction($name, $function) {
		call_user_func_array('self::applyFilter', func_get_args());
	}

	/**
	 * Adds a new action-type hook point
	 *
	 * @param string $name [description]
	 * @static
	 * @return void
	 */
	public static function addAction($name) {
		self::addFilter($name, '');
	}
}

/**
 * Private class used by the Hooks class
 *
 * The class stores the filters. It has properties to store the name of the
 * filter as well the functions that should run when the filters are stored.
 * The filters property is an array. The key is the name of the
 * function and value is the arguments. Currently we do not make any use of the
 * arguments.
 *
 * ### Usage
 *
 * This class is private and should not be used outside of the Hooks class
 *
 * @param string $name name of the filter
 *
 * @date September 31, 2014
 * @author Jaime A. Rodriguez <hi.i.am.jaime@gmail.com>
 * @version 0.4
 * @license  http://opensource.org/licenses/MIT
 * @internal
 */

class _Filter {
	/**
	 * The name of the filter
	 *
	 * @var string
	 */
	public $name;

	/**
	 * A list of functions to execute
	 *
	 * @var [string[]]
	 */
	public $functions;

	/**
	 * Constructor
	 *
	 * @param string $name The name of the filter
	 */
	public function __construct($name) {
		$this->name = $name;
	}

	/**
	 * Adds a function to this filter
	 *
	 * @param string $function The function to call
	 * @param array $args An array of parameters
	 */
	public function add($function, $args) {
		$this->functions[$function] = $args;
	}
}