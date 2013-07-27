<?php
ob_start();
//error_reporting(E_ALL);
//$time_start = microtime(true);

// TODO
// date is currently formatted dd.mm.yyyy, the QI expects dd.mm.yy
// patch QI before its next release, then remove the tinkering in here  

$db = new SQLite3('/srv/http/quaddicted.sqlite');

$results = $db->query('SELECT * FROM maps 
LEFT OUTER JOIN (SELECT zipname, GROUP_CONCAT(bsp) AS startmaps FROM startmaps GROUP BY zipname) 
AS group_subselectbsp ON group_subselectbsp.zipname = maps.zipname 
LEFT OUTER JOIN (SELECT zipname, GROUP_CONCAT(dependency) AS dependencies FROM dependencies GROUP BY zipname) 
AS group_subselectdep ON group_subselectdep.zipname = maps.zipname WHERE maps.type!=4 ORDER BY maps.zipname'); // currently excluding the speedmaps with that WHERE, TODO add them once at least the data was added for them

echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<files>\n";
while ($row = $results->fetchArray()) {
	//print_r($row);
	//echo "<hr />";
	echo "<file id=\"".$row[2]."\" type=\"".$row['type']."\" "; // $row[2] is a hack, no idea why i cannot use 'zipname' :(
	
	// not currently used by the QI
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
	
		echo "\t<author>".$row['author']."</author>\n"; // if there are errors with the xml parsing, probably some editor forgot to encode & to &amp;
		echo "\t<title>".$row['title']."</title>\n";
		echo "\t<md5sum>".$row['md5sum']."</md5sum>\n";
		echo "\t<size>".$row['size']."</size>\n";
		echo "\t<date>".substr($row['date'],0,6).substr($row['date'],8,9)."</date>\n"; // FIXME, see TODO
		echo "\t<description><![CDATA[".$row['description']."]]></description>\n";
		echo "\t<techinfo>\n";
			// TODO if techinfo would be empty, it should be excluded
			// TODO why don't i use isset() below?
			if ($row['zipbasedir'] != "") { echo "\t\t<zipbasedir>".$row['zipbasedir']."</zipbasedir>\n"; } //TODO why can this lead to empty entries?
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
