<?php
ob_start();
//error_reporting(E_ALL);
/*$time_start = microtime(true);*/
date_default_timezone_set('Europe/Berlin');

define('PUN_ROOT', '/srv/http/forum/');
include PUN_ROOT.'include/common.php';

include_once "markdown.php";
$parser = new Markdown_Parser; // or MarkdownExtra_Parser
$parser->no_markup = true; //disables the option for HTML in comments

require_once 'htmlpurifier/library/HTMLPurifier.auto.php';
$config = HTMLPurifier_Config::createDefault();
$purifier = new HTMLPurifier($config);


$html_header = <<<EOT
<!DOCTYPE html>
<html prefix="og: http://ogp.me/ns#" lang="en">
<head>
<meta charset="utf-8">
EOT;

$html_header2 = <<<EOT
<link rel="stylesheet" type="text/css" href="/static/style.css?20211120d" />
<link rel="stylesheet" type="text/css" href="/reviews/starrating.css" />
<script src="rating.js?20220301"></script>
<link rel="icon" href="/favicon.ico" type="image/x-icon" />
<link rel="shortcut icon" href="/favicon.ico" type="image/x-icon" />
<link href="/reviews/atom.php" type="application/atom+xml" rel="alternate" title="The latest Quake singleplayer releases at Quaddicted.com (Atom feed)" />
</head>
EOT;

echo $html_header;

