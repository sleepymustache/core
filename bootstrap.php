<?php

/**
 * Main bootstrap file
 *
 * ### Usage
 *
 * <code>
 *     require_once('/app/core/sleepy.php');
 * </code>
 *
 * ### Changelog
 *
 * ### Version 2.0a
 * * Converted to PSR-4
 *
 * ## Version 1.1
 * * Added documentation
 *
 * php version 7.0.0
 *
 * @category Core
 * @package  Sleepy
 * @author   Jaime A. Rodriguez <hi.i.am.jaime@gmail.com>
 * @license  http://opensource.org/licenses/MIT; MIT
 * @link     https://sleepymustache.com
 */

// Get the loader that all other Classes will rely on
require_once $_SERVER['DOCUMENT_ROOT'] . '/app/sleepy/core/Loader.php';

use Sleepy\Core\Loader;
use Sleepy\Core\SM;

Loader::register();
Loader::addNamespace('Sleepy', $_SERVER['DOCUMENT_ROOT'] . '/app/sleepy');
Loader::addNamespace('Sleepy\Core', $_SERVER['DOCUMENT_ROOT'] . '/app/sleepy/core');
Loader::addNamespace('Sleepy\MVC', $_SERVER['DOCUMENT_ROOT'] . '/app/sleepy/mvc');

Loader::addNamespace('Module', $_SERVER['DOCUMENT_ROOT'] . '/app/sleepy/modules');
Loader::addNamespace('Model', $_SERVER['DOCUMENT_ROOT'] . '/app/models');


SM::initialize();