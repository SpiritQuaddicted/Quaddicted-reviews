<?php
header('Content-Type: application/atom+xml; charset=utf-8');
date_default_timezone_set('Europe/Berlin');
$date = new DateTime();
$unixtime = $date->getTimestamp();

ob_start();
echo "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n";

echo "<feed xmlns=\"http://www.w3.org/2005/Atom\">\n";
echo "        <title>Recent user activity at Quaddicted.com</title>\n";
echo "        <link href=\"https://www.quaddicted.com/reviews/recentactivity.php\" rel=\"self\" />\n";
echo "        <link href=\"https://www.quaddicted.com/reviews/\" />\n";
echo "        <id>tag:quaddicted.com,2013-10-04:recent-activity-feed:/</id>\n";
echo "        <updated>2003-12-13T18:30:02Z</updated>\n";

$dbq = new SQLite3('/srv/http/quaddicted.sqlite');
$results = $dbq->query('SELECT * FROM recentactivity ORDER BY timestamp DESC LIMIT 10');
while ($row = $results->fetchArray()) {

	echo "	<entry>\n";
		$time = round((($unixtime-htmlentities($row['timestamp']))/60),0);
		//echo "		<title type=\"html\">&lt;b&gt;".htmlentities($row['username'])."&lt;/b&gt; ".htmlentities($row['string'])." &lt;small&gt;".$time." minutes ago.&lt;/small&gt;</title>\n"; // is used by dokuwiki
		echo "		<title type=\"html\">A user ".htmlentities($row['string'])." &lt;small&gt;".$time." minutes ago.&lt;/small&gt;</title>\n"; // is used by dokuwiki
		//echo "		<summary type=\"html\">&lt;b&gt;".htmlentities($row['username'])."&lt;/b&gt; ".htmlentities($row['string'])." ".$time." minutes ago.</summary>\n";
	echo "	</entry>\n";
}

echo "</feed>";
ob_end_flush();
?>