if ($_GET['map']) {

	// check if the requested map string can be an actual map
	if (!preg_match('/^[a-z0-9-_\.!\+ \(\)]*$/', $_GET['map'])) {
		header('HTTP/1.0 404 Not Found');
		echo "<h1>Argh!</h1><p>This database entry points to a filename with uppercase characters or something like that. Spirit forgot again that only <code>[a-z0-9-_\.!\+ \(\)]</code> are supported. Post in https://www.quaddicted.com/forum/viewtopic.php?id=636 please, if no one did already.<br />-Spirit</p>";
		require("_footer.php");
		die();
	} else {
		$zipname = $_GET["map"];
	}

	$dbq = new PDO('sqlite:/srv/http/quaddicted.sqlite');
	$dbq->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);


	$preparedStatement = $dbq->prepare('SELECT * FROM maps WHERE zipname = :zipname');
	$preparedStatement->execute(array(':zipname' => $zipname));
	$result = $preparedStatement->fetch();

	if (!$result) {
		header('HTTP/1.0 404 Not Found');
		echo $zipname." is not in the database.";
		require("_footer.php");
		die();
	}

	// if tags were added, add them to the db
	if (isset($_POST['progress'])) {
		if($_POST['tags']) {
			if (!preg_match('/^[a-z0-9_\-, ]*$/', $_POST['tags'])) {
				echo "<h1>only a-z, 0-9, _ and - are allowed in tags. please go back and try again.</h1>";
				require("_footer.php");
				die();
			}

			if (!$pun_user['is_guest']) {
				$username = pun_htmlspecialchars($pun_user['username']);
			} else {
				echo "wouldn't you like an username with your tags?";
				die();
			}

			$tags = explode(",", ($_POST["tags"]));
			$zipname = $_POST["zipname"];
			$tag_count = 0;
			foreach ($tags as $tag) {
				#$tag = sqlite_escape_string($tag);
				$tag = trim($tag);
				if ($tag) { // do not enter empty tags
					$tag_count++;

					$stmt = $dbq->prepare("INSERT INTO tags (zipname,tag,username) VALUES (:zipname, :tag, :username)");
					$stmt->bindParam(':zipname', $zipname);
					$stmt->bindParam(':tag', $tag);
					$stmt->bindParam(':username', $username);
					$stmt->execute();
					} else { echo "dropped some empty tag(s)";}
			}
			$stmt->closeCursor();
			$stmt = $dbq->prepare("UPDATE users SET num_tags = num_tags + :tag_count WHERE username = :username");
			$stmt->bindParam(':username', $username);
			$stmt->bindParam(':tag_count', $tag_count);
			$stmt->execute();
			$stmt->closeCursor();

			//add to recent activity
			$recentactivity_text = "added the tag(s) \"".str_replace('  ', ' ', str_replace(',', ', ', $_POST['tags']))."\" to <a href=\"/reviews/".urlencode($zipname).".html\">".$zipname."</a>"; // TODO make this safe D:
			$stmt = $dbq->prepare("INSERT INTO recentactivity (username, string) VALUES (:username, :recentactivity_text)");
			$stmt->bindParam(':username', $username);
			$stmt->bindParam(':recentactivity_text', $recentactivity_text);
			$stmt->execute();
			$stmt->closeCursor();
		}
	}//end tag

	// if a file was uploaded, process it
	if($_FILES['uploadedfile'] && $_POST['demodetails']) {

		if (!$pun_user['is_guest']) {
			$username = pun_htmlspecialchars($pun_user['username']);
		} else {
			echo "only logged in users can upload demos, sorry";
			die();
		}

		/* Inspect and sanitize the POST text data */

		if ($zipname != $_POST['demodetails']['zipname']) { echo "wrong zipname?"; die(); }

		$bspname = $_POST['demodetails']['bspname'];
		if ($bspname === "complete") {
			$bspname = "complete";
		} elseif ($bspname) {
			$preparedStatement = $dbq->prepare('SELECT bsp FROM startmaps WHERE zipname = :zipname AND bsp = :bspname');
			$preparedStatement->execute(array(':zipname' => $zipname, ':bspname' => $bspname));
			$startmaps = $preparedStatement->fetch();
			if (!$startmaps) {echo "wrong bspname?"; die(); }
		} else {
			$bspname = $zipname; // release has one map inside and it is named the same as the zip
		}

		$skill = $_POST['demodetails']['skill'];
		if (!preg_match('/[0-3]/', $skill)) { echo "wrong skill?"; die(); }
		$protocol = $_POST['demodetails']['protocol'];
		if (!preg_match('/\d+/', $protocol)) { echo "wrong protocol?"; die(); }
		$date = $_POST['demodetails']['date']; //YYYY-MM-DD
		if (!preg_match('/\d{4}-\d{2}-\d{2}/', $date)) { echo "wrong date?"; die(); }

		$length = $_POST['demodetails']['length']; // (XXh)(XXm)(XXs)
		if (strlen($length) > 0 && !preg_match('/[0-9]+[d,m,s]+/', $length)) { $length = false; echo "wrong length?"; die(); } // '/(?:\d+h)(?:\d{2}m)(?:\d{2}s)/' does not work

		$description = $_POST['demodetails']['description']; // htmlspecialchar on output
		$videourl = $_POST['demodetails']['videourl'];
		if (strlen($videourl) > 0 && !preg_match('/http/', $videourl)) { echo "wrong videourl?"; die(); }

		// username is not safe to be written to a filename, "/../" can be used ;)
		$username = preg_replace('/[^\w-]/', '', $username);

		// let's create the filename
		// <zipfile>_<bspfile>_ <skill>_(complete)_ (*h*m*s)_<YYYY-MM-DD>_ <playername>_ (protocol).zip
		$demofilename ="";
		$demofilename .= $zipname;
		$demofilename .= "_".$bspname;
		switch ($skill) {
			case 0:
				$demofilename .= "_easy";
				break;
			case 1:
				$demofilename .= "_normal";
				break;
			case 2:
				$demofilename .= "_hard";
				break;
			case 3:
				$demofilename .= "_nightmare";
				break;
		}
		if ($length) { $demofilename .= "_".$length; }
		$demofilename .= "_".$date;
		$demofilename .= "_".$username;
		$demofilename .= "_".$protocol;
		// now just the extension is missing, it is added below

		/* Inspect the uploaded file */

		// the user's browser sends the mime type, so I cannot test for 'application/x-dzip'
		// so I allow application/octet-stream, at least my Opera sends that for a .dz file
		// this probably allows way too many things, but hey, it is user controlled anyways
		$mimetypes = array('application/zip', 'application/x-zip-compressed', 'application/x-7z-compressed', 'application/octet-stream');
		if (!in_array($_FILES['uploadedfile']['type'], $mimetypes)) {echo "'".$_FILES['uploadedfile']['type']."' mime type is not allowed!"; die(); }

		$extensions = array('zip', '7z', 'dz');
		if (!in_array(pathinfo($_FILES['uploadedfile']['name'])['extension'], $extensions)) {echo "extension not a .zip, .7z or .dz!"; die(); }

		// Inspect the file's magic bytes
		$filestrings = array('PK', '7z', 'DZ');
		$handle = fopen($_FILES['uploadedfile']['tmp_name'], "r");
		$filestring = fread($handle, 2);
		fclose($handle);
		if (!in_array($filestring, $filestrings)) {echo "bytes not PK, 7z or DZ!"; die(); }

		/* Move the uploaded file */
		$target_dir = "/srv/http/files/demos/";
		$demofilename .=  "." . pathinfo($_FILES['uploadedfile']['name'])['extension'];
		$target_path = $target_dir . $demofilename;

		if (file_exists($target_path)) { echo "File exists!"; die(); }

		if (move_uploaded_file($_FILES['uploadedfile']['tmp_name'], $target_path)) {
			echo "The file " . $demofilename ." has been added!";
		} else {
			echo "Could not move the file to /files/demos/ , tell Spirit.";
		}
		chmod($target_path, 0644); // should be set already by some php function but hey

		/* Add details to the database */
		$stmt = $dbq->prepare("INSERT INTO demos (zipname, bspname, username, skill, protocol, date, length, description, videourl, filename) VALUES (:zipname, :bspname, :username, :skill, :protocol, :date, :length, :description, :videourl, :filename)");
		$stmt->bindParam(':zipname', $zipname);
		$stmt->bindParam(':bspname', $bspname);
		$stmt->bindParam(':username', $username);
		$stmt->bindParam(':skill', $skill);
		$stmt->bindParam(':protocol', $protocol);
		$stmt->bindParam(':date', $date);
		$stmt->bindParam(':length', $length);
		$stmt->bindParam(':description', $description);
		$stmt->bindParam(':videourl', $videourl);
		$stmt->bindParam(':filename', $demofilename);
		$stmt->execute();
		$stmt->closeCursor();

		//add to recent activity
		$recentactivity_text = "added a 100% demo of <a href=\"/reviews/".urlencode($zipname).".html\">".$zipname."</a> on skill ".$skill;
		$stmt = $dbq->prepare("INSERT INTO recentactivity (username, string) VALUES (:username, :recentactivity_text)");
		$stmt->bindParam(':username', $comment_user);
		$stmt->bindParam(':recentactivity_text', $recentactivity_text);
		$stmt->execute();
		$stmt->closeCursor();

	} // end demo add

	$mapid = $result['id']; //praktisch
	$page_title = $result['zipname'].".zip - ".$result['title']." by ".$result['author']." in the Quake archive at Quaddicted.com";
	echo "<title>".$page_title."</title>\n";
	echo "<meta name=\"keywords\" content='quake, quake map, quake level, quake singleplayer, quake download, ".$result['zipname'].", ".$result['title'].", ".$result['author'],"' />\n";
	echo "<meta name=\"description\" content='Screenshot, description, tags, comments for the Quake map/mod ".$result['zipname'].".zip - ".$result['title']." by ".$result['author']."' />\n";
	echo "<meta property=\"og:type\" content=\"website\">\n";
	echo "<meta property=\"og:url\" content=\"https://www.quaddicted.com/reviews/".$zipname.".html\">\n";
	echo "<meta property=\"og:title\" content=\"".$page_title."\">\n";
	echo "<meta property=\"og:description\" content=\"".str_replace('"', '&quot;', $result['description'])."\">\n";
	echo "<meta property=\"og:image\" content=\"https://www.quaddicted.com/reviews/screenshots/".$zipname."_thumb.jpg\">\n";
	$trolled_users = array('');
        if (isset($pun_user['username']) && in_array($pun_user['username'], $trolled_users)) {
		echo '<style>body {font-family: Comic Sans !important; color: pink !important;}</style>';
        }
	echo $html_header2;

	//echo "<body style=\"background: url(/reviews/screenshots/".$zipname.".jpg) no-repeat center center fixed; -webkit-background-size: cover; -moz-background-size: cover; -o-background-size: cover; background-size: cover;\">";
	echo "<body>";

	require("_header.php");
	echo '<div id="content" class="review" itemscope itemtype="http://schema.org/CreativeWork">';
	$redirect_url = "/reviews/".urlencode($zipname).".html";
	include("userbar.php"); // include the top login bar, provides $loggedin = true/false

	$authorised_users = array('Spirit','negke','Drew','Icantthinkofanickname');
	if (isset($pun_user['username']) && in_array($pun_user['username'], $authorised_users)) {
		echo "<a href=\"/reviews/editor/edit.php?zipname=".$zipname."\">edit</a>\n"; // editor is also protected by a separate authentication
	}

