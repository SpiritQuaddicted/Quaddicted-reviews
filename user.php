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
	<title>Quaddicted.com Quake Map Reviews, User Details</title>
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
include("userbar.php"); // include the top login bar, provides $loggedin = true/false

if (!$loggedin) {
	header('HTTP/1.0 403 Forbidden');
	echo "This feature is only for registered users for privacy reasons. Register and/or login.";
	require("_footer.php");
	ob_end_flush();
	die();
}

if (isset($_GET['username'])) {
	$username = $_GET["username"];

	$preparedStatement = $dbq->prepare('SELECT * FROM users WHERE username = :username');
	$preparedStatement->execute(array(':username' => $username));
	$result = $preparedStatement->fetch();

	if ($result) {
		$username = htmlspecialchars($username);
		echo "<h1>".$username."'s user profile</h1>";

		// numbers
		$preparedStatement = $dbq->prepare('SELECT * FROM users WHERE username = :username');
		$preparedStatement->execute(array(':username' => $result['username']));
		$result = $preparedStatement->fetch();
		$preparedStatement->closeCursor();
		echo $result['num_ratings']." ratings, ".$result['num_comments']." comments and ".$result['num_tags']." tags";
		//$result['awards']. TODO

		// ratings
		$preparedStatement = $dbq->prepare('SELECT * FROM ratings,maps WHERE username = :username AND ratings.zipname == maps.zipname ORDER BY ratings.id DESC LIMIT 10');
		$preparedStatement->execute(array(':username' => $result['username']));
		$ratings = $preparedStatement->fetchAll();

		if ($ratings) {
		echo "<h2>Last ratings:</h2>";
		echo "<ul>";
		foreach ($ratings as $rating){
			echo "<li>";
/*			for ($i=0;$i<$rating['rating_value'];$i++){
				echo "&hearts;";
			}
*/			echo $rating['rating_value']." <small>on</small> <a href=\"".$rating['zipname'].".html\">".$rating['zipname']."</a>";
			echo "</li>";
			}
		echo "</ul>";
		}

		// comments
		$preparedStatement = $dbq->prepare('SELECT * FROM comments,maps WHERE username = :username AND comments.zipname == maps.zipname ORDER BY comments.id DESC LIMIT 10');
		$preparedStatement->execute(array(':username' => $result['username']));
		$comments = $preparedStatement->fetchAll();

		if ($ratings) {
		echo "<h2>Last comments:</h2><ul>";
		foreach ($comments as $comment){
			echo "<li><a href=\"".$comment['zipname'].".html#comments\">".$comment['zipname']."</a>: ";

			// cut long comments
			$commenttext = htmlspecialchars($comment['comment']);
			if (strlen($commenttext) > 100) {
				echo substr($commenttext,0,100)."...";
			} else {
				echo $commenttext;
			}

			echo "</li>";
			}
		echo "</ul>";
		}

		// tags
		$preparedStatement = $dbq->prepare('SELECT * FROM tags, maps WHERE tags.username = :username AND tags.zipname == maps.zipname ORDER BY tags.id DESC LIMIT 10'); //GROUP BY maps.id ORDER BY maps.filename
		$preparedStatement->execute(array(':username' => $result['username']));
		$tags = $preparedStatement->fetchAll();

		if ($tags) {
		echo "<h2>Last tags:</h2><ul>";
		foreach ($tags as $tag){
			echo "<li>".$tag['tag']." <small>on</small> <a href=\"".$tag['zipname'].".html\">".$tag['zipname']."</a></li>";
		}
		echo "</ul>";
		}
	} else {
		echo "That user does not exist.";
	}

	unset($dbq); // unset database connection
}
else {
	echo "You need to specify a username.";
}

//$time_end = microtime(true);
//$time = $time_end - $time_start;
//echo "Rendered in ".($time*1000)." ms\n";

require("_footer.php");
ob_end_flush();
?>
