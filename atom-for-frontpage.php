<?php
header('Content-Type: application/atom+xml; charset=utf-8');
ob_start();
echo "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n";

echo "<feed xmlns=\"http://www.w3.org/2005/Atom\">\n";
echo "        <title>The latest Quake singleplayer releases at Quaddicted.com</title>\n";
echo "        <link href=\"https://www.quaddicted.com/reviews/atom.php\" rel=\"self\" />\n";
echo "        <link href=\"https://www.quaddicted.com/reviews/\" />\n";
echo "        <id>tag:quaddicted.com,".date(\DateTime::ATOM).":latest-spmaps-feed:/</id>\n";
echo "        <updated>".date(\DateTime::ATOM)."</updated>\n";

$dbq = new SQLite3('/srv/http/quaddicted.sqlite');
$results = $dbq->query('SELECT id,timestamp,author,zipname,title,description FROM maps ORDER BY id DESC LIMIT 10');
while ($row = $results->fetchArray()) {

	echo "	<entry>\n";
		echo "		<title type=\"html\">".htmlentities($row['title'], ENT_XML1)." by ".htmlentities($row['author'], ENT_XML1)."</title>\n";
		echo "		<link href=\"https://www.quaddicted.com/reviews/".$row['zipname'].".html\" />\n";
		echo "		<id>tag:quaddicted.com,".date('Y-m-d', $row['timestamp']).":spmaps-mapid-".$row['id']."</id>\n";
		echo "		<summary type=\"html\">".htmlentities($row['description']."<img src=\"https://www.quaddicted.com/reviews/screenshots/".$row['zipname'].".jpg\" />", ENT_XML1)."</summary>\n";
		echo "		<updated>".date(\DateTime::ATOM, $row['timestamp'])."</updated>\n";  // changed from issued, 20180326
	echo "	</entry>\n";
}

echo "</feed>";
ob_end_flush();
?>