echo "<div class=\"left\">";

	// display the screenshot only if we have it
	if (file_exists("/srv/http/reviews/screenshots/".$zipname.".jpg")) {
		echo "<a itemprop=\"image\" href=\"/reviews/screenshots/".$zipname.".jpg\"><img src=\"/reviews/screenshots/".$zipname."_thumb.jpg\" alt=\"Screenshot of ".$zipname."\" class=\"screenshot\" /></a>\n";
	}

	/* ===== START INFO TABLE =====*/

	echo "<table id=\"infos\">\n";
	echo "<tr class=\"light\"><td>Author:</td><td><a href='/reviews/?filtered=".$result['author']."' rel=\"nofollow\">".$result['author']."</a></td></tr>\n";
	echo "<tr class=\"dark\"><td>Title:</td><td>".$result['title']."</td></tr>\n";
	echo "<tr class=\"light\"><td>Download:</td><td><a href=\"/filebase/".$zipname.".zip\">".$zipname.".zip</a><small> (".$result['md5sum'].")</small></td></tr>\n";
	echo "<tr class=\"dark\"><td>Filesize:</td><td>".$result['size']." Kilobytes</td></tr>\n";
	echo "<tr class=\"light\"><td>Release date:</td><td>".$result['date']."</td></tr>\n";
	if ($result['url']) {
		echo "<tr class=\"dark\"><td>Homepage:</td><td><a href=\"".$result['url']."\">".$result['url']."</a></td></tr>\n";
	} else {
		echo "<tr class=\"dark\"><td>Homepage:</td><td></td></tr>\n";
	}
	echo "<tr class=\"light\"><td>Additional Links:</td><td>\n";

	$preparedStatement = $dbq->prepare('SELECT url,title FROM externallinks WHERE zipname = :zipname');
	$preparedStatement->execute(array(':zipname' => $zipname));
	$externallinks = $preparedStatement->fetchAll();

	if ($externallinks) {
		foreach ($externallinks as $externallink){
			echo "<a href=\"".htmlentities($externallink['url'])."\">".$externallink['title']."</a> &bull; ";
		}
	}
	echo "</td>";
	echo "</tr>\n";
	echo "<tr class=\"dark\"><td>Type:</td><td>";
		switch ($result['type']) {
			case 1:
				echo "Single BSP File(s)";
				break;
			case 2:
				echo "Partial conversion";
				break;
			case 3:
				echo "Total conversion";
				break;
			case 4:
				echo "Speedmapping";
				break;
			case 5:
				echo "Misc. Files";
				break;
			default:
				echo "undefined, please tell Spirit";
				break;
		}
	echo "</td></tr>\n";
	echo "<tr class=\"light\"><td colspan=\"2\">";

	if ($result['hasbsp']==="1") { echo "BSP: <img src=\"/static/tick.png\" class=\"ticks\" alt=\"&#x2714;\" /> • ";}
	else { echo "BSP: <img src=\"/static/cross.png\" class=\"ticks\" alt=\"&#x2718;\" /> • ";}
	if ($result['haspak']==="1") { echo "PAK: <img src=\"/static/tick.png\" class=\"ticks\" alt=\"&#x2714;\" /> • ";}
	else { echo "PAK: <img src=\"/static/cross.png\" class=\"ticks\" alt=\"&#x2718;\" /> • ";}
	if ($result['hasprogs']==="1") { echo "PROGS.DAT: <img src=\"/static/tick.png\" class=\"ticks\" alt=\"&#x2714;\" /> • ";}
	else { echo "PROGS.DAT: <img src=\"/static/cross.png\" class=\"ticks\" alt=\"&#x2718;\" /> • ";}
	if ($result['hascustomstuff']==="1") { echo "Custom Models/Sounds: <img src=\"/static/tick.png\" class=\"ticks\" alt=\"&#x2714;\" />";}
	else { echo "Custom Models/Sounds: <img src=\"/static/cross.png\" class=\"ticks\" alt=\"&#x2718;\" />";}
	echo "</td></tr>\n";

	$preparedStatement = $dbq->prepare('SELECT dependency FROM dependencies WHERE zipname = :zipname');
	$preparedStatement->execute(array(':zipname' => $zipname));
	$dependencies = $preparedStatement->fetchAll();

	if ($dependencies) {
		echo "<tr class=\"dark\"><td>Dependencies:</td>";
		echo "<td>";
		foreach ($dependencies as $dependency){
			echo "<a href=\"".$dependency['dependency'].".html\">".$dependency['dependency']."</a> &bull; ";
		}
		echo "</td></tr>\n";
	}
	echo "</table>\n";

	/* ===== END INFO TABLE =====*/


