<?php
include "wc3.php";
if(empty($_GET['id']) || !$download = Download::get($_GET['id']))
	die("404 - Download not found anywhere in existence.");
$download->addView();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Frameset//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>WCDDL - <?php echo $download->showTitle(); ?></title>
<meta name="keywords" content="Place your keywords here" />
<meta name="description" content="Place your description here" />
</head>

<frameset cols="250,*" frameborder="no" border="0" framespacing="0">
  <frame src="leftbar.php?id=<?php echo $download->id; ?>" name="leftFrame" scrolling="No" noresize="noresize" id="leftFrame" />
  <frame src="<?php echo $download->url; ?>" name="mainFrame" id="mainFrame" />
</frameset>
<noframes><body>
Get frames!
</body>
</noframes></html>
