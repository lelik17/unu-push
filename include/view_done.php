<?

include $_SERVER["DOCUMENT_ROOT"] . "/include/db_connect.php";
include $_SERVER["DOCUMENT_ROOT"] . "/auth.php";
include $_SERVER["DOCUMENT_ROOT"] . "/include/functions.php";

if (isset($_POST) AND $loggedin==1) {
	$hash = $_POST['hash'];
	$report_id = intval($_POST['report_id']);
	$coins = intval($_POST['coins']);

	if (!preg_match("/^[a-zA-Z0-9]+$/i",$hash)) {
		print "Передан некорректный hash";
    	exit(); // некорректный hash
	}

	$res = mysqli_query($linksql, "SELECT * FROM tasks WHERE hash='$hash' AND active='1'");
	if (mysqli_num_rows($res)==0) {
		print "Раздача не найдена";
		exit(); // раздача не найдена
	}
	$task = mysqli_fetch_assoc($res);

	if (!check_google_captcha($_POST['g-recaptcha-response'])) {
		print "Вы не подтвердили, что вы не робот";
		exit();
	}
 
	$res = mysqli_query($linksql, "SELECT * FROM tasks_reports WHERE task_id='".$task["id"]."' AND user_id='$loggedin_id' AND id='$report_id'");
	if (mysqli_num_rows($res)==0) {
		print "Произошла ошибка";
	} else {
		pay($coins, $task["coin"], $task["id"], $loggedin_id, $report_id);
		header("Location: https://push.unu.ru/push/$hash");
	}
}

?>