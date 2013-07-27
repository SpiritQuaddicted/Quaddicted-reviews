<?php
ob_start();
//error_reporting(E_ALL);
$time_start = microtime(true);

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
    <link rel="stylesheet" type="text/css" href="/static/style.css" />
    <link rel="icon" href="/favicon.ico" type="image/x-icon" />
    <link rel="shortcut icon" href="/favicon.ico" type="image/x-icon" />
    <link rel="alternate" type="application/rss+xml" title="Quaddicted.com - Quake Singleplayer Archive and News RSS Feed" href="/?feed=rss2" />
    <link href="atom.php" type="application/atom+xml" rel="alternate" title="The latest Quake singleplayer releases at Quaddicted.com (Atom feed)" />
    <script src="/static/sorttable.js" type="text/javascript"></script>

<!-- table filter -->
<script type="text/javascript">
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
<script type="text/javascript">
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
<script type="text/javascript">

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
			urlparams = urlparams.replace(/\+/, " ");
			var fillurlparams = document.getElementById('filterinput');
			fillurlparams.value = urlparams;
			filteri(urlparams, 'spmaps', '1');
		}
		else
		{
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
                                <a href="/archives/">Archives</a>
				<a href="/articles/">Articles</a>
                                <a href="/interviews/">Interviews</a>
                                <a href="/start?do=index">Sitemap</a>
                                <a href="/forum/">Forum</a>
                                <a href="/help">Help</a>
                        </span>
		</div>
	
	<div id="content" style="width:99%;">
EOT;


$html_footer = <<<EOT
</table>
<p>To get a plain directory listing of all the files go to <a href="/filebase/">filebase/</a>.</p>
</div>
    </div>
  </body>
</html>
EOT;

echo $html_header;
//$time = microtime(true) - $time_start;
$redirect_url = "/reviews/";
$dbq = new PDO('sqlite:/srv/http/quaddicted.sqlite'); //userbar needs this
include("userbar.php"); // include the top login bar, provides $loggedin = true/false
$dbq = NULL; // the PDO is no longer needed, sqlite3 is used below

echo <<<EOT
	<h2>This is the most comprehensive archive of singleplayer maps for Quake.</h2>
	Lighter rows are <a href="/help/installing_custom_content#installing_mods">mods</a>. Darker rows are speedmaps. <a href="/help/maps">Information on the map descriptions</a>.
<p>	<form>
    		<b>Filter:</b> <input name="filtered" onkeyup="filter(this, 'spmaps', '1')" type="text" id="filterinput" /><noscript> (needs Javascript enabled)</noscript>
	</form>
</p>
	<div style="float:right;"><a href="random_map.php">Play a random map!</a></div>
EOT;

$dbq = new SQLite3('/srv/http/quaddicted.sqlite');


if($loggedin == true && $_GET['myratings'] == 1)
{
	echo "<span>Showing maps you rated (shown in the \"User's\" column). <a href=\"/reviews/\">Reset view</a>. <a href=\"/reviews/?myratings=-1\">Maps you did not rate</a>.</span>";
	$preparedStatement = $dbq->prepare('SELECT author,maps.zipname AS zipname,title,size,date,rating,num_comments,rating_value,type,tags FROM maps JOIN (SELECT zipname,rating_value FROM ratings WHERE username = :username) AS ratings ON maps.zipname = ratings.zipname LEFT OUTER JOIN ( SELECT zipname, GROUP_CONCAT(DISTINCT tag) AS tags FROM tags GROUP BY zipname) AS group_subselect ON group_subselect.zipname = maps.zipname ORDER BY maps.zipname;');
}
elseif($loggedin == true && $_GET['myratings'] == -1)
{
	echo "<span>Showing maps you did not rate. <a href=\"/reviews/\">Reset view</a>. <a href=\"/reviews/?myratings=1\">Maps you rated</a>.</span>";
	$preparedStatement = $dbq->prepare('SELECT author,maps.zipname,title,size,date,rating,num_comments,num_ratings,sum_ratings,type,tags FROM maps LEFT OUTER JOIN ( SELECT zipname, GROUP_CONCAT(DISTINCT tag) AS tags FROM tags GROUP BY zipname) AS group_subselect ON group_subselect.zipname = maps.zipname WHERE maps.zipname NOT IN (SELECT zipname FROM ratings WHERE username = :username) ORDER BY maps.zipname;');
}
elseif($loggedin == true)
{
	echo "<span><a href=\"/reviews/?myratings=1\">Maps you rated</a>. <a href=\"/reviews/?myratings=-1\">Maps you did not rate</a>.</span>";
	$preparedStatement = $dbq->prepare('SELECT author,maps.zipname,title,size,date,rating,num_comments,num_ratings,sum_ratings,type,tags FROM maps LEFT OUTER JOIN ( SELECT zipname, GROUP_CONCAT(DISTINCT tag) AS tags FROM tags GROUP BY zipname) AS group_subselect ON group_subselect.zipname = maps.zipname ORDER BY maps.zipname'); // ORDER BY sum_ratings/num_ratings DESC'); // standard! "von iSteve, um nur maps mit tags zu zeigen: JOIN, um alle und mit tags LEFT OUTER JOIN" // ORDER BY random()
}
else
{
	// same query as above!
//	$preparedStatement = $dbq->prepare('SELECT author,maps.zipname,title,size,date,rating,num_comments,num_ratings,sum_ratings,type,tags FROM maps LEFT OUTER JOIN ( SELECT zipname, GROUP_CONCAT(DISTINCT tag) AS tags FROM tags GROUP BY zipname) AS group_subselect ON group_subselect.zipname = maps.zipname ORDER BY maps.zipname'); // ORDER BY sum_ratings/num_ratings DESC'); // standard! "von iSteve, um nur maps mit tags zu zeigen: JOIN, um alle und mit tags LEFT OUTER JOIN" // ORDER BY random()
//	$preparedStatement = $dbq->prepare('SELECT author,maps.zipname,title,size,date,rating,num_comments,num_ratings,sum_ratings,type,tags,((SELECT avg(num_ratings) FROM maps WHERE num_ratings !="")*(SELECT (CAST (sum(sum_ratings) AS real)/sum(num_ratings)) FROM maps WHERE num_ratings !="") + num_ratings*(SELECT (CAST (sum(sum_ratings) AS real)/sum(num_ratings)))  ) / ((SELECT avg(num_ratings) FROM maps WHERE num_ratings !="") + num_ratings) AS bayesian_rating FROM maps LEFT OUTER JOIN ( SELECT zipname, GROUP_CONCAT(DISTINCT tag) AS tags FROM tags GROUP BY zipname) AS group_subselect ON group_subselect.zipname = maps.zipname ORDER BY maps.zipname;');
	//$query="select * from maps;";
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
echo "<tr><th><a>Author</a><small>⇅</small></th><th><a>Title</a><small>⇅</small></th><th><a>Size</a><small>⇅</small></th><th><a>Date DMY</a><small>⇅</small></th><th><a>Rating</a><small>⇅</small></th><th><a>Com#</a><small>⇅</small></th><th><a>User's</a><small>⇅</small></th><th><a>Tags</a><small>⇅</small></th></tr>\n";


/*
$time_end = microtime(true);
$time = $time_end - $time_start;
echo "<span><small>".(round(($time*1000),0))."ms before rendering the table</small></span>\n";
*/

while ($row = $results->fetchArray()) {
//while ($row = $results->fetchArray()) {
	//print_r($row);
	//echo "<hr />";
		if ($row['type'] == 2)
		{
			echo "<tr class=\"light\">";
		}
		elseif ($row['type'] == 4)
		{
			echo "<tr class=\"sm\">";
		}
		else
		{
			echo "<tr class=\"dark\">";
		}
		
		echo "<td class=\"author\">".$row['author']."</td><td class=\"title\"><a href=\"".$row['zipname'].".html\">".$row['zipname'].".zip - ".$row['title']."</a></td>";
		echo "<td class=\"size\"><a href=\"/filebase/".$row['zipname'].".zip\">".$row['size']." KB</a></td>";
		echo "<td>".$row['date']."</td><td class=\"ratingtd\" sorttable_customkey=\"".$row['rating']."\">";
		
		switch ($row['rating']) {
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
		}
/*
		for ($i=0;$i<$row['rating'];$i++){
			echo "&hearts;";
		}
*/
		echo "</td><td>";
		if($row['num_comments']){
			echo "<a href=\"".$row['zipname'].".html#comments\">".$row['num_comments']."</a>";
			//echo $row['num_comments'];
		}
		echo "</td>";
		
		// user ratings
		if($row['bayesian_rating']){
			echo "<td class=\"userrating\" sorttable_customkey=\"".round($row['bayesian_rating'],2)."\">";
/*			for ($i=0;$i<$row['bayesian_rating'];$i++){
				echo "&hearts;"; //♥
			}
*/
			echo round($row['bayesian_rating'],2)." (".$row['num_ratings'].")";
		} else {
			echo "<td class=\"userrating\">";
		}
		
		// user ratings
		if($_GET['myratings'] == 1 && $row['rating_value']){
			//echo $rating;
			for ($i=0;$i<$row['rating_value'];$i++){
				echo "&hearts;"; //♥
				//echo "&#9733;"; //★
			}
		}
		
		echo "</td><td class=\"tags\">";
		if ($row['tags']){echo $row['tags'];}; //tags wurden ja gejoint
		echo "</td></tr>\n";
}

echo $row['avg_rating'];
echo $row['sum_sum_ratings'];
echo $row['sum_num_ratings'];

$dbq = NULL;
echo $html_footer;

$time_end = microtime(true);
$time = $time_end - $time_start;
echo (round(($time*1000),0));


ob_end_flush();
?>
