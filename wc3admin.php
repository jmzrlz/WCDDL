<?php
session_start();
include "wc3.php";
Admin::load()->authenticate();
Admin::load()->init();
$adminLinks = Core::load()->parseConfig('admin_links');
if(!is_array($adminLinks)) $adminLinks = array();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>WCDDL</title>
<link rel="stylesheet" type="text/css" href="style.css" media="screen" />
<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.5/jquery.min.js"></script>
<?php echo Core::load()->templateVar('admin_head'); ?>
</head>
<body>
<div id="wrap">
	<div id="top"></div>
	<div id="content">
		<div class="header">
		<h1><a href="/">WCDDL</a></h1>
		<h2>WCDDL Admin</h2>
		</div>
		
        <div class="breadcrumbs">
		<a href="/">DDL Home</a> &middot;
<?php
foreach($adminLinks as $adminLink) {
	echo '<a class="admin_nav" href="' . $adminLink[0] . '">' . $adminLink[1] . '</a> &middot; ';
}
?>
		</div>

		<div class="middle" style="float:none; width:100%;">
		<h2>Welcome Admin!</h2>
<?php
		Admin::load()->handleContent();
?>
		</div>

		<div id="clear"></div>
	</div>
	
    <div id="bottom"></div>
</div>

<div id="footer">
Copyright &copy; 2009 YourSite - Powered by <a href="http://warezcoders.com/">WCDDL</a>
</div>

</body>
</html>
