<?
namespace ErrorHook;

/**
 * Class MailNotifier
 * @package ErrorHook
 */
abstract class AbstractNotifier
{
	/**
	 * @var int track error duplicates timeout
	 */
	protected $preventDuplicates = 0;

	/**
	 * notify wrapper to handle detailed notify
	 * @param stdClass $items
	 * @return mixed
	 */
	abstract protected function notifyDetailed($items);

	/**
	 * @param $errno
	 * @param $errstr
	 * @param $errfile
	 * @param $errline
	 * @param $trace
	 * @return mixed
	 */
	public function notify($errno, $errstr, $errfile, $errline, $trace)
	{
		$hash = md5(implode(':', [$errno, $errfile, $errline]));
		if($this->isDuplicateNotify($hash))
		{
			return true;
		}

		$items = new \stdClass();

		// Remain only 1st line for subject.
		$errstrClean = preg_replace("/\r?\n.*/s", '', $errstr);
		$items->title = "$errno: $errstrClean at $errfile on line $errline";

		if(php_sapi_name() == "cli")
		{
			$items->main = "$ ".(isset($_SERVER['argv']) ? serialize($_SERVER['argv']) : '(-no-argv-)');
		}
		else
		{
			$items->main = "//"
				.(isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '(-no-host-)')
				.(isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '(-no-uri-)');
		}

		$items->main .= "\n$errno: $errstr at $errfile on line $errline";

		$items->server = self::varExport($_SERVER);
		$items->cookie = self::varExport($_COOKIE);
		$items->get = self::varExport($_GET);
		$items->post = self::varExport($_POST);

		if(!empty($_SESSION))
		{
			$items->session = self::varExport($_SESSION);
		}

		if(!empty($_FILES))
		{
			$items->files = self::varExport($_FILES);
		}

		$items->trace = self::backtraceToString($trace);

		ob_start();
		debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
		$items->traceTxt = ob_get_contents();
		ob_end_clean();

		return $this->notifyDetailed($items);
	}

	/**
	 * @param $name
	 * @param $body
	 * @return string
	 */
	protected function makeSection($name, $body)
	{
		$body = substr(rtrim($body),0,65535);// cut part of string to prevent " Allowed memory size of ... bytes exhausted"
		if ($name) $body = preg_replace('/^/m', '    ', $body);
		$body = preg_replace('/^([ \t\r]*\n)+/s', '', $body);
		return ($name? $name . ":\n" : "") . $body . "\n";
	}

	/**
	 * Check if is duplicate notification using ->preventDuplicate time
	 * @param $hash
	 * @return bool
	 */
	protected function isDuplicateNotify($hash)
	{
		if(!$this->preventDuplicates)
		{
			// no need any checks
			return false;
		}

		$cid = 'duperr_'.$hash;
		$cacher = \fmcore\CacheRegistry::errorHook();
		if($cacher->hasItem($cid))
		{
			return true;
		}

		$cacher->setItem($cid,1);
		return false;
	}

	/**
	 * var_export clone, without using output buffering.
	 * (For calls in ob_handler)
	 *
	 * @param mixed $var to be exported
	 * @param integer $maxLevel (recursion protect)
	 * @param integer $level of current indent
	 * @return string
	 */
	public static function varExport($var, $maxLevel = 10, $level = 0)
	{
		$escapes = "\"\r\t\x00\$";
		$tab = '    ';

		if (is_bool($var)) {
			return $var ? 'TRUE' : 'FALSE';
		} elseif (is_string($var)) {
			return '"' . addcslashes($var, $escapes) . '"';
		} elseif (is_float($var) || is_int($var)) {
			return $var;
		} elseif (is_null($var)) {
			return 'NULL';
		} elseif (is_resource($var)) {
			return 'NULL /* ' . $var . ' */';
		}

		if ($maxLevel < $level) {
			return 'NULL /* ' . print_r($var,1) . ' MAX LEVEL ' . $maxLevel . " REACHED*/";
		}

		if (is_array($var)) {
			$return = "array(\n";
		} else {
			$return = get_class($var) . "::__set_state(array(\n";
		}

		$offset = str_repeat($tab, $level + 1);

		foreach ((array) $var as $key => $value) {
			$return .= $offset;
			if (is_int($key)) {
				$return .= $key;
			} else {
				$return .= '"' . addcslashes($key, $escapes). '"';
			}
			$return .= ' => ' . self::varExport($value, $maxLevel, $level + 1) . ",\n";
		}

		return $return
		. str_repeat($tab, $level)
		. (is_array($var) ? ')' : '))');
	}

	/**
	 * Analog for debug_print_backtrace(), but returns string.
	 * @param $backtrace
	 * @return string
	 */
	public static function backtraceToString($backtrace)
	{
		// Iterate backtrace
		$calls = array();
		if(!empty($backtrace))
		foreach ($backtrace as $i => $call) {
			if (!isset($call['file'])) {
				$call['file'] = '(null)';
			}
			if (!isset($call['line'])) {
				$call['line'] = '0';
			}
			$location = $call['file'] . ':' . $call['line'];
			$function = (isset($call['class'])) ?
				$call['class'] . (isset($call['type']) ? $call['type'] : '.') . $call['function'] :
				$call['function'];

			$params = '';
			if (isset($call['args']) && is_array($call['args'])) {
				$args = array();
				foreach ($call['args'] as $arg) {
					if (is_array($arg)) {
						$args[] = "Array(...)";
					} elseif (is_object($arg)) {
						$args[] = get_class($arg);
					} else {
						$args[] = $arg;
					}
				}
				$params = implode(', ', $args);
			}

			$calls[] = sprintf('#%d  %s(%s) called at [%s]',
				$i,
				$function,
				$params,
				$location);
		}

		return implode("\n", $calls) . "\n";
	}
}
