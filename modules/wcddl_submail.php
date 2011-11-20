<?php
// WCDDL3 Submail
// Coded by JmZ
// mmm chocolate

if(!defined("WCDDL_GUTS"))
		exit;

// Set to true to enable this module
$modEnabled = false;

class subMail {
	// Change each of these to what you want
	public $from = 'contact@mysite.com';
	public $from_name = 'MySite';
	public $subject = 'Submissions Accepted at MySite!';
	public $message = "{sname},
Your downloads were successfully submitted to MySite.com and have now been placed in the queue for further inspection before being listed publicly.
Please allow upto 3 days for this process to be completed as we have a large amount of downloads to deal with.

The following downloads were received:
{downloads}
If some downloads are missing, they were excluded automatically due to being invalid.
If you see no downloads above, we recommend you re-submit them as our system has faced problems during the submission process.
			
MySite Staff";

	public function send($submit) {
		$this->message = str_replace(
			array(
				'{sname}',
				'{surl}',
				'{email}',
				'{downloads}'
			),
			array(
				$submit->sname,
				$submit->surl,
				$submit->email,
				$this->prepareDownloads($submit)
			),
			$this->message);
		mail($submit->email, $this->subject, $this->message, "From: " . $this->from_name . " <" . $this->from . ">");
	}

	private function prepareDownloads($submit) {
		$str = "";
		foreach($submit->title as $k => $v)
			if(!empty($v) && !empty($submit->url[$k]))
				$str .= $v . ' - ' . $submit->url[$k] . "\n";
		return $str;
	}
}

if($modEnabled)
	Core::load()->hook('SubmitDownloadPost', array('subMail', 'send'));
