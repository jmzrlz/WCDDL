<?php
defined('WCDDL_GUTS') or die();

// Hook twice
function testOne($rows) {
	array_pop($rows);
}

function testTwo($rows) {
	array_pop($rows);
}

Core::load()->hook('DownloadsGetRows', 'testOne');
Core::load()->hook('DownloadsGetRows', 'testTwo');
