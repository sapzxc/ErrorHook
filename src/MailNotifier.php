<?
namespace ErrorHook;

/**
 * Class MailNotifier
 * @package ErrorHook
 */
class MailNotifier extends AbstractNotifier
{
	const ENCODING = 'UTF-8';

	private $to;
	private $from;

	/**
	 * @param $to
	 * @param $from
	 */
	public function __construct($to, $from)
	{
		$this->to = $to;
		$this->from = $from;
		$this->trackDuplicates = 300;
	}

	/**
	 * @param $items
	 * @return bool
	 */
	public function notifyDetailed($items)
	{
		$body = $items->main."\n\n"
			.$this->makeSection('TRACE', $items->trace)."\n\n"
			.$this->makeSection('GET', $items->get)."\n\n"
			.$this->makeSection('POST', $items->post)."\n\n"
			.$this->makeSection('COOKIE', $items->cookie)."\n\n"
			.$this->makeSection('SERVER', $items->server)."\n\n"
			.(isset($items->session) ? $this->makeSection('SESSION', $items->session)."\n\n" : '')
			.(isset($items->files) ? $this->makeSection('FILES', $items->files)."\n\n" : '')
			.$this->makeSection('DETAILED TRACE', $items->traceTxt)."\n\n"
		;

		return mail(
			$this->to,
			$this->encodeMailHeader('[ERROR] '.$items->title),
			$body,

			"From: {$this->from}".
			"\r\nContent-Type: text/plain; charset=UTF-8"
		);
	}

	/**
	 * @param $header
	 * @return mixed
	 */
	private function encodeMailHeader($header)
	{
		return preg_replace_callback(
			'/((?:^|>)\s*)([^<>]*?[^\w\s.][^<>]*?)(\s*(?:<|$))/s',
			function($p){
				return $p[1] . '=?'.self::ENCODING.'?B?' . base64_encode($p[2]) . "?=" . $p[3];
			},
			$header
		);
	}
}
