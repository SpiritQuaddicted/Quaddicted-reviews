<?php
//error_reporting(E_ALL);
$db = new SQLite3('/srv/http/quaddicted.sqlite');
$zipname = $db->querySingle('SELECT zipname FROM maps ORDER BY random() LIMIT 1;');
//echo "zip: ".$zipname;
header('Location: /reviews/'.$zipname.'.html');
?>
