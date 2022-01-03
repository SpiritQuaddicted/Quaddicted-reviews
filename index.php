<?php
ob_start();
//error_reporting(E_ALL);
//$time_start = microtime(true);

define('PUN_ROOT', '/srv/http/forum/');
include PUN_ROOT.'include/common.php';


$html_header = <<<EOT
<!DOCTYPE html>
<html>
  <head>
    <title>Quaddicted.com: Quake Singleplayer Maps</title>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="keywords" content="quake, quake maps, quake levels, quake singleplayer, quake downloads" />
    <meta name="description" content="This is the most comprehensive archive of singleplayer maps for Quake." />
    <link rel="stylesheet" type="text/css" href="/static/style.css?20211120d" />
    <link rel="icon" href="/favicon.ico" type="image/x-icon" />
    <link rel="shortcut icon" href="/favicon.ico" type="image/x-icon" />
    <link rel="alternate" type="application/rss+xml" title="Quaddicted.com - Quake Singleplayer Archive and News RSS Feed" href="/forum/extern.php?action=feed&amp;fid=5&amp;type=atom" />
    <link href="atom.php" type="application/atom+xml" rel="alternate" title="The latest Quake singleplayer releases at Quaddicted.com (Atom feed)" />
    <script src="/static/sorttable.js"></script>

<!-- table filter -->
<script>
   	function filter (phrase, _id){
		var words = phrase.value.toLowerCase().split(" ");
		var table = document.getElementById(_id);
		var ele;
		for (var r = 1; r < table.rows.length; r++){
			ele = table.rows[r].innerHTML.replace(/<[^>]+>/g,"");
		        var displayStyle = 'none';
		        for (var i = 0; i < words.length; i++) {
			    if (ele.toLowerCase().indexOf(words[i])>=0)
				displayStyle = '';
			    else {
				displayStyle = 'none';
				break;
			    }
		        }
			table.rows[r].style.display = displayStyle;
		}
	}
</script>
<!-- damn initial table filter -->
<script>
   	function filteri (phrase, _id){
		var words = phrase.toLowerCase().split(" ");
		var table = document.getElementById(_id);
		var ele;
		for (var r = 1; r < table.rows.length; r++){
			ele = table.rows[r].innerHTML.replace(/<[^>]+>/g,"");
		        var displayStyle = 'none';
		        for (var i = 0; i < words.length; i++) {
			    if (ele.toLowerCase().indexOf(words[i])>=0)
				displayStyle = '';
			    else {
				displayStyle = 'none';
				break;
			    }
		        }
			table.rows[r].style.display = displayStyle;
		}
	}
</script>
<!-- get parameters for the filter from the url -->
<script>
	function gup( name )
	{
		name = name.replace(/[\[]/,"\\\[").replace(/[\]]/,"\\\]");
		var regexS = "[\\?&]"+name+"=([^&#]*)";
		var regex = new RegExp( regexS );
		var results = regex.exec( window.location.href );
		if( results == null )
			return "";
		else
			return results[1];
	}


	function fillfilterfromurl() {
		var urlparams = gup( 'filtered' );
		if (urlparams){
			urlparams = decodeURIComponent(urlparams.replace(/\+/g, ' '));
			var fillurlparams = document.getElementById('filterinput');
			fillurlparams.value = urlparams;
			filteri(urlparams, 'spmaps', '1');
		}
	}
</script>

</head>
  <body onload="fillfilterfromurl()">
	<div id="widewrapperthisidisnotused">
		<div id="header">
			<a href="/"><img src="/static/top.png" alt="Quaddicted.com Logo" id="logo" /></a>
			<div id="quakeinjector"><img src="/static/injector64.png" alt="Quake Injector logo"/>Easily install and launch Quake maps with the cross-platform <a href="/tools/quake_injector">Quake Injector</a></div>
			<br />
			<br />
                        <span id="navlinks">
                                <a href="/">Frontpage</a>
				<a href="/forum/viewforum.php?id=5">News</a>
                                <a href="/reviews/">Maps</a>
                                <a href="/start?do=index">Wiki</a>
                                <a href="/forum/">Forum</a>
                                <a href="/help">Help</a>
                        </span>
		</div>

	<div id="content" style="width:99%;">
