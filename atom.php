<?php 
header('Content-Type: application/atom+xml; charset=utf-8');
ob_start();
echo "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n";
 
echo "<feed xmlns=\"http://www.w3.org/2005/Atom\">\n";
echo "        <title>The latest Quake singleplayer releases at Quaddicted.com</title>\n";
echo '        <link href="https://www.quaddicted.com/reviews/atom.php" rel="self" />'."\n";
echo '        <link href="https://www.quaddicted.com/reviews/" />'."\n";
echo "        <id>tag:quaddicted.com,2011-07-16:latest-spmaps-feed:/</id>\n";
echo "        <updated>2003-12-13T18:30:02Z</updated>\n";

$dbq = new SQLite3('/srv/http/quaddicted.sqlite');
$results = $dbq->query('SELECT id,author,zipname,title,description FROM maps ORDER BY id DESC LIMIT 10');
while ($row = $results->fetchArray()) {

	echo "	<entry>\n";
		echo "		<title type=\"html\">".$row['zipname'].".zip - ".htmlentities($row['title'])." by ".htmlentities($row['author'])."</title>\n";
		echo "		<link href=\"https://www.quaddicted.com/reviews/".$row['zipname'].".html\" />\n";
		echo "		<id>tag:quaddicted.com,2011-01-01:spmaps-mapid-".$row['id']."</id>\n";
		echo "		<summary type=\"html\">".htmlentities($row['description']."<img src=\"https://www.quaddicted.com/reviews/screenshots/".$row['zipname'].".jpg\" />")."</summary>\n";
	echo "	</entry>\n";
}
 
echo "</feed>";
ob_end_flush();
?>
