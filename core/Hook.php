<?php

/**
 * Adds Hooks and Filters
 *
 * Modules are methods that run at certain points in the application. These points
 * are called hooks. There are two types of hooks--actions and filters. Actions are
 * points where code that run. For example, when the page is done generating you can
 * run code at the action *sleepy_postprocess*. Filters are used to manipulate
 * content. For example, a placeholder called *cms* with the string "sleepy* can be
 * modified by using the filter *placeholder_cms*.
 *
 * ## Usage
 *
 * ### PHP File: index.php
 * ~~~ php
 *   use Sleepy\Hook;
 *
 *   // Add a filter hook so that when the execution gets to this line, it runs
 *   // whichever modules are subscribed to this hook.
 *   $content = Hook::addFilter('update_content', $_POST['content']);
 * ~~~
 *
 * ### PHP File: *app\module\sanitize\main.php*
 * ~~~ php
 *   namespace Module;
 *
 *   class Sanitize {
 *     public function clean_html($html) {
 *       $c = htmlentities(trim($html), ENT_NOQUOTES, "UTF-8", false);
 *       return $c;
 *     }
 *   }
 *
 *   // Subscribe to the filter "update_content", defined in *index.php* above, and
 *   // run the method Sanitize::clean_html() passing in $_POST['content'] as the
 *   // $html parameter.
 *   \Sleepy\Hook::applyFilter("update_content", "\Module\Sanitize\clean_html");
 * ~~~
 *
 * ## Changelog
 *
 * ### Version 2.0a
 * * Converted to PSR-4
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
 * php version 7.2.10
 *
 * @todo devise a better way of passing multiple parameters to hooks, perhaps use
 * objects instead of arrays
 *
 * @category Core
 * @package  Sleepy
 * @author   Jaime A. Rodriguez <hi.i.am.jaime@gmail.com>
 * @license  http://opensource.org/licenses/MIT; MIT
 * @link     https://sleepymustache.com
 */

namespace Sleepy\Core;

/**
 * The Hook Class
 *
 * The static class that is used to define hook points and assign functionality to
 * the actions and filters
 *
 * @category Core
 * @package  Sleepy
 * @author   Jaime A. Rodriguez <hi.i.am.jaime@gmail.com>
 * @license  http://opensource.org/licenses/MIT; MIT
 * @link     https://sleepymustache.com
 */
class Hook
{
    public static $hooks = [];

    /**
     * Has this been initialized?
     *
     * @var     bool
     * @private
     */
    private static $_initialized = false;

    /**
     * An array of filters
     *
     * @var     Filter[]
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
     * @return  void
     */
    private function __clone()
    {
    }

    /**
     * The constructor is private to ensure we only have one instance
     *
     * @private
     * @return  void
     */
    private function __construct()
    {
    }

    /**
     * Return instance or create initial instance
     *
     * @private
     * @static
     * @return  object
     */
    private static function _initialize()
    {
        if (!self::$_initialized) {
            self::$directories[]
                = DIRBASE . DIRECTORY_SEPARATOR . 'module' . DIRECTORY_SEPARATOR;
            self::$_initialized = true;
            self::_load();
        }
    }

    /**
     * Loads all the modules
     *
     * @private
     * @static
     * @return  void
     */
    private static function _load()
    {
        $directories = self::$directories;

        // get all subdirectories
        foreach (self::$directories as $directory) {
            $subdirectories = glob($directory . '/*', GLOB_ONLYDIR);

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

            foreach ($files as $file) {
                if (strpos($file, '_test.php') !== false) {
                    continue;
                }

                include_once $file;
            }
        }
    }

    /**
     * Adds a new filter to a filter-type hook point
     *
     * @param string $name     [description]
     * @param string $function [description]
     *
     * @static
     * @return void
     */
    public static function applyFilter($name, $function)
    {
        self::_initialize();

        $name = strtolower($name);
        $args = func_get_args();

        array_shift($args);
        array_shift($args);

        if (!isset(self::$_filters[$name])) {
            self::$_filters[$name] = new Filter($name);
        }

        // add the function to the filter
        self::$_filters[$name]->add($function, $args);
    }

    /**
     * Adds a new filter-type hook point
     *
     * @param mixed  $name  [description]
     * @param string $value [description]
     *
     * @static
     * @return void
     */
    public static function addFilter($name, $value)
    {
        self::_initialize();
        $name = strtolower($name);
        array_push(self::$hooks, $name);

        // If there are no functions to run
        if (!isset(self::$_filters[$name])) {
            if (is_array($value)) {
                return $value[0];
            } else {
                return $value;
            }
        }

        foreach (self::$_filters[$name]->functions as $function) {
            if (is_array($value)) {
                $returned = call_user_func_array($function['call'], $value);
            } else {
                $returned = call_user_func($function['call'], $value);
            }
        }

        return $returned;
    }

    /**
     * Adds a new function to a action-type hook point
     *
     * @param string $name     Name of filter
     * @param string $function Function to call
     *
     * @static
     * @return void
     */
    public static function doAction($name, $function)
    {
        call_user_func_array('self::applyFilter', func_get_args());
    }

    /**
     * Adds a new action-type hook point
     *
     * @param string $name [description]
     *
     * @static
     * @return void
     */
    public static function addAction($name)
    {
        self::addFilter($name, '');
    }

    /**
     * Registers a module
     *
     * @param Module $className The Module to register
     *
     * @return void
     */
    public static function register($className)
    {
        if ($className instanceof Module) {
            //$x = new $className();
        } else {
            throw new Exception('Modules must extend \Sleepy\Core\Module');
        }
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
 * @category Core
 * @package  Sleepy
 * @author   Jaime A. Rodriguez <hi.i.am.jaime@gmail.com>
 * @license  http://opensource.org/licenses/MIT; MIT
 * @version  Release: 2.0.0
 * @link     https://sleepymustache.com
 * @date     March 02, 2020
 * @internal
 */
class Filter
{
    /**
     * The name of the filter
     *
     * @var string
     */
    public $name;

    /**
     * A list of functions to execute
     *
     * @var string[]
     */
    public $functions = [];

    /**
     * Constructor
     *
     * @param string $name The name of the filter
     */
    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * Adds a function to this filter
     *
     * @param string $function The function to call
     * @param array  $args     An array of parameters
     *
     * @return void
     */
    public function add($function, $args)
    {
        array_push(
            $this->functions,
            [
                "call" => $function,
                "arguments" => $args
            ]
        );
    }
}