?>

<div id="demos">
<?php
$preparedStatement = $dbq->prepare('SELECT * FROM demos WHERE zipname = :zipname');
$preparedStatement->execute(array(':zipname' => $zipname));
$demos = $preparedStatement->fetchAll();

if ($demos) {
	?>
	<br /><br /><br />
	<table id="demolist">
	<caption>Walkthrough demos:</caption>
	<tr><th>Download</th><th>Skill</th><th>Length</th><th>Player</th><th>Protocol</th><th>Date</th></tr>
	<?php
	foreach ($demos as $demo){
		echo "<tr><td><a href=\"/files/demos/".$demo['filename']."\">".$demo['bspname']."</a></td><td>";

		switch ($demo['skill']) {
			case 0:
				echo "Easy";
				break;
			case 1:
				echo "Normal";
				break;
			case 2:
				echo "Hard";
				break;
			case 3:
				echo "Nightmare";
				break;
		}
		echo "</td><td>".$demo['length']."</td><td>".htmlspecialchars($demo['username'])."</td><td>".$demo['protocol']."</td><td>".$demo['date']."</td>";
		if ($demo['description']) {
			echo "<tr><td colspan=6>".htmlspecialchars($demo['description'])."</td>";
		}
	}
	echo "</table>";
}
?>

