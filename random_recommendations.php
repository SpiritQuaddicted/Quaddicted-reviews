<?php
ob_start();
//error_reporting(E_ALL);
//$time_start = microtime(true);

define('PUN_ROOT', '/srv/http/forum/');
include PUN_ROOT.'include/common.php';

$html_header = <<<EOT
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
  <head>
    <title>Quaddicted.com: Quake Singleplayer Maps</title>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="keywords" content="quake, quake maps, quake levels, quake singleplayer, quake downloads" />
    <meta name="description" content="This is the most comprehensive archive of singleplayer maps for Quake." />
    <link rel="icon" href="/favicon.ico" type="image/x-icon" />
    <link rel="shortcut icon" href="/favicon.ico" type="image/x-icon" />
    <link rel="alternate" type="application/rss+xml" title="Quaddicted.com - Quake Singleplayer Archive and News RSS Feed" href="/?feed=rss2" />
    
<style type="text/css">
	body {font-family:'Droid Sans','Bitstream Vera Sans',tahoma,Sans-Serif;font-size:12px;background-color:#0d0b05;color:#fff;line-height: 18px;}
	div#logo {text-align:center;}
	div#widewrapper {width:1012px;margin-left:auto;margin-right:auto;}
	div#widecontent {background-color:#201b0e;width:100%;border:1px #333 solid;margin-bottom:20px;padding:2px;}
	img.screenshot {width:330px;height:248px;}
	div.file {background-color:#363020;float:left;margin:2px;border:1px solid #555;width:330px;height:248px;}
</style>
</head>
  <body>
EOT;

$html_header2 = <<<EOT
    <div id="logo"><a href="/"><img src="/static/top.png" alt="Quaddicted.com Logo by Husker" /></a></div>
    <div id="widewrapper">
      <div id="widecontent">
      Random Quake 1 singleplayer map recommendations. Refresh to refresh.<br />
EOT;

echo $html_header;
//$time = microtime(true) - $time_start;
/*
$dbq = new PDO('sqlite:/srv/http/quaddicted.sqlite'); //userbar needs this
$redirect_url = "/reviews/";
include("userbar.php"); // include the top login bar, provides $loggedin = true/false
$dbq = NULL; // the PDO is no longer needed, sqlite3 is used below
*/

echo $html_header2;
$db = new SQLite3('/srv/http/quaddicted.sqlite');

$query="SELECT author,zipname,title,rating,num_comments,num_ratings,sum_ratings FROM maps WHERE rating = 5 AND num_ratings > 3 ORDER BY random() DESC LIMIT 9";
//echo $query;

$results = $db->query($query);

while ($row = $results->fetchArray(SQLITE3_ASSOC)) {
	
	echo "	<div class=\"file\" >";
	echo "		<div class=\"screenshot\">
				<a href=\"/reviews/".$row['zipname'].".html\">
				<img src=\"/reviews/screenshots/".$row['zipname']."_thumb.jpg\" alt=\"Screenshot of ".$row['zipname']."\" class=\"screenshot\" /></a>
			</div>";

/*	echo "		<p class=\"description\">
				<a href=\"".$row['zipname'].".html\">".$row['zipname'].".zip - ".$row['title']." by ".$row['author']."</a>
				<br />\n";
	echo "		</p>\n";
*/

	echo "	</div>\n";
}

echo "<div style=\"clear:both;\"></div>";

$db = NULL;

/*$time_end = microtime(true);
$time = $time_end - $time_start;
echo "<span><small>".($time*1000)." :(</small></span>\n";*/
require("_footer.php");
ob_end_flush();
?>
