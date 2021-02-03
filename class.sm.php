<?php
namespace Sleepy;

/**
 * Provides sleepyMUSTACHE core bootstrap functionality
 *
 * ## Usage
 *
 * <code>
 *   \Sleepy\SM::initialize();
 * </code>
 *
 * ## Changelog
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
 * @date May 9, 2019
 * @author Jaime A. Rodriguez <hi.i.am.jaime@gmail.com>
 * @version 1.3
 * @license http://opensource.org/licenses/MIT
 */
class SM {
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
  private function __clone() {}

  /**
   * The constructor is private to ensure we only have one sleepy_preprocess
   * and sleepy_postprocess hooks.
   *
   * @return void
   */
  private function __construct() {
    require_once('class.debug.php');

    // Enable sessions
    session_start();

    // Teamsite fixes
    if (@include_once('Webkit/init.php')) {
      define('TEAMSITE', true);
      $_SERVER['DOCUMENT_ROOT'] = $docroot;
    } else {
      $WHG_DB_HOST = "";
      $WHG_DB_USER = "";
      $WHG_DB_PASSWD = "";
      $WHG_DB_REPLDB = "";
    }

    // Check for the settings overide in the root
    if (@!include_once(__DIR__ . '../../settings.php')) {
      include_once('settings.php');
    }

    require_once('class.hooks.php');
    require_once('class.template.php');
    require_once('class.router.php');

    ob_start();
    Hook::addAction('sleepy_preprocess');

    // Send the encoding ahead of time to speed up rendering
    header('Content-Type: text/html; charset=utf-8');
  }

  /**
   * Show the buffered pages with actions and filters
   *
   * @return void
   */
  public function __destruct() {
    echo Hook::addFilter('sleepy_render', ob_get_clean());
    Hook::addAction('sleepy_postprocess');
  }

  /**
   * Initialized the SM class
   *
   * @return void
   */
  public static function initialize() {
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
  public static function isLive() {
    return (ENV == 'LIVE');
  }

  /**
   * Checks if we are in the staging environment
   *
   * @return boolean True Are we in the staging environment?
   */
  public static function isStage() {
    return (ENV == 'STAGE');
  }

  /**
   * Checks if we are in the development environment
   *
   * @return boolean Are we in the development environment?
   */
  public static function isDev() {
    return (ENV != 'LIVE' && ENV != 'STAGE');
  }

  /**
   * Checks if the current site matches a URL
   *
   * @param  string  $str The URL to match with current site
   * @return boolean      true if there was a match
   */
  public static function isENV($str) {
    foreach (explode(',' , $str) as $url) {
      if (stripos($_SERVER['SERVER_NAME'], $url) !== false)
        return true;
    }

    return false;
  }
}