<?php if($loggedin == true) { ?>
<br /><br />
<h3>Upload your 100% walkthrough demo(s)</h3>
<form enctype="multipart/form-data" action="<?php echo urlencode($zipname); ?>.html" method="post">
<div id="demouploadform"><!-- validator? ... -->
<input type="hidden" name="MAX_FILE_SIZE" value="50000000" />
<input type="hidden" name="demodetails[zipname]" value="<?php echo $zipname; ?>" />

<?php
	// if the release has multiple maps, we need to know which one the demo is on
	$preparedStatement = $dbq->prepare('SELECT bsp FROM startmaps WHERE zipname = :zipname');
	$preparedStatement->execute(array(':zipname' => $zipname));
	$startmaps = $preparedStatement->fetchAll();
	if ($startmaps) {
		echo "<label>Map</label>: <select name=\"demodetails[bspname]\">";
		foreach ($startmaps as $startmap){
			echo "<option value=\"".$startmap['bsp']."\">".$startmap['bsp']."</option>\n";
		}
		echo "<option value=\"complete\">[All maps]</option>"; // Blank option for "one zip with all maps"?
		echo "</select>";
	}
?>
<br />

<label>Skill</label>: <select required name="demodetails[skill]">
<option value=""></option>
<option value="0">Easy</option>
<option value="1">Normal</option>
<option value="2">Hard</option>
<option value="3">Nightmare</option>
</select>
<div style="float:right;"><label><i>Length</i></label>: <input type="text" name="demodetails[length]" maxlength=9 size=10 placeholder="XhXXmXXs" /></div>
<br style="clear:both;" />

<label>Protocol</label>: <select required name="demodetails[protocol]">
<option value=""></option>
<option value="15">Standard (15)</option>
<option value="666">Fitz (666)</option>
<option value="999">RMQ (999)</option>
<option value="FTE999">FTE999</option>
<option value="10002">bjp (10002)</option>
</select>
<div style="float:right;"><label><i>Date</i></label>: <input type="text" name="demodetails[date]" maxlength=10 size=10 value="<?php echo date("Y-m-d"); ?>" /></div>
<br style="clear:both;" />

<label><i>Description</i></label>:<br /><input type="text" name="demodetails[description]" maxlength="80" size="40" value="" /><br />
<label><i>URL to captured video</i></label>:<br /><input type="text" name="demodetails[videourl]" size="40" value="" /><br />
<br />
Choose a file to upload: <input name="uploadedfile" type="file" /><br />
<input type="submit" value="Upload File" /> 50 Megabyte maximum, only zip, 7z, dz (lowercase!)
<br />You need to be logged in. <i>Italic items are optional.</i>
</div>
</form>
<?php } /* end of the IF loggedin*/ ?>
</div> <!-- demos -->

