<?php
/**
 * A specific type of controller meant to handle CRUD operations.
 *
 * It automatically sends the correct HTTP methods and routes to the
 * correct actions.
 *
 * ## Usage
 *
 * ~~~ php
 *   class Contacts extends CRUD {
 *     public function create(Route $route) : View {
 *       return new View(new Model(), 'default');
 *     }
 *   }
 * ~~~
 *
 * ## Changelog
 *
 * ### Version 2.0a
 * * Converted to PSR-4
 *
 * ### Version 1.0.1
 * * Updated documentation
 *
 * ### Version 1.0
 * * Initial Release
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

use Sleepy\MVC\Route;
use Sleepy\MVC\View;


/**
 * The CRUD class
 *
 * The CRUD class is a controller that handles the basic CRUC functionality
 * automatically
 *
 * @category Core
 * @package  Sleepy
 * @author   Jaime A. Rodriguez <hi.i.am.jaime@gmail.com>
 * @license  http://opensource.org/licenses/MIT; MIT
 * @link     https://sleepymustache.com
 */
abstract class CRUD extends Controller
{

    /**
     * Default action routes based on method
     *
     * @param Route $route The Route
     *
     * @return View
     */
    public function index(Route $route) : View
    {
        switch ($route->method) {
        case 'OPTIONS':
            http_response_code(200);
            return new View(new Model(), 'default');
        case 'POST':
            http_response_code(201);
            return $this->create($route);
            break;
        case 'GET':
            if (is_numeric($route->params['id'])) {
                http_response_code(200);
                return $this->read($route);
            } else {
                http_response_code(200);
                return $this->list($route);
            }
            break;
        case 'DELETE':
            http_response_code(204);
            return $this->delete($route);
            break;
        case 'PUT':
            http_response_code(204);
            return $this->update($route);
        }
    }

    /**
     * Gets a list of items
     *
     * @param Route $route The Route
     *
     * @return View
     */
    abstract function list(Route $route) : View;

    /**
     * Create a new item
     *
     * @param Route $route The Route
     *
     * @return View
     */
    abstract function create(Route $route) : View;

    /**
     * Gets a single item
     *
     * @param Route $route The Route
     *
     * @return View
     */
    abstract function read(Route $route) : View;

    /**
     * Updates a single item
     *
     * @param Route $route The Route
     *
     * @return View
     */
    abstract public function update(Route $route) : View;

    /**
     * Deletes a single item
     *
     * @param Route $route The Route
     *
     * @return View
     */
    abstract function delete(Route $route) : View;
}