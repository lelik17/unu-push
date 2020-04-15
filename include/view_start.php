<?

include $_SERVER["DOCUMENT_ROOT"] . "/include/db_connect.php";
include $_SERVER["DOCUMENT_ROOT"] . "/auth.php";
include $_SERVER["DOCUMENT_ROOT"] . "/include/functions.php";

if (isset($_POST) AND !empty($_POST) AND $loggedin==1) {
	$hash = $_POST['hash'];

	if (!preg_match("/^[a-zA-Z0-9]+$/i",$hash)) {
    	exit(); // некорректный hash
	}

	$res = mysqli_query($linksql, "SELECT * FROM tasks WHERE hash='$hash' AND active='1'");
	if (mysqli_num_rows($res)==0) {
		exit(); // раздача не найдена
	}
	$task = mysqli_fetch_assoc($res);

	if (isset($_POST['report'])) {
		$report_id = intval($_POST['report']);
		$res = mysqli_query($linksql, "SELECT id FROM tasks_reports WHERE id='$report_id'");
		if (mysqli_num_rows($res)==0) {
			print "error";
		} else {
			mysqli_query($linksql, "UPDATE tasks_reports SET date_upd=NOW() WHERE user_id='$loggedin_id' AND task_id='".$task["id"]."' AND id='$report_id'");
		}
	} else {
		mysqli_query($linksql, "INSERT INTO tasks_reports SET task_id='".$task["id"]."', user_id='$loggedin_id', date=NOW(), date_upd=NOW()");
		$id = mysqli_insert_id($linksql);
		print $id;
	}
}

?>