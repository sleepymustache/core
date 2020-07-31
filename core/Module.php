<?php
/**
 * The Module Class
 *
 * The module class is used to extend the functionality of sleepyMUSTACHE. It works
 * in conjunction with the Hooks module to enable action points and filters.active
 *
 * php version 7.0.0
 *
 * @category Core
 * @package  Sleepy
 * @author   Jaime A. Rodriguez <hi.i.am.jaime@gmail.com>
 * @license  http://opensource.org/licenses/MIT; MIT
 * @link     https://sleepymustache.com
 */

namespace Sleepy\Core;

use Sleepy\Core\Hook;

/**
 * The Module class that all modules must extend
 *
 * @category Core
 * @package  Sleepy
 * @author   Jaime A. Rodriguez <hi.i.am.jaime@gmail.com>
 * @license  http://opensource.org/licenses/MIT; MIT
 * @link     https://sleepymustache.com
 */
abstract class Module
{
    public $hooks = [];

    public $environments = [
        'dev'   => true,
        'stage' => true,
        'live'  => true
    ];

    /**
     * The constructor
     */
    public function __construct()
    {
        $this->_render();            
        Hook::addAction((new \ReflectionClass($this))->getShortName() . "_preprocess");
    }

    /**
     * The destructor
     */
    public function __destruct()
    {
        Hook::addAction((new \ReflectionClass($this))->getShortName() . "_postprocess");
    }

    /**
     * The main render function that always gets executed
     *
     * @return void
     */
    private function _render()
    {
        if (!is_array($this->hooks)) {
            throw new \Exception(
                '$this->hooks need to be an associative array of hook -> method'
            );
        }

        if (SM::isLive() && !$this->environments['live']) {
            return false;
        }

        if (SM::isStage() && !$this->environments['stage']) {
            return false;
        }

        if (SM::isDev() && !$this->environments['dev']) {
            return false;
        }

        foreach ($this->hooks as $key => $value) {
            Hook::applyFilter($key, array($this, $value));
        }
    }
}
