<?php
include "wc3.php";
$downloads = Core::load()->mapRequest('Downloads', array('page', 'query', 'type'));
if(!empty($downloads->query))
	$downloads->query = str_replace('-', ' ', $downloads->query);
$rows = $downloads->get();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>WCDDL</title>
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
		<h2>Downloads</h2>
        <table width="100%" border="0">
        <tr><td>Type</td><td><strong>Title</strong></td><td>Views</td></tr>
        <?php
        foreach($rows as $row) {
        ?>
		<tr>
			<td><?php echo $row->type; ?></td>
			<td><a href="download.php?id=<?php echo $row->id; ?>"><?php echo $row->showTitle(); ?></a></td>
			<td><?php echo $row->views; ?></td>
		</tr>
        <?php
        }
        ?>
        <tr><td colspan="4">
        <?php
		echo $downloads->pages(array(
			array('default', WCDDL_PAGES_DEFAULT),
			array('query', WCDDL_PAGES_QUERY),
			array('type', WCDDL_PAGES_TYPE),
			array(array('query', 'type'), WCDDL_PAGES_QUERY_TYPE),
		));
        ?>
        </td></tr>
        </table>
		</div>
		
		<div class="right">
        <h2>Search</h2>
        <p>
            <form action="index.php" method="post">
            <input type="text" name="query" />
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
Executed <?php echo Database::queryLogCount(); ?> Queries in <?php echo Database::queryLogTime(); ?> seconds!
</div>
</body>
</html>
