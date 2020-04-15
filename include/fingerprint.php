<?php
include $_SERVER["DOCUMENT_ROOT"] . "/include/db_connect.php";
include $_SERVER["DOCUMENT_ROOT"] . "/auth.php";

if ($loggedin==1 AND isset ($_POST['hash'])) {
	$hash = $_POST['hash'];
	$hash = preg_replace('/[^a-z\d]/ui', '',$hash);

	mysqli_query($linksql, "UPDATE users SET user_fingerprint='".mysqli_real_escape_string($linksql, $hash)."' WHERE id='$loggedin_id'");

}
?>