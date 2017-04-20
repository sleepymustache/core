<?php
namespace Sleepy;

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
 * <code>
 *   // Turn debugging to screen on
 *   Debug::$enable_show = true;
 *   Debug::out("This will goto the screen because $enable_show == true");
 *
 *   // Turn off debugging to screen
 *   Debug::$enable_show = false;
 * </code>
 *
 * ## Changelog
 *
 * ### Version 1.10
 * * Added the ability to debug straight to the JS console

 * ### Version 1.9
 * * Updated private suffix (_) and documentation for consistency
 *
 * ### Version 1.8
 * * Added namespacing
 *
 * @date April 19, 2017
 * @author Jaime A. Rodriguez <hi.i.am.jaime@gmail.com>
 * @version 1.10
 * @license  http://opensource.org/licenses/MIT
 */

class Debug {
	/**
	 * The single instance is stored here.
	 *
	 * @var Debug
	 * @private
	 */
	private static $_instance = NULL;

	/**
	 * PDO Database object
	 *
	 * @var PDO
	 * @private
	 */
	private static $_dbPDO;

	/**
	 * Enable output to JS Console
	 *
	 * @var bool
	 */
	public static $enable_console = false;

	/**
	 * Enable output to screen
	 *
	 * @var bool
	 */
	public static $enable_show = false;

	/**
	 * Enabled logging to a database
	 *
	 * @var bool
	 */
	public static $enable_log = false;

	/**
	 * Enabled logging via email
	 *
	 * @var bool
	 */
	public static $enable_send = false;

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
	 * @private
	 */
	private function __clone() {}

	/**
	 * The constructor is private to ensure we only have one instance
	 *
	 * @private
	 */
	private function __construct() {
		// Setup email defaults
		$server_ip = (isset($_SERVER['SERVER_ADDR'])) ? $_SERVER['SERVER_ADDR'] : '';
		$user_ip = (isset($_SERVER['REMOTE_ADDR'])) ? $_SERVER['REMOTE_ADDR'] : '';
		$filename = (isset($_SERVER['SCRIPT_FILENAME'])) ? $_SERVER['SCRIPT_FILENAME'] : '';
		$date = date(DATE_ATOM, mktime(date('G'), date('i'), 0, date('m'), date('d'), date('Y')));

		Debug::$emailBuffer = array();
		Debug::$emailBuffer[] = "Date: {$date}";
		Debug::$emailBuffer[] = "Server IP: {$server_ip}";
		Debug::$emailBuffer[] = "Client IP: {$user_ip}";
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
	 */
	public function __destruct() {
		if (self::$enable_send) {
			self::sendEmail();
		}
	}

	/**
	* Return instance or create initial instance
	*
	* @return Debug
	* @private
	*/
	private static function _initialize() {
		if (!self::$_instance) {
			self::$_instance = new Debug();
		}

		return self::$_instance;
	}

	/**
	 * Displays debug information in JS Console
	 *
	 * @param mixed $var Anything you want to log
	 * @return bool
	 * @todo  create a hook so the dev can create custom views when outputting
	 *        debug data.
	 * @private
	 */
	private static function console($var) {
		if (!self::$enable_console) {
			return false;
		}

		echo '<script>console.log(';

		if (is_array($var) || is_object($var)) {
			echo json_encode($var);
		} else {
			echo "'{$var}'";
		}

		echo ');</script>';

		return true;
	}

	/**
	 * sets the Exception Handler
	 */
	public function setHandler() {
		self::_initialize();
		set_exception_handler(array('Debug', 'exceptionHandler'));
	}

	/**
	 * Exception Handler
	 */
	public function exceptionHandler($e) {
		if (headers_sent()) {
			echo 'Error: ' , $e->getMessage(), "\n";
		} else {
			$_SESSION['exception'] = $e->getMessage() . '<br />' . str_replace("\n", '<br />', $e->getTraceAsString()) . '';
			header('Location: /error/');
		}
	}

	/**
	 * Writes to a database log table.  The table should be called log, or set
	 * $this->dbTable. It should contain 2 columns: 'datetime, message'
	 *
	 * @param  mixed $var Anything you want to log
	 * @return bool
	 * @todo add a create for the log table
	 * @private
	 */
	private function log($var) {
		if (!self::$enable_log) {
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
				self::$_dbPDO = new \PDO('mysql:host=' . self::$dbHost . ';dbname=' . self::$dbName, self::$dbUser, self::$dbPass);
				self::$_dbPDO->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
			}
			$query = self::$_dbPDO->prepare('INSERT INTO ' . self::$dbTable . ' (datetime, message) values (:datetime, :message)');
			$datetime = date(DATE_ATOM, mktime(date('G'), date('i'), 0, date('m'), date('d'), date('Y')));
			$query->bindParam(':datetime', $datetime);
			$query->bindParam(':message', $buffer);
			$query->execute();
		} catch(\PDOException $e) {
			self::show($e->getMessage());
			return false;
		}

		return true;
	}

	/**
	 * Displays debug information on screen
	 *
	 * @param mixed $var Anything you want to log
	 * @return bool
	 * @todo  create a hook so the dev can create custom views when outputting
	 *        debug data.
	 * @private
	 */
	private static function show($var) {
		if (!self::$enable_show) {
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
	 * @param mixed $var
	 *   Anything you want to log
	 * @return bool
	 * @private
	 */
	private static function send($var) {
		if (!self::$enable_send) {
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
	 * @param  mixed $var Anything you want to log
	 * @return void
	 */
	public static function out($var) {
		$result = true;

		self::_initialize();

		if (self::$enable_console) {
			$result = $result && self::$_instance->console($var);
		}

		if (self::$enable_send) {
			$result = $result && self::$_instance->send($var);
		}

		if (self::$enable_log) {
			$result = $result && self::$_instance->log($var);
		}

		if (self::$enable_show) {
			$result = $result && self::$_instance->show($var);
		}

		if (!self::$enable_console &&
			!self::$enable_show &&
			!self::$enable_send &&
			!self::$enable_log) {
			$result = false;
		}

		return $result;
	}

	/**
	 * Sets all the enabled flags to false
	 *
	 * @return void
	 */
	public static function disable() {
		self::$enable_console = false;
		self::$enable_log     = false;
		self::$enable_send    = false;
		self::$enable_show    = false;
	}

	/**
	 * Sends the email.
	 *
	 * @return bool true if sent successfully
	 * @todo  make this private, I cannot remember why this is public...
	 */
	public static function sendEmail() {
		if (!self::$enable_send) {
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
		return mail(self::$emailTo, self::$emailSubject, implode("<br />\n", self::$emailBuffer), implode("\n", $headers));
	}
}
