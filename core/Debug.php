<?php
/**
 * Provides custom debugging functions.
 *
 * This class can send emails, log to a database, or display on screen debug
 * information. You can set the enabled flags to enable the debug functions or
 * set them to false to quiet them down. This way you can leave them as a part
 * of your code with little overhead. For email and database logging, don't
 * forget to setup the public properties.
 *
 * ## Usage
 *
 * ~~~ php
 *   use Sleepy\Core;
 *
 *   // Turn debugging to screen on
 *   Debug::$enableShow = true;
 *   Debug::out("This will goto the screen because $enableShow == true");
 *
 *   // Turn off debugging to screen
 *   Debug::$enableShow = false;
 * ~~~
 *
 * ## Changelog
 *
 * ### Version 2.0a
 * * Converted to PSR-4
 *
 * ### Version 1.10
 * * Added the ability to debug straight to the JS console

 * ### Version 1.9
 * * Updated private suffix (_) and documentation for consistency
 *
 * ### Version 1.8
 * * Added namespacing
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

/**
 * The Debug class
 *
 * @category Core
 * @package  Sleepy
 * @author   Jaime A. Rodriguez <hi.i.am.jaime@gmail.com>
 * @license  http://opensource.org/licenses/MIT; MIT
 * @link     https://sleepymustache.com
 */
class Debug
{
    /**
     * The single instance is stored here.
     *
     * @var Debug
     */
    private static $_instance = null;

    /**
     * PDO Database object
     *
     * @var PDO
     */
    private static $_dbPDO;

    /**
     * Enable output to JS Console
     *
     * @var bool
     */
    public static $enableConsole = false;

    /**
     * Enable output to screen
     *
     * @var bool
     */
    public static $enableShow = false;

    /**
     * Enabled logging to a database
     *
     * @var bool
     */
    public static $enableLog = false;

    /**
     * Enabled logging via email
     *
     * @var bool
     */
    public static $enableSend = false;

    /**
     * Email address to send email to.
     *
     * @var string
     */
    public static $emailTo;

    /**
     * Email address cc send email to.
     *
     * @var string
     */
    public static $emailCC;

    /**
     * Email address bcc send email to.
     *
     * @var string
     */
    public static $emailBCC;

    /**
     * Email address to send email from.
     *
     * @var string
     */
    public static $emailFrom;

    /**
     * The subject of the email.
     *
     * @var string
     */
    public static $emailSubject;

    /**
     * The body of the email.
     *
     * @var string[]
     */
    public static $emailBuffer;

    /**
     * Database Host
     *
     * @var string
     */
    public static $dbHost;

    /**
     * Database Name
     *
     * @var string
     */
    public static $dbName;

    /**
     * Database User Name
     *
     * @var string
     */
    public static $dbUser;

    /**
     * Database Password
     *
     * @var string
     */
    public static $dbPass;

    /**
     * Database Table to use for logging
     *
     * @var string
     */
    public static $dbTable;

    /**
     * Prevent class from being cloned
     *
     * @return void
     */
    private function __clone()
    {
    }

    /**
     * The constructor is private to ensure we only have one instance
     *
     * @return void
     */
    private function __construct()
    {
        // Setup email defaults
        $serverIp = (isset($_SERVER['SERVER_ADDR']))
            ? $_SERVER['SERVER_ADDR']
            : '';
        $userIp = (isset($_SERVER['REMOTE_ADDR']))
            ? $_SERVER['REMOTE_ADDR']
            : '';
        $filename = (isset($_SERVER['SCRIPT_FILENAME']))
            ? $_SERVER['SCRIPT_FILENAME']
            : '';
        $date = date(
            DATE_ATOM,
            mktime(date('G'),  date('i'),  0,  date('m'),  date('d'),  date('Y'))
        );

        Debug::$emailBuffer = array();
        Debug::$emailBuffer[] = "Date: {$date}";
        Debug::$emailBuffer[] = "Server IP: {$serverIp}";
        Debug::$emailBuffer[] = "Client IP: {$userIp}";
        Debug::$emailBuffer[] = "Filename: {$filename}";
        Debug::$emailBuffer[] = '---';
        Debug::$emailTo = EMAIL_TO;
        Debug::$emailFrom = EMAIL_FROM;
        Debug::$emailSubject = $date;
        Debug::$emailCC = EMAIL_CC;
        Debug::$emailBCC = EMAIL_BCC;

        // Setup logging defaults
        Debug::$dbHost  = DBHOST;
        Debug::$dbName  = DBNAME;
        Debug::$dbUser  = DBUSER;
        Debug::$dbPass  = DBPASS;
        Debug::$dbTable = 'log';
    }

    /**
     * Send the email when the page is unloaded
     *
     * @return void
     */
    public function __destruct()
    {
        if (self::$enableSend) {
            self::sendEmail();
        }
    }

    /**
     * Return instance or create initial instance
     *
     * @return Debug
     */
    private static function _initialize()
    {
        if (!self::$_instance) {
            self::$_instance = new Debug();
        }

        return self::$_instance;
    }

