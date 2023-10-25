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
echo "        <id>tag:quaddicted.com,".date(\DateTime::ATOM).":recent-activity-feed:/</id>\n";
echo "        <updated>".date(\DateTime::ATOM)."</updated>\n";

$dbq = new SQLite3('/srv/http/quaddicted.sqlite');
$results = $dbq->query('SELECT id,timestamp,username,zipname,string FROM recentactivity ORDER BY timestamp DESC LIMIT 10');
while ($row = $results->fetchArray()) {
  echo "  <entry>\n";
  $time = round((($unixtime-htmlentities($row['timestamp']))/60),0);
  echo "<title type=\"html\">A user ".htmlentities($row['string'], ENT_XML1)." ".$time." minutes ago.</title>\n"; // is used by dokuwiki // ENT_XML1 so things like &ldquo; is output as numeric entity rather, that is allowed, the name isnt
  echo "<link rel=\"alternate\" href=\"https://www.quaddicted.com/reviews/".$row['zipname'].".html\"/>\n";
  echo "<id>tag:quaddicted.com,".date('Y-m-d', $row['timestamp']).":recent-activity-feed-".$row['id']."</id>\n";
  echo "<updated>".date(\DateTime::ATOM, $row['timestamp'])."</updated>\n";  // changed from issued, 20180326
  echo "  </entry>\n";
}

echo "</feed>";
ob_end_flush();
?>

