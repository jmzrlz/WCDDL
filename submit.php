<?php
include "wc3.php";
$sub_error = $sub_success = '';
if(!empty($_POST)) {
	$subs = Core::load()->mapRequest('Submit', array('title', 'url', 'type', 'sname', 'surl', 'email'));
	if($subs->submit())
		$sub_success = 'Downloads submitted successfully.';
	else
		$sub_error = $subs->error;
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>WCDDL - Submit</title>
<link rel="stylesheet" type="text/css" href="style.css" media="screen" />
</head>
<body>
<div id="wrap">
	<div id="top"></div>
	<div id="content">
		<div class="header">
		<h1><a href="/">WCDDL</a></h1>
		<h2>WCDDL Slogan</h2>
		</div>
		
        <div class="breadcrumbs">
		<a href="/">Home</a> &middot; <a href="submit.php">Submit</a>
		</div>

		<div class="middle">
		<h2>Submit</h2>
                <form action="" method="post">
                <table width="100%" border="0">
                <tr><td><small>Title</small></td><td><small>URL</small></td><td><small>Type</small></td></tr>
                <?php
				for($i=1;$i<=10;$i++) {
				?>
                <tr><td><input type="text" name="title[]" /></td><td><input type="text" name="url[]" /></td><td><select name="type[]">
                <?php
				$opts = !defined('WCDDL_TYPES') ? array() : explode(',', WCDDL_TYPES);
				if(is_array($opts)) {
					foreach($opts as $at) {
						echo '<option value="'.$at.'">'.$at.'</option>';
					}
				}
				?>
                </select></td></tr>
                <?php
				}
				?>
                <tr><td><small>Site Name</small></td><td><small>Site URL</small></td><td>&nbsp;</td></tr>
				<tr>
					<td><input type="text" name="sname" value="<?php echo empty($_POST['sname']) ? '' : common::displayStr($_POST['sname']); ?>" /></td>
					<td><input type="text" name="surl" value="<?php echo empty($_POST['surl']) ? '' : common::displayStr($_POST['surl']); ?>" /></td>
					<td>&nbsp;</td>
				</tr>
                <tr><td><small>Email</small></td><td>&nbsp;</td><td>&nbsp;</td></tr>
				<tr><td><input type="text" name="email" value="<?php echo empty($_POST['email']) ? '' : common::displayStr($_POST['email']); ?>" /></td><td>&nbsp;</td><td>&nbsp;</td></tr>
                <tr><td colspan="3" align="center"><input type="submit" value="Submit Downloads" /></td></tr>
                <tr><td colspan="3" align="center" style="color:#FF0000;"><Br /><?php echo $sub_error; ?></td></tr>
                <tr><td colspan="3" align="center" style="color:green;"><Br /><?php echo $sub_success; ?></td></tr>
                </table>
                </form>
		</div>
		
		<div class="right">
        <h2>Search</h2>
        <p>
            <form action="index.php" method="post">
            <input type="text" name="q" />
            <input type="submit" value="Go" />
            </form>
        </p>
		<h2>Navigation</h2>
		<ul>
            <li><a href="index.php">Home</a></li>
            <li><a href="index.php?type=app">Apps</a></li>
            <li><a href="index.php?type=game">Games</a></li>
            <li><a href="index.php?type=movie">Movies</a></li>
            <li><a href="index.php?type=music">Music</a></li>
            <li><strong><a href="submit.php">Submit</a></strong></li>
		</ul>
        
        <h2>Partners</h2>
        <ul>
            <li><a href="index.html">Link 1 </a></li>
            <li><a href="index.html">Link 2 </a></li>
            <li><a href="index.html">Link 3 </a></li>
            <li><a href="index.html">Link 4</a></li>
        </ul>
        
        <h2>Recent Searches</h2>
        <p>
		<?php
		DownloadQuery::$outputPattern = '<a href="/index.php?q=#queryurl#">#query#</a>';
		echo implode(", ", Downloads::showQueries());
		?>
        </p>
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
