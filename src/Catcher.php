<?
namespace ErrorHook;

/**
 * Class Catcher
 * @package ErrorHook
 */
class Catcher
{
	private $previousErrorHandler = null;
	private $types = [
		'E_ERROR', 'E_WARNING', 'E_PARSE', 'E_NOTICE', 'E_CORE_ERROR',
		'E_CORE_WARNING', 'E_COMPILE_ERROR', 'E_COMPILE_WARNING',
		'E_USER_ERROR', 'E_USER_WARNING', 'E_USER_NOTICE', 'E_STRICT',
		'E_RECOVERABLE_ERROR', 'E_DEPRECATED', 'E_USER_DEPRECATED',
	];
	private $notifier;

	/**
	 * register all hooks
	 */
	public function __construct($notifier)
	{
		$this->notifier = $notifier;
		$this->previousErrorHandler = set_error_handler([$this, 'handleNotice']);
		register_shutdown_function([$this, 'handleFatal']);
	}

	/**
	 * @param $errno
	 * @param $errstr
	 * @param $errfile
	 * @param $errline
	 * @return mixed
	 */
	public function handleNotice($errno, $errstr, $errfile, $errline)
	{
		if(!($errno & error_reporting()))
		{
			return $this->callPreviousErrorHandler($errno, $errstr, $errfile, $errline);
		}

		$trace = debug_backtrace();
		array_shift($trace);
		if($this->notify($errno, $errstr, $errfile, $errline, $trace) === false)
		{
			return $this->callPreviousErrorHandler($errno, $errstr, $errfile, $errline, $trace);
		}
	}

	/**
	 * handle fatal error
	 */
	public function handleFatal()
	{
		$error = error_get_last();
		if(!is_array($error) || !in_array($error['type'], array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR)))
		{
			return;
		}
		$this->notify($error['type'], $error['message'], $error['file'], $error['line'], null);
	}

	/**
	 * @param mixed
	 * @return bool|mixed
	 */
	private function callPreviousErrorHandler()
	{
		if($this->previousErrorHandler)
		{
			$args = func_get_args();
			return call_user_func_array($this->previousErrorHandler, $args);
		}
		return false;
	}

	/**
	 * Processes a notification.
	 * @param mixed $errno
	 * @param string $errstr
	 * @param string $errfile
	 * @param int $errline
	 * @param array $trace
	 * @return bool  True if we need to stop the processing.
	 */
	private function notify($errno, $errstr, $errfile, $errline, $trace)
	{
		// Translate error number to error name
		if(is_numeric($errno))
			foreach($this->types as $t)
			{
				if(defined($t) && $errno == constant($t))
				{
					$errno = $t;
				}
			}

		return $this->notifier->notify($errno, $errstr, $errfile, $errline, $trace);
	}

}