EOT;


echo $html_header;
//$time = microtime(true) - $time_start;
$redirect_url = "/reviews/";
$dbq = new PDO('sqlite:/srv/http/quaddicted.sqlite'); //userbar needs this
include("userbar.php"); // include the top login bar, provides $loggedin = true/false
$dbq = NULL; // the PDO is no longer needed, sqlite3 is used below

echo <<<EOT
	<h2>Welcome to the most comprehensive archive of singleplayer maps for Quake.</h2>
	Lighter rows are <a href="/help/installing_custom_content#installing_mods">mods</a>. Darker rows are speedmaps. <a href="/help/maps">Information on the map descriptions</a>. You are <a href="/archives/">encouraged to download everything</a>.
	<form>
    		<b>Instant Filter:</b> <input name="filtered" onkeyup="filter(this, 'spmaps', '1')" type="text" id="filterinput" size="50" /><noscript> (enable Javascript for this to work)</noscript>
	</form>
	<div style="float:right;"><a href="random_map.php">Play a random map!</a></div>
EOT;

$dbq = new SQLite3('/srv/http/quaddicted.sqlite');


if($loggedin === true && $_GET['myratings'] === "1")
{
	echo '<span>Showing maps you rated. <a href=".">Reset view</a>. <a href="?myratings=-1">Maps you did not rate</a>.</span>';
	$preparedStatement = $dbq->prepare('SELECT author,maps.zipname AS zipname,title,size,date,rating,num_comments,rating_value,type,tags FROM maps 
	JOIN (SELECT zipname,rating_value FROM ratings WHERE username = :username) AS ratings ON maps.zipname = ratings.zipname 
	LEFT OUTER JOIN ( SELECT zipname, GROUP_CONCAT(DISTINCT tag) AS tags FROM tags GROUP BY zipname) AS group_subselect ON group_subselect.zipname = maps.zipname ORDER BY maps.zipname;');
}
elseif($loggedin === true && $_GET['myratings'] === "-1")
{
	echo "<span>Showing maps you did not rate. <a href=\".\">Reset view</a>. <a href=\"?myratings=1\">Maps you rated</a>.</span>";
	$preparedStatement = $dbq->prepare('SELECT author,maps.zipname,title,size,date,rating,num_comments,num_ratings,sum_ratings,type,tags,
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
	LEFT OUTER JOIN ( SELECT zipname, GROUP_CONCAT(DISTINCT tag) AS tags FROM tags GROUP BY zipname) AS group_subselect ON group_subselect.zipname = maps.zipname WHERE maps.zipname 
	NOT IN (SELECT zipname FROM ratings WHERE username = :username) ORDER BY maps.zipname;');
}
else
{
	echo "<span><a href=\"?myratings=1\">Maps you rated</a>. <a href=\"?myratings=-1\">Maps you did not rate</a>.</span>";
	$query='SELECT author,maps.zipname,title,size,date,rating,num_comments,num_ratings,sum_ratings,type,tags,
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
	FROM maps LEFT OUTER JOIN ( SELECT zipname, GROUP_CONCAT(DISTINCT tag) AS tags FROM tags GROUP BY zipname) AS group_subselect ON group_subselect.zipname = maps.zipname ORDER BY maps.zipname;';
	$preparedStatement = $dbq->prepare($query);
}

$preparedStatement->bindValue(':username', $username);
$results = $preparedStatement->execute();


echo "<table class=\"sortable filelisting\" id=\"spmaps\">\n";
echo "<thead><tr><th><a>Author(s)</a><small>⇅</small></th>
	<th><a>Title</a><small>⇅</small></th>
	<th><a>Size</a><small>⇅</small></th>
	<th><a>Date DMY</a><small>⇅</small></th>
	<th><a>Rating</a><small>⇅</small></th>
	<th><a>Com#</a><small>⇅</small></th>";
if ($loggedin === true && $_GET['myratings'] === "1") {
	echo "<th><a>Yours</a><small>⇅</small></th>";
} else {
	echo "<th><a>Users'</a><small>⇅</small></th>";
}
echo "<th><a>Tags</a><small>⇅</small></th></tr></thead>\n";

echo "<tbody>\n";

/*
$time_end = microtime(true);
$time = $time_end - $time_start;
echo "<span><small>".(round(($time*1000),0))."ms before rendering the table</small></span>\n";
*/

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

//while ($row = $results->fetchArray()) {
foreach($results_fetched as $row) {
	//print_r($row);
	//echo "<hr />";
		if ($row['type'] === 2)
		{
			echo "<tr class=\"light\">";
		}
		elseif ($row['type'] === 4)
		{
			echo "<tr class=\"sm\">";
		}
		else
		{
			echo "<tr class=\"dark\">";
		}

                /*
$price = round($row['bayesian_rating']/2,2);
                echo "<td><a style='color: red !important;' href='http://steamcommunity.com/games/SteamWorkshop/announcements/detail/208632365237576574'>";
                if ($price < 0.01) {
			echo "FREE!";
                } else {
                        echo "$ ".sprintf('%0.2f', $price);
                }
                echo "</a></td>";
*/

		echo "<td class=\"author\"><a href='/reviews/?filtered=".urlencode($row['author'])."' rel=\"nofollow\">".$row['author']."</a></td><td class=\"title\"><a href=\"".urlencode($row['zipname']).".html\">".$row['zipname'].".zip - ".$row['title']."</a></td>";
		echo "<td class=\"size\" style=\"white-space: nowrap;\"><a href=\"/filebase/".$row['zipname'].".zip\">".$row['size']." KB</a></td>";
		echo "<td style=\"text-align:center;\">".$row['date']."</td><td class=\"ratingtd\" sorttable_customkey=\"".$row['rating']."\">";

		/*switch ($row['rating']) {
		    case 1:
		        echo "Crap";
		        break;
		    case 2:
		        echo "Poor";
		        break;
		    case 3:
		        echo "Average";
		        break;
		    case 4:
		        echo "Nice";
		        break;
		    case 5:
		        echo "Excellent";
		        break;
		    default:
		        echo "no rating (yet)";
		        break;
		}*/

		for ($i=0;$i<$row['rating'];$i++){
			echo "&hearts;";
		}

		echo "</td><td style=\"text-align:center;\">";
		if($row['num_comments']){
			echo "<a href=\"".$row['zipname'].".html#comments\">".$row['num_comments']."</a>";
		}
		echo "</td>";

		// user ratings
		$rating = NULL;
		echo "<td class=\"userrating\">";
		if($row['bayesian_rating']){
			$row['bayesian_rating'] = normalize($row['bayesian_rating'], $a, $b);
			$rating = round($row['bayesian_rating'],2)." (".$row['num_ratings'].")";
		} elseif ($_GET['myratings'] === "1" && $row['rating_value']) {
			for ($i=0;$i<$row['rating_value'];$i++){
				$rating .= "&hearts;";
			}
		} else {
			if($row['num_ratings'] > 0) {
				$userrating = round($row['sum_ratings'] / $row['num_ratings'],1);
				for ($i=0;$i<$userrating;$i++){
					$rating .= "&hearts;";
				}
			}
		}
		echo $rating;
		echo "</td><td class=\"tags\">";
		if ($row['tags'])
			echo trim($row['tags'], ","); //tags were concatted in sql, so just print them
		echo "</td></tr>\n";
}

$dbq = NULL;

echo '</tbody></table><p>To get a plain directory listing of all the files go to <a href="/filebase/">filebase/</a>.</p>';

echo $html_footer;
require("_footer.php");
/*$time_end = microtime(true);
$time = $time_end - $time_start;
echo (round(($time*1000),0));*/

ob_end_flush();
?>
