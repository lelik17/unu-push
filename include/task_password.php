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

	$res = mysqli_query($linksql, "SELECT count(1) AS done FROM tasks_reports WHERE task_id='".$task["id"]."'");
	$data = mysqli_fetch_assoc($res);

	if ($data["done"]>=$task["task_limit"]) {
		print "Чуть-чуть не успели 😥, эта раздача уже завершена.";
		exit();
	}

	if (!check_google_captcha($_POST['g-recaptcha-response'])) {
		print "Вы не подтвердили, что вы не робот";
		exit();
	}

	$right_password = 0;
	$passwords = explode("\n", $task["task_password"]);
	foreach($passwords AS $password) {
		$password = trim($password);

		if (mb_strtoupper($password) === mb_strtoupper($_POST['pass'])) {
			print "done";
			pay($task["coins_per_action"], $task["coin"], $task["id"], $loggedin_id);
			$right_password = 1;
		}
	}

	if ($right_password==0) {
		print "Введён неверный пароль";
		exit();
	}

}

?>