    /**
     * Displays debug information in JS Console
     *
     * @param mixed $var Anything you want to log
     *
     * @return bool
     *
     * @todo create a hook so the dev can create custom views when outputting
     *       debug data.
     */
    private static function _console($var)
    {
        $output = '';

        if (!self::$enableConsole) {
            return false;
        }

        echo '<script>console.log(';

        if (is_object($var) && \method_exists($var, '__debugInfo')) {
            $output = json_encode($var->__debugInfo());
        } else if (is_array($var) || is_object($var)) {
            $output = json_encode($var);
        } else {
            $output =  "'{$var}'";
        }

        if (class_exists('\Sleepy\Hook')) {
            echo Hook::addFilter('debug_console_output', $output);
        } else {
            echo $output;
        }

        echo ');</script>';

        return true;
    }

    /**
     * Sets the Exception Handler
     *
     * @return void
     */
    public function setHandler()
    {
        self::_initialize();
        set_exception_handler(array('Debug', 'exceptionHandler'));
    }

    /**
     * Exception Handler
     *
     * @param Exception $e The exception
     *
     * @return void
     */
    public function exceptionHandler($e)
    {
        if (headers_sent()) {
            echo 'Error: ' , $e->getMessage(), "\n";
        } else {
            $_SESSION['exception']
                = $e->getMessage()
                . '<br />'
                . str_replace("\n", '<br />', $e->getTraceAsString());
            header('Location: /error/');
        }
    }

    /**
     * Writes to a database log table.  The table should be called log, or set
     * $this->dbTable. It should contain 2 columns: 'datetime, message'
     *
     * @param mixed $var Anything you want to log
     *
     * @return bool
     *
     * @todo add a create for the log table
     */
    private function _log($var)
    {
        if (!self::$enableLog) {
            return false;
        }

        if (is_array($var) || is_object($var)) {
            $buffer = print_r($var, true);
        } else {
            $buffer = $var;
        }

        try {
            // MySQL with PDO_MYSQL
            if (!is_object(self::$_dbPDO)) {
                self::$_dbPDO = new \PDO(
                    'mysql:host=' . self::$dbHost . ';dbname=' . self::$dbName,
                    self::$dbUser, self::$dbPass
                );
                self::$_dbPDO->setAttribute(
                    \PDO::ATTR_ERRMODE,
                    \PDO::ERRMODE_EXCEPTION
                );
            }
            $query = self::$_dbPDO->prepare(
                'INSERT INTO '
                . self::$dbTable
                . ' (datetime, message) values (:datetime, :message)'
            );
            $datetime = date(
                DATE_ATOM,
                mktime(date('G'), date('i'), 0, date('m'), date('d'), date('Y'))
            );
            $query->bindParam(':datetime', $datetime);
            $query->bindParam(':message', $buffer);
            $query->execute();
        } catch(\PDOException $e) {
            self::_show($e->getMessage());
            return false;
        }

        return true;
    }

    /**
     * Displays debug information on screen
     *
     * @param mixed $var Anything you want to log     *
     *
     * @return bool
     *
     * @todo create a hook so the dev can create custom views when outputting
     *       debug data.
     */
    private static function _show($var)
    {
        if (!self::$enableShow) {
            return false;
        }

        echo '<pre>';

        if (is_array($var) || is_object($var)) {
            print_r($var);
        } else {
            echo $var;
        }

        echo '</pre>';

        return true;
    }

    /**
     * Iterates a buffer that gets emailed on __destruct()
     *
     * @param mixed $var Anything you want to log
     *
     * @return bool
     */
    private static function _send($var)
    {
        if (!self::$enableSend) {
            return false;
        }

        if (is_array($var) || is_object($var)) {
            self::$emailBuffer[] = print_r($var, true);
        } else {
            self::$emailBuffer[] = $var;
        }

        return true;
    }

    /**
     * Determines what output methods are enabled and passes $var to it.
     *
     * @param mixed $var Anything you want to log
     *
     * @return void
     */
    public static function out($var)
    {
        $result = true;

        self::_initialize();

        if (self::$enableConsole) {
            $result = $result && self::$_instance->_console($var);
        }

        if (self::$enableSend) {
            $result = $result && self::$_instance->_send($var);
        }

        if (self::$enableLog) {
            $result = $result && self::$_instance->_log($var);
        }

        if (self::$enableShow) {
            $result = $result && self::$_instance->_show($var);
        }

        if (!self::$enableConsole
            && !self::$enableShow
            && !self::$enableSend
            && !self::$enableLog
        ) {
            $result = false;
        }

        return $result;
    }

    /**
     * Sets all the enabled flags to false
     *
     * @return void
     */
    public static function disable()
    {
        self::$enableConsole = false;
        self::$enableLog     = false;
        self::$enableSend    = false;
        self::$enableShow    = false;
    }

    /**
     * Sends the email.
     *
     * @return bool true if sent successfully
     * @todo   make this private, I cannot remember why this is public...
     */
    public static function sendEmail()
    {
        if (!self::$enableSend) {
            return false;
        }

        $headers = array();
        $headers[] = 'From: ' . self::$emailFrom;
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-type: text/html; charset=iso-8859-1';
        if (self::$emailCC != '') {
            $headers[] = 'Cc: ' . self::$emailCC;
        }
        if (self::$emailBCC != '') {
            $headers[] = 'Bcc: ' . self::$emailBCC;
        }
        return mail(
            self::$emailTo,
            self::$emailSubject,
            implode("<br />\n", self::$emailBuffer),
            implode("\n", $headers)
        );
    }
}
