<?php
define('PUN_ROOT', '/srv/http/forum/');
include PUN_ROOT.'include/common.php';
ob_start();
//error_reporting(E_ALL);
//$time_start = microtime(true);

$html_header = <<<EOT
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title>Quaddicted.com Quake Map Reviews, User Activity Log</title>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<link rel="stylesheet" type="text/css" href="/static/style.css" />
	<link rel="icon" href="/favicon.ico" type="image/x-icon" />
	<link rel="shortcut icon" href="/favicon.ico" type="image/x-icon" />
	<link rel="alternate" type="application/rss+xml" title="Quaddicted.com - Quake Singleplayer Archive and News RSS Feed" href="/?feed=rss2" />
</head>
<body>
        <div id="wrapper">
EOT;

echo $html_header;
require("_header.php");
echo '<div id="content">';

$dbq = new PDO('sqlite:/srv/http/quaddicted.sqlite');
$redirect_url = "/reviews/log.php";
include("userbar.php"); // include the top login bar, provides $loggedin = true/false

if (!$loggedin) {
	header('HTTP/1.0 403 Forbidden');
	echo "This feature is only for registered users. Register and/or login.";
	require("_footer.php");
	die();
}

$dbq = new SQLite3('/srv/http/quaddicted.sqlite');

echo "<h1>Latest Activity</h1>";

echo '<div style="float:left; margin:10px;"><h2>Users</h2>';
$results = $dbq->query('SELECT username FROM users ORDER BY rowid DESC LIMIT 10');
while ($row = $results->fetchArray()) {
	echo "<a href=\"user.php?username=".htmlspecialchars($row['username'])."\">".htmlspecialchars($row['username'])."</a><br />\n";
}

echo '</div><div style="float:left; margin:10px;"><h2>Tags</h2>';
$results = $dbq->query('SELECT * FROM tags ORDER BY id DESC LIMIT 10');
while ($row = $results->fetchArray()) {
	echo htmlspecialchars($row['tag'])." <small>on</small> <a href=\"".$row['zipname'].".html\">".$row['zipname']."</a> <small>by</small> ".htmlspecialchars($row['username'])."<br />\n";
}

echo '</div><div style="float:left; margin:10px;"><h2>Ratings</h2>';
$results = $dbq->query('SELECT * FROM ratings ORDER BY id DESC LIMIT 10');
while ($row = $results->fetchArray()) {
	echo htmlspecialchars($row['rating_value'])." <small>on</small> <a href=\"".$row['zipname'].".html\">".$row['zipname']."</a> <small>by</small> ".htmlspecialchars($row['username'])."<br />\n";
}

echo '</div><div style="float:left; margin:10px;"><h2>Comments</h2>';
$results = $dbq->query('SELECT * FROM comments ORDER BY timestamp DESC LIMIT 10');
while ($row = $results->fetchArray()) {
	echo htmlspecialchars($row['username'])." <small>on</small> <a href=\"".$row['zipname'].".html#comments\">".$row['zipname']."</a>: ";
	// cut long comments
	$commenttext = htmlspecialchars($row['comment']);
	if (strlen($commenttext) > 100) {
		echo substr($commenttext,0,100)."...";
	} else {
		echo $commenttext;
	}
	echo "<br />\n";
}

echo '</div><div style="float:left; margin:10px;"><h2>Maps</h2>';
$results = $dbq->query('SELECT zipname,author,title,date FROM maps ORDER BY id DESC LIMIT 10');
while ($row = $results->fetchArray()) {
	echo "<small>".htmlspecialchars($row['date'])."</small> <a href=\"".$row['zipname'].".html\">".htmlspecialchars($row['zipname'])."</a> by ".htmlspecialchars($row['author'])."<br />\n";
}

echo '</div><div style="float:left; margin:10px;"><h2>Demos</h2>';
$results = $dbq->query('SELECT zipname,username,skill FROM demos ORDER BY id DESC LIMIT 10');
while ($row = $results->fetchArray()) {
        echo "<a href=\"".$row['zipname'].".html\">".htmlspecialchars($row['zipname'])."</a> on Skill ".htmlspecialchars($row['skill'])." by ".htmlspecialchars($row['username'])."<br />\n";
}
echo "</div>";


unset($dbq);
//$time_end = microtime(true);
//$time = $time_end - $time_start;
//echo "Rendered in ".($time*1000)." ms\n";

require("_footer.php");
ob_end_flush();
?>

