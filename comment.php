<?php
if($_POST['zipname'] && $_POST['comment_text'] && $_POST['comment_user']){
	define('PUN_ROOT', '/srv/http/forum/');
	include PUN_ROOT.'include/common.php';

	$zipname = $_POST['zipname']; // no need to escape as i use a prepared statement?
	$comment_text = $_POST['comment_text'];
	$comment_user = $_POST['comment_user'];
	$dbq = new PDO('sqlite:/srv/http/quaddicted.sqlite');
	$dbq->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
	//echo "Username: ".$comment_user." - Mapid: ".$mapid." - comment_text: ".$comment_text."<br />\n";

	$spamprotection = $_POST['fhtagn'];
	if ($pun_user['is_guest'] && ($spamprotection != "1996")) {
		echo "Comment was not added because you failed the spam protection question. \nLet me help you: Quake was released in 1996... \nIf you use a nice browser like Opera you can just go back and your text will be there.";
		die();
	}

	if(preg_match('/^[a-z0-9-_\.!\+ \(\)]*$/', $zipname)) {
		if (pun_htmlspecialchars($pun_user['username']) === $comment_user) { $registered="1"; } else { $registered="0"; }

		//store comment
		$stmt = $dbq->prepare("INSERT INTO comments (zipname, username, registered, comment) VALUES (:zipname, :username, :registered, :comment_text)");
		$stmt->bindParam(':zipname', $zipname);
		$stmt->bindParam(':username', $comment_user);
		$stmt->bindParam(':registered', $registered);
		$stmt->bindParam(':comment_text', $comment_text);
		$stmt->execute();
		$stmt->closeCursor();

		// if logged in
		if (pun_htmlspecialchars($pun_user['username']) === $comment_user) {
			//update user's comment count
			$stmt = $dbq->prepare("UPDATE users SET num_comments = num_comments + 1 WHERE username = :comment_user");
			$stmt->bindParam(':comment_user', $comment_user);
			$stmt->execute();
		$stmt->closeCursor();
		}

		//update map's comment count
		$stmt = $dbq->prepare("UPDATE maps SET num_comments = num_comments + 1 WHERE zipname = :zipname");
		$stmt->bindParam(':zipname', $zipname);
		$stmt->execute();
		$stmt->closeCursor();

		//add to recent activity
		$recentactivity_text = "commented on <a href=\"/reviews/".$zipname.".html\">".$zipname."</a>: ".substr($comment_text,0,20)."(...)";
		$stmt = $dbq->prepare("INSERT INTO recentactivity (username, string) VALUES (:username, :recentactivity_text)");
		$stmt->bindParam(':username', $comment_user);
		$stmt->bindParam(':recentactivity_text', $recentactivity_text);
		$stmt->execute();
		$stmt->closeCursor();

		//http_redirect("details.php", array("map" => "$zipname"), true, HTTP_REDIRECT_TEMP);
		header("Location: ".urlencode($zipname).".html");
		echo "Comment added.";
	} else { echo "malformed zipname or rating";}
	$dbq = NULL;
} else { echo "no zipname or comment?";
	// This would be injectable, right?
	/*echo $_POST['zipname'];
	echo $_POST['comment_text'];
	echo $_POST['comment_user'];*/
}
?>
