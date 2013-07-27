<?php
ob_start();
//error_reporting(E_ALL);
//$time_start = microtime(true);

// TODO
// date ist hier dd.mm.yyyy, der injector will aber dd.mm.yy. injector vor nächstem release patchen, solange wird hier dd.mm.yy draus gemacht.  

$db = new SQLite3('/srv/http/quaddicted.sqlite');

$results = $db->query('SELECT * FROM maps 
LEFT OUTER JOIN (SELECT zipname, GROUP_CONCAT(bsp) AS startmaps FROM startmaps GROUP BY zipname) 
AS group_subselectbsp ON group_subselectbsp.zipname = maps.zipname 
LEFT OUTER JOIN (SELECT zipname, GROUP_CONCAT(dependency) AS dependencies FROM dependencies GROUP BY zipname) 
AS group_subselectdep ON group_subselectdep.zipname = maps.zipname WHERE maps.type!=4 ORDER BY maps.zipname'); // currently excluding the speedmaps with that WHERE, TODO remove it once at least the techinfo was added for them

echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<files>\n";
while ($row = $results->fetchArray()) {
	//print_r($row);
	//echo "<hr />";
	echo "<file id=\"".$row[2]."\" type=\"".$row['type']."\" "; // $row[2] ist ein hack, ich weiss nicht wo 'zipname' hin ist. :(
	
	// benutzt der injector doch garnicht!
	/*
	if ($row['hasbsp']==="1") { echo "hasbsp=\"1\" ";}
	else { echo "hasbsp=\"0\" ";}
	if ($row['haspak']==="1") { echo "haspak=\"1\" ";}
	else { echo "haspak=\"0\" ";}
	if ($row['hasprogs']==="1") { echo "hasprogs=\"1\" ";}
	else { echo "hasprogs=\"0\" ";}
	if ($row['hascustomstuff']==="1") { echo "hascustomstuff=\"1\" ";}
	else { echo "hascustomstuff=\"0\" ";}
	*/
	echo "rating=\"".$row['rating']."\">\n";
	
		echo "\t<author>".$row['author']."</author>\n"; // falls fehler beim xml parsen kommen hat wahrscheinlich jemand umlaute als html entities benutzt, das soll nicht sein
		echo "\t<title>".$row['title']."</title>\n";
		echo "\t<md5sum>".$row['md5sum']."</md5sum>\n";
		echo "\t<size>".$row['size']."</size>\n";
		echo "\t<date>".substr($row['date'],0,6).substr($row['date'],8,9)."</date>\n"; // FIXME, siehe TODO oben
		echo "\t<description><![CDATA[".$row['description']."]]></description>\n";
		echo "\t<techinfo>\n";
			// eigentlich mit isset() checken oder? sind die etwa nicht NULL in der db?!
			// leere techinfo sollte auch nicht rein ;) oder sind das eh nur die speedmaps bisher? die bekommen ja zumindest startmaps
			if ($row['zipbasedir'] != "") { echo "\t\t<zipbasedir>".$row['zipbasedir']."</zipbasedir>\n"; } //TODO wieso gibt das auch leere tags?
			if ($row['commandline'] != "") { echo "\t\t<commandline>".$row['commandline']."</commandline>\n"; }
			if ($row['startmaps'] != "") {
				$startmaps = explode(",", $row['startmaps']);
				foreach($startmaps as $startmap) {
					echo "\t\t<startmap>".$startmap."</startmap>\n";
       				}
			}
			if (isset($row['dependencies'])) {
				echo "\t\t<requirements>\n";
				$dependencies = explode(",", $row['dependencies']);
				foreach($dependencies as $dependency) {
					echo "\t\t\t<file id=\"".$dependency."\" />\n";
       				}
				echo "\t\t</requirements>\n";
			}
		echo "\t</techinfo>\n";
	echo "</file>\n";
}
	
$db->close();
unset($db); // unset database connection

echo "</files>";
//$time_end = microtime(true);
//$time = $time_end - $time_start;
//echo "Rendered in ".($time*1000)." ms\n";
ob_end_flush();
?>
