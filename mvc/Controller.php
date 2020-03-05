<?php
/**
 * The base controller
 *
 * ## Usage
 *
 * ~~~ php
 *   class Home extends Controller {
 *     public function index(Route $route): View {
 *       return View(new Model());
 *     }
 *   }
 * ~~~
 *
 * ## Changelog
 *
 * ### Version 2.0a
 * * Converted to PSR-4
 *
 * ### Version 1.1
 * * Updated documentation
 * * Add Hook Actions
 *
 * ### Version 1.0
 * * Initial release
 *
 * php version 7.0.0
 *
 * @category MVC
 * @package  Sleepy
 * @author   Jaime A. Rodriguez <hi.i.am.jaime@gmail.com>
 * @license  http://opensource.org/licenses/MIT; MIT
 * @link     https://sleepymustache.com
 */

namespace Sleepy\MVC;

/**
 * The controller class must be extended for all Controllers in the routed version
 * of sleepyMUSTACHE.
 *
 * @category MVC
 * @package  Sleepy
 * @author   Jaime A. Rodriguez <hi.i.am.jaime@gmail.com>
 * @license  http://opensource.org/licenses/MIT; MIT
 * @link     https://sleepymustache.com
 */
abstract class Controller
{
    /**
     * The constructor
     */
    public function __construct()
    {
        if (class_exists('Hook')) {
            Hook::addFilter('controller_preprocess', $string);
        }
    }

    /**
     * The destructor
     */
    public function __destruct()
    {
        if (class_exists('Hook')) {
            Hook::addFilter('controller_postprocess', $string);
        }
    }
}