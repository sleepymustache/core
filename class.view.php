<?php
namespace Sleepy;

require_once('class.model.php');
require_once('class.template.php');

/**
 * The presentation layer in MVC
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
 * ### Version 2.0
 * * Views are now templates
 *
 * ### Version 1.0
 * * Initial Release
 *
 * @date May 21, 2019
 * @author Jaime A. Rodriguez <hi.i.am.jaime@gmail.com>
 * @version 2.0
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