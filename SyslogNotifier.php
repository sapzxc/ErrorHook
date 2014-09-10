<?
namespace ErrorHook;

/**
 * Class SyslogNotifier
 * @package ErrorHook
 */
class SyslogNotifier extends AbstractNotifier
{
	private $uid;
	private $label    = '';

	/**
	 * @param $logIndex
	 * @param $label
	 */
	public function __construct($logIndex, $label)
	{
		$this->uid = uniqid();
		$this->label = $label;
		openlog($this->uid, LOG_ODELAY, $logIndex);
	}

	/**
	 * @param stdClass $items
	 * @return bool|mixed
	 */
	public function notifyDetailed($items)
	{
		return syslog(LOG_DEBUG, $this->label."\n".
			$items->title."\n".$items->trace
		);
	}
}