<?php

	// included files
	echo "<br /><details><summary>Files in the ZIP archive</summary><table id=\"includedfileslist\">\n<caption>Files in the ZIP archive:</caption>\n<tr>\n<th>File</th>\n<th>Size</th>\n<th>Date</th>\n</tr>";

	$preparedStatement = $dbq->prepare('SELECT size,date,filename FROM includedfiles WHERE zipname = :zipname');
	$preparedStatement->execute(array(':zipname' => $zipname));
	$includedfiles = $preparedStatement->fetchAll();

	if ($includedfiles) {
		foreach ($includedfiles as $includedfile){
			$filesize = ceil($includedfile['size']/1024);
			echo "<tr><td>".$includedfile['filename']."</td><td class=\"filesize\">".$filesize." KB</td><td>".$includedfile['date']."</td></tr>";
		}
	}
	echo "</table></details>";

echo "</div> <!--left-->";

echo "<div class=\"right\">";
	echo "<h3 class=\"toptitle\" itemprop=\"alternateName\">".$result['zipname'].".zip</h3>";
	echo "<h2 class=\"title\" itemprop=\"name\">".$result['title']."</h2>";
	echo "<span itemprop=\"description\">".$result['description']."</span>\n";

	/* Tags */
	$preparedStatement = $dbq->prepare('SELECT DISTINCT tag FROM tags WHERE zipname = :zipname');
	$preparedStatement->execute(array(':zipname' => $zipname));
	$tags = $preparedStatement->fetchAll();

	if ($tags) {
		echo "<br /><br /><strong>Tags: </strong>";
		foreach ($tags as $tag){
			$tagout = $tagout.", ".$tag[0];
		}
		echo trim($tagout," ,");
	}

	if ($loggedin) {
		echo "<form enctype=\"multipart/form-data\" method=\"post\" action=\"".urlencode($zipname).".html\"><div><input type=\"hidden\" name=\"progress\" value=\"1\" /><input type=\"hidden\" name=\"zipname\" value=\"".$zipname."\" />\n"; // zipname.html hat einen htaccess redirect auf details.php
		echo '<br />Add tags: <input type="text" name="tags" placeholder="comma, separated, please" />
		<input type="submit" value="Submit" /></div></form>';
		echo '<small><a href="/help/tagging_policy">Please do not add evaluative tags</a></small>';
	} /*else {
		echo "\n<br /><br />You could add tags if you logged in.";
	}*/

	if ($result['type'] != "4") {
		echo "<br /><br /><strong><a href=\"/help/maps#rating\">Editor's Rating</a>: ";
		switch ($result['rating']) {
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
		echo "</strong><br />\n";
	}

	/* user ratings */
	if($result['sum_ratings']){
			$rating = round($result['sum_ratings'] / $result['num_ratings'],1); // rounds to one decimal point
	}else{
		$rating = 0;
	}

	echo "<div itemprop=\"aggregateRating\" itemscope itemtype=\"http://schema.org/AggregateRating\"><strong>User Rating: </strong>";
	if ($loggedin) {
		echo "<ul class=\"star-rating\">\n<li class=\"current-rating\" id=\"current-rating\" style=\"width: ".($rating *25)."px\">Currently: ".$rating."/5 Stars.</li>\n";
		echo "<li><a href=\"javascript:rateImg(1,'".$zipname."')\" title=\"1 star out of 5\" class=\"one-star\">1</a></li>\n";
		echo "<li><a href=\"javascript:rateImg(2,'".$zipname."')\" title=\"2 stars out of 5\" class=\"two-stars\">2</a></li>\n";
		echo "<li><a href=\"javascript:rateImg(3,'".$zipname."')\" title=\"3 stars out of 5\" class=\"three-stars\">3</a></li>\n";
		echo "<li><a href=\"javascript:rateImg(4,'".$zipname."')\" title=\"4 stars out of 5\" class=\"four-stars\">4</a></li>\n";
    		echo "<li><a href=\"javascript:rateImg(5,'".$zipname."')\" title=\"5 stars out of 5\" class=\"five-stars\">5</a></li>\n";
		echo "</ul>\n";
	}

	echo "<span itemprop=\"ratingValue\">".$rating."</span>/<span itemprop=\"bestRating\">5</span> with <span itemprop=\"ratingCount\">".$result['num_ratings']."</span> ratings";

	// todo would be nicer like "if ($loggedin && ($row['username'] === $username))"
	if ($loggedin) {
              	$preparedStatement = $dbq->prepare('SELECT rating_value FROM ratings WHERE username = :username AND zipname = :zipname');
		$preparedStatement->execute(array(':zipname' => $zipname, ':username' => $username));
		$userrating = $preparedStatement->fetch();

		if ($userrating) {
			echo ", you gave it: <span class=\"userrating\">";
			for ($i=0;$i<$userrating['rating_value'];$i++){
				echo "&hearts;";
			}
			echo "</span>";
		}
	} else {
		echo "\n<br />You can NOT add ratings if you are not logged in.";
	}

	// histograms!
	echo "<br />";
        $preparedStatement = $dbq->prepare('SELECT rating_value, CAST(100.0*count()/(SELECT count(*) AS count FROM ratings WHERE zipname = :zipname)+0.5 AS int) AS percentage FROM ratings WHERE zipname = :zipname GROUP BY rating_value ORDER BY rating_value');
        $preparedStatement->execute(array(':zipname' => $zipname));
        $rating_frequencies = $preparedStatement->fetchAll();

	// oh god
	$ratings = [
	  "1" => 0,
	  "2" => 0,
	  "3" => 0,
	  "4" => 0,
	  "5" => 0,
	];

	foreach ($rating_frequencies as $row) {
	    $rating_value = (string) $row['rating_value'];
	    $percentage = $row['percentage'];
	    $ratings[$rating_value] = $percentage;
	}

	// oh shub
	$svg = '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="150" height="120" viewBox="0 0 150 120">';
	$svg .= '<rect x="10"  y="'.(100-$ratings["1"]).'" width="20" height="'.$ratings["1"].'" />';
	$svg .= '<rect x="40"  y="'.(100-$ratings["2"]).'" width="20" height="'.$ratings["2"].'" />';
	$svg .= '<rect x="70"  y="'.(100-$ratings["3"]).'" width="20" height="'.$ratings["3"].'" />';
	$svg .= '<rect x="100" y="'.(100-$ratings["4"]).'" width="20" height="'.$ratings["4"].'" />';
	$svg .= '<rect x="130" y="'.(100-$ratings["5"]).'" width="20" height="'.$ratings["5"].'" />';
	$svg .= '<text x="17" y="110">1</text>';
	$svg .= '<text x="47" y="110">2</text>';
	$svg .= '<text x="77" y="110">3</text>';
	$svg .= '<text x="107" y="110">4</text>';
	$svg .= '<text x="137" y="110">5</text>';
	$svg .= '</svg>';
	echo $svg;

	echo "</div>"; //ratings div for schema.org

	//comments
	$preparedStatement = $dbq->prepare('SELECT comment,comments.zipname,comments.timestamp,comments.username,registered,rating_value FROM comments 
					    LEFT OUTER JOIN ratings ON comments.username = ratings.username AND comments.zipname = ratings.zipname 
					    WHERE comments.zipname= :zipname ORDER BY comments.timestamp');
	$preparedStatement->execute(array(':zipname' => $zipname));
	$comments = $preparedStatement->fetchAll();
	echo "<div id=\"comments\"><!--<h2 class=\"title\">Comments</h2>-->\n";
	foreach ($comments as $row){
		if ($loggedin && ($row['username'] === $username)) {
			echo "<div class=\"comment_own\">";
		} else {
			echo "<div class=\"comment\">";
		}
		echo "<strong>".htmlspecialchars($row['username'])."</strong>";
		echo "<small>";
		if (preg_match('/^(negke|Spirit|Icantthinkofanickname)$/', $row['username']) && ($row['registered'] === "1" )) {
			echo "<span style=\"color:gold;\" title=\"Premium user\">★</span>";
		}


		if ($row['registered'] === "1" )
		{
			echo " Registered";
		} elseif ($row['registered'] === "0" ) {
			echo " Guest";
		}
		if ($row['rating_value']) { echo ", rated this a ".$row['rating_value'];}
		echo "</small> ";
		echo "<small class=\"commentdate\">".date("j F Y, G:i",$row['timestamp'])."</small>";

		if ($row['timestamp'] > 1363384265) { // markdown was installed afterwards
			$html = $parser->transform($row['comment']); // markdown
			$html = $purifier->purify($html); // html purifier
		} else {
			$html = nl2br(htmlspecialchars($row['comment'])); // before markdown
		}

		echo "<div class=\"commenttext\">".$html."</div>";
		echo "</div><!-- comment -->\n";

	}

	echo "<div id=\"commentform\">";
	echo "<h3>Post a Comment</h3><small>Your comment will be parsed with <a href=\"http://daringfireball.net/projects/markdown/dingus\">Markdown</a>!<br />Keep the comments on topic and do not post nonsense. <br />Did you read the file's readme?</small>";
	echo "<form method=\"post\" action=\"comment.php\"><div id=\"commentformdiv\">";
	echo "<input type=\"hidden\" name=\"zipname\" value=\"".$zipname."\" />";
	echo "<textarea name=\"comment_text\" cols=\"40\" rows=\"13\"></textarea><br />";
	echo "<div id=\"commentinputfloater\" style=\"text-align:right;\">"; //to align the inputs on the right
	if ($loggedin)
	{
		echo "<input type=\"hidden\" name=\"comment_user\" value=\"".htmlspecialchars($username)."\" />";
	} else {
		echo "Name <input type=\"text\" name=\"comment_user\" maxlength=\"40\" size=\"20\" />";
		echo "<br />Quake was released in <input type=\"text\" name=\"fhtagn\" maxlength=\"4\" size=\"4\" />";
	}

	echo "<br /><input type=\"submit\" name=\"Submit\" value=\"Submit\" /></div></div></form></div>";
//	if (!$loggedin) { echo "</div>"; } // commentinputfloater
	echo "</div>\n"; // commentform

	echo "</div> <!--right-->";
	echo "<div style=\"clear:both;\"></div>";

	$dbq = NULL;
}
else { echo "no map requested";}

/*
$time_end = microtime(true);
$time = $time_end - $time_start;
echo "Rendered in ".($time*1000)." ms\n";*/

require("_footer.php");
ob_end_flush();
?>
