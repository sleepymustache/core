<?php
namespace Sleepy;

require_once('class.router.php');
require_once('class.model.php');

/**
 * The base controller
 *
 * ## Usage
 *
 * <code>
 *   class Home extends Controller {
 *     public function index(Route $route): View {
 *       return View(new Model());
 *     }
 *   }
 * </code>
 *
 * ## Changelog
 *
 * ### Version 1.0
 * * Initial release
 *
 * @date May 17, 2019
 * @author Jaime A. Rodriguez <hi.i.am.jaime@gmail.com>
 * @version 1.0
 * @license  http://opensource.org/licenses/MIT
 */
abstract class Controller {}