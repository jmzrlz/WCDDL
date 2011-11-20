<?php
// Backlink check
// Coded by JmZ
// LOLLERSKATES

if(!defined("WCDDL_GUTS"))
		exit;

// Set the following to true to enable this mod
// Be sure to change yoursite.com below
$modEnabled = false;

function backlinkCheck($submit) {
	// Change the domain below to yours
	$myURL = 'yoursite.com';
	// FGC can be slow, replace with curl if you want
	// Also, this is only a simple check so not always reliable
	$get = file_get_contents($submit->surl);
	if(!preg_match('#href="http://(www\.)?' . $myURL . '/?"#i', $get))
		$submit->error = 'No backlink detected.';
}

if($modEnabled)
	Core::load()->hook('SubmitValidation', 'backlinkCheck');
