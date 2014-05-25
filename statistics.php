<?php
                  
define('PUN_ROOT', '/srv/http/forum/');
include PUN_ROOT.'include/common.php';
ob_start();
//error_reporting(E_ALL);
$time_start = microtime(true);

$html_header = <<<EOT
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>Quaddicted.com Quake Map Reviews, Statistics</title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<link rel="stylesheet" type="text/css" href="/static/style.css" />
<link rel="stylesheet" type="text/css" href="starrating.css" />
<script type="text/javascript" src="rating.js"></script>
<link rel="icon" href="/favicon.ico" type="image/x-icon" />
<link rel="shortcut icon" href="/favicon.ico" type="image/x-icon" />
<link rel="alternate" type="application/rss+xml" title="Quaddicted.com - Quake Singleplayer Archive and News RSS Feed" href="/?feed=rss2" />
</head>
<body>
        <div id="wrapper">
                <div id="header">
                        <a href="/"><img src="/static/top.png" alt="Quaddicted.com Logo" id="logo" /></a>
                        <div id="quakeinjector"><img src="/static/injector64.png" />Easily install and launch Quake maps with the cross-platform <a href="/tools/quake_injector">Quake Injector</a></div>
                        <br />
                        <br />
                        <span id="navlinks">
                                <a href="/">Frontpage</a>
                                <a href="/forum/viewforum.php?id=5">News</a>
                                <a href="/reviews/">Maps</a>
                                <a href="/archives/">Archives</a>
                                <a href="/articles/">Articles</a>
                                <a href="/interviews/">Interviews</a>
                                <a href="/forum/">Forum</a>
                                <a href="/help">Help</a>
                        </span>
                </div>
<div id="content">
EOT;

$html_footer = <<<EOT
 </div>
</div>
</body>
</html>

EOT;

echo $html_header;
$dbq = new PDO('sqlite:/srv/http/quaddicted.sqlite');
$redirect_url = "/reviews/log.php";
include("userbar.php"); // include the top login bar, provides $loggedin = true/false

$dbq = new SQLite3('/srv/http/quaddicted.sqlite');

echo "<h1>Statistics</h1>";

echo "<h2>General statistics</h2>";
echo "Total number of releases: ";
$results = $dbq->query('SELECT count(*) FROM maps;');
while ($row = $results->fetchArray()) {
        echo htmlspecialchars($row['count(*)'])."<br />\n";
}

echo "Total number of tags: ";
$results = $dbq->query('SELECT count(*) FROM tags;');
while ($row = $results->fetchArray()) {
        echo htmlspecialchars($row['count(*)'])."<br />\n";
}

echo "Total number of comments: ";
$results = $dbq->query('SELECT count(*) FROM comments;');
while ($row = $results->fetchArray()) {
        echo htmlspecialchars($row['count(*)'])."<br />\n";
}

echo "Total number of ratings: ";
$results = $dbq->query('SELECT count(*) FROM ratings;');
while ($row = $results->fetchArray()) {
        echo htmlspecialchars($row['count(*)'])."<br />\n";
}

echo "Maps that do have user ratings: ";
$results = $dbq->query('SELECT count(*) FROM maps WHERE num_ratings!="";');
while ($row = $results->fetchArray()) {
        echo htmlspecialchars($row['count(*)'])."<br />\n";
}

echo "Maps that do not have user ratings: ";
$results = $dbq->query('SELECT count(*) FROM maps WHERE num_ratings="";');
while ($row = $results->fetchArray()) {
        echo htmlspecialchars($row['count(*)'])."<br />\n";
}

echo "Average rating: ";
$results = $dbq->query('SELECT avg(rating_value) FROM ratings;');
while ($row = $results->fetchArray()) {
        echo htmlspecialchars(round($row['avg(rating_value)']),2)."<br />\n";
}

echo "<h2>Releases per year</h2>";
$results = $dbq->query('SELECT count(*) AS cnt, substr(date, 7,10) AS year FROM maps GROUP BY substr(date, 7,10);');
while ($row = $results->fetchArray()) {
	echo htmlspecialchars($row['year']).": ".htmlspecialchars($row['cnt'])."<br />\n";
}

echo "<h2>Releases per rating</h2>";
$results = $dbq->query('SELECT rating, count(*) FROM maps WHERE rating IN (1,2,3,4,5) GROUP BY rating;');
while ($row = $results->fetchArray()) {
        echo htmlspecialchars($row['rating']).": ".htmlspecialchars($row['count(*)'])."<br />\n";
}

echo "<h2>Average ratings per year (editor | users) (Buggy, does average over '' ratings too!)</h2>";
$results = $dbq->query('SELECT avg(rating) AS rating, avg(sum_ratings/num_ratings) AS userrating, substr(date, 7,10) AS year FROM maps WHERE rating!="" GROUP BY substr(date, 7,10);');
while ($row = $results->fetchArray()) {
	echo htmlspecialchars($row['year']).": ".htmlspecialchars(round($row['rating']),1)." | ".htmlspecialchars(round($row['userrating']),1)."<br />\n";
}


unset($dbq);
$time_end = microtime(true);
$time = $time_end - $time_start;
echo "Rendered in ".($time*1000)." ms\n";
echo $html_footer;
ob_end_flush();
?>

