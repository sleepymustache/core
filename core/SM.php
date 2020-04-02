<?php
/**
 * Provides sleepyMUSTACHE core bootstrap functionality
 *
 * ## Usage
 *
 * ~~~ php
 *   \Sleepy\SM::initialize();
 * ~~~
 *
 * ## Changelog
 *
 * ### Version 2.0a
 * * Converted to PSR-4
 *
 * ### Version 1.3.1
 * * Updated documentation
 *
 * ### Version 1.3
 * * No longer mention old setup
 *
 * ### Version 1.2
 * * Updated documentation
 *
 * ### Version 1.1
 * * Updated private prefix (_) for consistency
 * * Updated documentation
 * * Added teamsite fixes
 *
 * ### Version 1.0
 * * Initial commit
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
 * The SM class is used to initialize/get/set information about the sleepyMUSTACHE
 * install.
 *
 * @category Core
 * @package  Sleepy
 * @author   Jaime A. Rodriguez <hi.i.am.jaime@gmail.com>
 * @license  http://opensource.org/licenses/MIT; MIT
 * @link     https://sleepymustache.com
 */
class SM
{
    /**
     * The Live URL
     *
     * @var string
     */
    public static $live_urls = '';

    /**
     * The stage URL
     *
     * @var string
     */
    public static $stage_urls = '';

    /**
     * Stores the instance of SM when initialized
     *
     * @var SM
     */
    private static $_instance;

    /**
     * Is sleepyMUSTACHE initialized?
     *
     * @var boolean
     */
    public static $is_initialized = false;

    /**
     * Prevent class from being cloned
     *
     * @return void
     */
    private function __clone()
    {
    }

    /**
     * The constructor is private to ensure we only have one sleepy_preprocess
     * and sleepy_postprocess hooks.
     *
     * @return void
     */
    private function __construct()
    {
        // Enable sessions
        session_start();

        $settingsFile = $_SERVER['DOCUMENT_ROOT']
            . DIRECTORY_SEPARATOR . 'settings.php';

        // Check for the settings overide in the root
        if (@!include_once $settingsFile) {
            include_once 'settings.php';
        }

        Hook::addAction('sleepy_preprocess');
        register_shutdown_function('\Sleepy\Core\SM::shutdown');
        ob_start();

        // Send the encoding ahead of time to speed up rendering
        if (!headers_sent()) {
            header('Content-Type: text/html; charset=utf-8');
        }
    }

    /**
     * Show the buffered pages with actions and filters
     *
     * @return void
     */
    public static function shutdown()
    {
        echo Hook::addFilter('sleepy_render', ob_get_clean());
        Hook::addAction('sleepy_postprocess');
    }

    /**
     * Initialized the SM class
     *
     * @return void
     */
    public static function initialize()
    {
        if (!self::$is_initialized) {
            self::$is_initialized = true;
            self::$_instance = new SM;
        }
    }

    /**
     * Checks if we are in the live environment
     *
     * @return boolean Are we in the live environment?
     */
    public static function isLive()
    {
        return self::isENV(self::$live_urls);
    }

    /**
     * Checks if we are in the staging environment
     *
     * @return boolean True Are we in the staging environment?
     */
    public static function isStage()
    {
        return self::isENV(self::$stage_urls);
    }

    /**
     * Checks if we are in the development environment
     *
     * @return boolean Are we in the development environment?
     */
    public static function isDev()
    {
        return (!self::isEnv(self::$stage_urls) && !self::isEnv(self::$live_urls));
    }

    /**
     * Checks if the current site matches a URL
     *
     * @param Array $arr The URL to match with current site
     *
     * @return boolean      true if there was a match
     */
    public static function isENV($arr)
    {
        if (!isset($_SERVER['SERVER_NAME'])) {
            return false;
        };

        foreach ($arr as $url) {
            if (stripos($_SERVER['SERVER_NAME'], $url) !== false) {
                return true;
            }
        }

        return false;
    }
}
