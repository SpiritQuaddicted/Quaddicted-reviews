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
	<title>Quaddicted.com Quake Map Reviews, User Leaderboard</title>
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

echo "<h1>Most active users</h1>";
echo "Don't spam just to move on these lists or you will feel the wrath of the Shambler God<br />";

echo "<h2>Ratings</h2>";
echo "<table><th>Username</th><th>Ratings (average)</th>";
$results = $dbq->query('SELECT username, num_ratings, sum_ratings FROM users ORDER BY num_ratings DESC LIMIT 10;');
while ($row = $results->fetchArray()) {
	echo "<tr><td><a href=\"user.php?username=".htmlspecialchars($row['username'])."\">".htmlspecialchars($row['username'])."</a></td><td>".$row['num_ratings']." (".round($row['sum_ratings']/$row['num_ratings'],1).")</td></tr>\n";
}
echo "</table>";

echo "<h2>Comments</h2>";
echo "<table><th>Username</th><th>Comments</th>";
$results = $dbq->query('SELECT username, num_comments FROM users ORDER BY num_comments DESC LIMIT 10;');
while ($row = $results->fetchArray()) {
	echo "<tr><td><a href=\"user.php?username=".htmlspecialchars($row['username'])."\">".htmlspecialchars($row['username'])."</a></td><td>".$row['num_comments']."</td></tr>\n";
}
echo "</table>";

echo "<h2>Tags</h2>";
echo "<table><th>Username</th><th>Tags</th>";
$results = $dbq->query('SELECT username, num_tags FROM users ORDER BY num_tags DESC LIMIT 10');
while ($row = $results->fetchArray()) {
	echo "<tr><td><a href=\"user.php?username=".htmlspecialchars($row['username'])."\">".htmlspecialchars($row['username'])."</a></td><td>".$row['num_tags']."</td></tr>\n";
}
echo "</table>";

unset($dbq);
//$time_end = microtime(true);
//$time = $time_end - $time_start;
//echo "Rendered in ".($time*1000)." ms\n";

require("_footer.php");
ob_end_flush();
?>

