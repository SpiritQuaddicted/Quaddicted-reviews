<?php
ob_start();
//error_reporting(E_ALL);
//$time_start = microtime(true);

$db = new SQLite3('/srv/http/quaddicted.sqlite');

$results = $db->query('SELECT
maps.zipname,type,rating,author,title,md5sum,size,date,description,zipbasedir,commandline,startmaps,dependencies,tags,
(
	(SELECT avg(num_ratings) FROM maps WHERE num_ratings !="")
	*
	(SELECT
		(
			CAST(sum(sum_ratings) AS real)/sum(num_ratings)
		) FROM maps WHERE num_ratings !=""
	)
	+
	num_ratings
	*
	(
		CAST((sum_ratings) AS real) / (num_ratings)
	)
)
/
(
	(SELECT avg(num_ratings) FROM maps WHERE num_ratings !="")
	+
	num_ratings
)
AS bayesian_rating
FROM maps
LEFT OUTER JOIN (SELECT zipname, GROUP_CONCAT(bsp) AS startmaps FROM startmaps GROUP BY zipname)
AS group_subselectbsp ON group_subselectbsp.zipname = maps.zipname
LEFT OUTER JOIN (SELECT zipname, GROUP_CONCAT(dependency) AS dependencies FROM dependencies GROUP BY zipname)
AS group_subselectdep ON group_subselectdep.zipname = maps.zipname
LEFT OUTER JOIN ( SELECT zipname, GROUP_CONCAT(DISTINCT tag) AS tags FROM tags GROUP BY zipname)
AS group_subselecttags ON group_subselecttags.zipname = maps.zipname
ORDER BY maps.zipname
;');

// from https://stackoverflow.com/questions/24754506/need-to-fetch-all-results-from-sqlite3result-to-multiarray
function resultSetToArray($queryResultSet){
    $multiArray = array();
    $count = 0;
    while($row = $queryResultSet->fetchArray(SQLITE3_ASSOC)){
        foreach($row as $i=>$value) {
            $multiArray[$count][$i] = $value;
        }
        $count++;
    }
    return $multiArray;
}

// i want all results so i can get a min and max from the rating thingie
$results_fetched = resultSetToArray($results);

$min = 5;
$max = 0;
foreach($results_fetched as $row) {
  if ($row['bayesian_rating'] > $max) {
    $max = $row['bayesian_rating'];
  }
  if (!empty($row['bayesian_rating']) && $row['bayesian_rating'] < $min) {
    $min = $row['bayesian_rating'];
  }
}

// via https://stats.stackexchange.com/questions/70801/how-to-normalize-data-to-0-1-range
$new_max = 5;
$new_min = 1;  // one heart...
$a = ($new_max-$new_min)/($max-$min);
$b = $new_max - $a * $max;

function normalize($value, $a, $b) {
        $normalized = $a * $value + $b;
        return $normalized;
}


echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<files>\n";
while ($row = $results->fetchArray()) {
	//print_r($row);
	//echo "<hr />";
	echo "<file id=\"".$row['zipname']."\" type=\"".$row['type']."\" ";

	echo "rating=\"".$row['rating']."\"";
        if($row['bayesian_rating']){
	        $row['bayesian_rating'] = normalize($row['bayesian_rating'], $a, $b);
                $normalized_users_rating = round($row['bayesian_rating'],2);
		echo " normalized_users_rating=\"".$normalized_users_rating."\"";
	}
	echo ">\n";

		echo "\t<author>".$row['author']."</author>\n"; // if there are errors with the xml parsing, probably some editor forgot to encode & to &amp;
		echo "\t<title>".$row['title']."</title>\n";
		echo "\t<md5sum>".$row['md5sum']."</md5sum>\n";
		echo "\t<size>".$row['size']."</size>\n";
		echo "\t<date>".$row['date']."</date>\n";
		echo "\t<description><![CDATA[".$row['description']."]]></description>\n";
                        if (isset($row['tags'])) {
                                echo "\t<tags>\n";
                                $tags = explode(",", $row['tags']);
                                foreach($tags as $tag) {
                                        echo "\t\t<tag>".$tag."</tag>\n";
                                }
                                echo "\t</tags>\n";
                        }

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
