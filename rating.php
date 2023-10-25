<?php
// This file is called from details.php when the user clicks on hearts to rate a map
if($_GET['rating'] && $_GET['zipname']){
	define('PUN_ROOT', '/srv/http/forum/');
	include PUN_ROOT.'include/common.php';

	if (!$pun_user['is_guest']) {

		$dbq = new PDO('sqlite:/srv/http/quaddicted.sqlite');
		$username = pun_htmlspecialchars($pun_user['username']);
		$zipname = $_GET['zipname'];
		$rating = $_GET['rating'];
		echo "Debug: Username: ".$username." - zipname: ".$zipname." - Rating: ".$rating."<br />\n";

		// test if already voted
		$preparedStatement = $dbq->prepare("SELECT * FROM ratings WHERE username = :username AND zipname = :zipname");
		$preparedStatement->execute(array(':username' => $username, ':zipname' => $zipname));
		$alreadyvoted = $preparedStatement->fetch();

		if(preg_match('/^[a-z0-9-_\.!\+ \(\)]*$/', $zipname) && $rating <= 5 && $rating > 0) {
			if (!$alreadyvoted) {
				//update map rating
				$stmt = $dbq->prepare("UPDATE maps SET num_ratings = num_ratings + 1, sum_ratings = sum_ratings + :rating_value WHERE zipname = :zipname");
				$stmt->bindParam(':zipname', $zipname);
				$stmt->bindParam(':rating_value', $rating);
				$stmt->execute();

				//store rating in rating table
				$stmt = $dbq->prepare("INSERT INTO ratings (zipname, rating_value, username) VALUES (:zipname, :rating_value, :username)");
				$stmt->bindParam(':zipname', $zipname);
				$stmt->bindParam(':rating_value', $rating);
				$stmt->bindParam(':username', $username);
				$stmt->execute();

				//update user's rating count
				$stmt = $dbq->prepare("UPDATE users SET num_ratings = num_ratings + 1, sum_ratings = sum_ratings + :rating WHERE username = :username");
				$stmt->bindParam(':rating', $rating);
				$stmt->bindParam(':username', $username);
				$stmt->execute();

				//add to recent activity
				//$recentactivity_text = "rated <a href=\"/reviews/".urlencode($zipname).".html\">".$zipname."</a> a ".$rating."/5";
				$recentactivity_text = "rated ".$zipname." a ".$rating."/5";
				$stmt = $dbq->prepare("INSERT INTO recentactivity (username, zipname, string) VALUES (:username, :zipname, :recentactivity_text)");
				$stmt->bindParam(':username', $username);
                                $stmt->bindParam(':zipname', $zipname);
				$stmt->bindParam(':recentactivity_text', $recentactivity_text);
				$stmt->execute();
				$stmt->closeCursor();
			} else {
				if ((int)$rating === (int)$alreadyvoted['rating_value']) {
					echo "You already voted for that map and gave it exactly the same rating...";
					header("HTTP/1.1 418 I'm a teapot");
					die();
				}

				$stmt = $dbq->prepare("UPDATE maps SET sum_ratings = sum_ratings + :new_rating - :old_rating WHERE zipname = :zipname");
				$stmt->bindParam(':zipname', $zipname);
				$stmt->bindParam(':new_rating', $rating);
				$stmt->bindParam(':old_rating', $alreadyvoted['rating_value']);
				$stmt->execute();

				$stmt = $dbq->prepare("UPDATE ratings SET rating_value = :rating WHERE zipname = :zipname AND username = :username");
				$stmt->bindParam(':zipname', $zipname);
				$stmt->bindParam(':username', $username);
				$stmt->bindParam(':rating', $rating);
				$stmt->execute();

				$stmt = $dbq->prepare("UPDATE users SET sum_ratings = sum_ratings + :new_rating - :old_rating WHERE username = :username");
				$stmt->bindParam(':new_rating', $rating);
				$stmt->bindParam(':old_rating', $alreadyvoted['rating_value']);
				$stmt->bindParam(':username', $username);
				$stmt->execute();

                                //$recentactivity_text = "re-rated <a href=\"/reviews/".urlencode($zipname).".html\">".$zipname."</a> a ".$rating."/5";
				$recentactivity_text = "re-rated ".$zipname." a ".$rating."/5";
				$stmt = $dbq->prepare("INSERT INTO recentactivity (username, zipname, string) VALUES (:username, :zipname, :recentactivity_text)");
				$stmt->bindParam(':username', $username);
                                $stmt->bindParam(':zipname', $zipname);
				$stmt->bindParam(':recentactivity_text', $recentactivity_text);
				$stmt->execute();
				$stmt->closeCursor();
			}
		} else {
			echo "malformed zipname or rating";
			header('HTTP/1.1 403 Forbidden');
			die();
		}

		$dbq = NULL; // unset database connection
	} else {
			echo "not logged in?";
			header('HTTP/1.1 403 Forbidden');
			die();
		}
} else {
		echo "no zipname or rating?";
		header('HTTP/1.0 404 Not Found');
		die();
	}
?>
