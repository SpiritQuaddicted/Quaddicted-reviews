
<?php
// http://fluxbb.org/forums/viewtopic.php?id=4480
// in fluxbb a guest is a visitor that is not logged in
if (!$pun_user['is_guest'])
{
	$username = pun_htmlspecialchars($pun_user['username']);

	$preparedStatement = $dbq->prepare('SELECT * FROM users WHERE username = :username');
	$preparedStatement->execute(array(':username' => $username));
	$userresult = $preparedStatement->fetch();
	$preparedStatement->closeCursor();

	if ($userresult) {
		$num_total_maps = 1216; //todo dynamically
		$num_ratings = $userresult['num_ratings']; //will sonst nicht
		echo "<div id=\"userbar\">Welcome <a href=\"user.php?username=".$username."\">".$username."</a>. ";
		//echo " You have collected <a href=\"\">".$userresult['awards']."</a>/<a href=\"\">9</a> achievements and left <a href=\"\">".$userresult['num_comments']." comments</a>, ".$userresult['num_tags']." tags.</div><br />";
		echo "You rated <a href=\"/reviews/?myratings=1\">".$userresult['num_ratings']."</a>/<a href=\"/reviews/\">~".$num_total_maps."</a> releases, <a href=\"/reviews/?myratings=-1\">".($num_total_maps-$num_ratings)."</a> to go.";
		echo " You left ".$userresult['num_comments']." comments and ".$userresult['num_tags']." tags.";
		echo " <a href=\"/forum/login.php?action=out&amp;id=".$pun_user['id']."&amp;csrf_token=".pun_hash($pun_user['id'].pun_hash(get_remote_address()))."\">Logout</a></div> <!-- userbar -->\n";
		$loggedin = true;
	} else { echo "fhtagn... Your user account is not in the database for the map system, please tell Spirit about this. Sorry!";}
} elseif ($pun_user['is_guest']) {

	// div unten mit z-index:-100; , dann kann man aber nicht mehr drauf klicken...
	// redirect_url must be set in the including php
	?>
	<div id="userbar">
	<form id="login" method="post" action="/forum/login.php?action=in" onsubmit="return process_form(this)" style="display:inline;padding: 0px;">
		<input type="hidden" name="form_sent" value="1" />
		<input type="hidden" name="redirect_url" value="<?php echo $redirect_url ?>" />
		Username: <input type="text" name="req_username" size="25" maxlength="25" tabindex="1" />
		Password: <input type="password" name="req_password" size="25" tabindex="2" />
		<input type="checkbox" name="save_pass" value="1" tabindex="3" />Remember Me
		<input type="submit" name="login" value="Login" tabindex="3" />
	</form>
	Or would you like to <a href="/forum/register.php">register</a>?

	<?php
	echo "</div> <!-- userbar -->\n";
	$loggedin = false;
} else {
	echo "what kind of user are you? tell spirit to fix this message";
}

?>
