<?php
namespace Sleepy;

require_once('class.router.php');
require_once('class.model.php');

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
 * ### Version 1.1
 * * Updated documentation
 * * Add Hook Actions
 *
 * ### Version 1.0
 * * Initial release
 *
 * @date May 17, 2019
 * @author Jaime A. Rodriguez <hi.i.am.jaime@gmail.com>
 * @version 1.1
 * @license  http://opensource.org/licenses/MIT
 */
abstract class Controller {
    public function __construct() {
        if (class_exists('Hook')) {
            Hook::addFilter('controller_preprocess', $string);
        }
    }

    public function __destruct() {
        if (class_exists('Hook')) {
            Hook::addFilter('controller_postprocess', $string);
        }
    }
}