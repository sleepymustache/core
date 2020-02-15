<?php
namespace Sleepy;

require_once('class.model.php');
require_once('class.template.php');

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
 * ### Version 1.1.1
 * * Updated documentation
 *
 * ### Version 1.1
 * * Views are now templates
 *
 * ### Version 1.0
 * * Initial Release
 *
 * @date February 13, 2020
 * @author Jaime A. Rodriguez <hi.i.am.jaime@gmail.com>
 * @version 1.1.1
 * @license  http://opensource.org/licenses/MIT
 */
  class View {
    public function __construct(Model $model, string $template="default") {
      $this->model = $model;

      $this->page = new Template($template, DIRBASE . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR);
      $this->page->model = &$this->model;
      $this->modelToPlaceholder($model);
      $this->render($model);
    }

    /**
     * Render function used to scope out variables
     *
     * @param Model $model
     * @return void
     */
    private function render($model) {
      $this->page->show();
    }

    /**
     * Creates placeholders for all Model properties
     *
     * @param Model $model
     * @return void
     */
    private function modelToPlaceholder($model) {
      foreach($model as $key => $value) {
        $this->page->bind($key, $value);
      }
    }
  }