<?

include $_SERVER["DOCUMENT_ROOT"] . "/include/db_connect.php";
include $_SERVER["DOCUMENT_ROOT"] . "/auth.php";
include $_SERVER["DOCUMENT_ROOT"] . "/include/functions.php";

$task_id = intval($_GET['task_id']);
$res = mysqli_query($linksql, "SELECT * FROM tasks WHERE id='$task_id' AND user_id='$loggedin_id'");
if (mysqli_num_rows($res)==0) {
	print "Раздача не найдена :(";
}
$task = mysqli_fetch_assoc($res);

$link = get_share_link($task_id);


if ($task["task_type"]=="followers") {
	$message = "<p>Поделитесь ссылкой с теми, кого хотите поощрить за подписку. Им будет предложено подписаться на " . $task["channel"] . ". В случае успешной подписки, пользователь получит " . $task["coins_per_action"] . " " . $task["coin"] . "</p>";
	$share_message = "Подпишитесь и получите " . $task["coins_per_action"] . " " . $task["coin"] . "!";
} if ($task["task_type"]=="view") {
	if ($task["service"]=="twitch") {
		$message = "<p>Поделитесь ссылкой с теми, кого хотите поощрить за просмотры стрима. За каждую минуту просмотра они получат по " . $task["coins_per_action"] . " " . $task["coin"] . "</p>";
	} else {
		$message = "<p>Поделитесь ссылкой с теми, кого хотите поощрить за просмотры. Им будет предложено посмотреть ваше видео. В случае просмотра ролика целиком пользователь получит " . $task["coins_per_action"] . " " . $task["coin"] . "</p>";
	}
	$share_message = "Посмотрите видео и получите " . $task["coins_per_action"] . " " . $task["coin"] . "!";
} else {
	$message = "<p>Поделитесь ссылкой с теми, кто должен выполнить задание. После перехода пользователя по ссылке ему будет предложено выполнить ваше задание. В случае успешного выполнения, ему будет отправлено вознаграждение " . $task["coins_per_action"] . " " . $task["coin"] . "</p>";
	$share_message = "Получите " . $task["coins_per_action"] . " " . $task["coin"] . "!";
}

$message .= "<p style='font-size: 18px'><strong>Ссылка:</strong> <a href='$link' class='wrap'>$link</a></p>
	<p>Поделиться ссылкой в соц. сетях:</p>
	<p><script src='https://yastatic.net/es5-shims/0.0.2/es5-shims.min.js'></script><script src='https://yastatic.net/share2/share.js'></script><div class='ya-share2' data-services='vkontakte,facebook,odnoklassniki,twitter,viber,whatsapp,telegram' data-url='$link' data-title='$share_message'></div></p>";

if (isset($_GET['modal']) AND $_GET['modal']==1) {
	print $message;
} else {
	web_show_message("Поделитесь ссылкой", $message);
}
?>