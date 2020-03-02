<?php
/**
 * The presentation layer in MVC
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
 * ### Version 1.1.1
 * * Updated documentation
 *
 * ### Version 1.1
 * * Views are now templates
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

/**
 * The View class
 *
 * All views must extend the view class
 *
 * @category MVC
 * @package  Sleepy
 * @author   Jaime A. Rodriguez <hi.i.am.jaime@gmail.com>
 * @license  http://opensource.org/licenses/MIT; MIT
 * @link     https://sleepymustache.com
 */
class View
{
    /**
     * The contstructor
     *
     * @param Model  $model    The model for the view
     * @param string $template which template to use for rendering the view
     */
    public function __construct(Model $model, string $template="default")
    {
        $this->model = $model;

        $this->page = new Template(
            $template,
            DIRBASE . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR
        );
        $this->page->model = &$this->model;
        $this->_modelToPlaceholder($model);
        $this->_render($model);
    }

    /**
     * Render function used to scope out variables
     *
     * @param Model $model The model to use
     *
     * @return void
     */
    private function _render($model)
    {
        $this->page->show();
    }

    /**
     * Creates placeholders for all Model properties
     *
     * @param Model $model The model to use
     *
     * @return void
     */
    private function _modelToPlaceholder($model)
    {
        foreach ($model as $key => $value) {
            $this->page->bind($key, $value);
        }
    }
}