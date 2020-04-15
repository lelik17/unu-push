<?

include $_SERVER["DOCUMENT_ROOT"] . "/include/db_connect.php";
include $_SERVER["DOCUMENT_ROOT"] . "/auth.php";
include $_SERVER["DOCUMENT_ROOT"] . "/include/functions.php";

$task_id = intval($_GET['task_id']);
$res = mysqli_query($linksql, "SELECT * FROM tasks WHERE id='$task_id' AND user_id='$loggedin_id'");
if (mysqli_num_rows($res)==0) {
	web_show_message("Произошла ошибка", "<p>Раздача не найдена</p>");
}
$task = mysqli_fetch_assoc($res);

$need = check_minter_balance($task_id);

if ($need!=0) { 
	web_show_message("Требуется оплата", "<p>Для запуска раздачи не хватет <strong>".$need." ".$task["coin"]."</strong>. Пожалуйста, сделайте перевод на кошелёк <strong><span class='wrap'>".$task["wallet"]."</span></strong>.</p><p>После того, как сделаете перевод, нажмите на кнопку:<br><button class='sm-bt-purle' onclick=\"location.href='https://push.unu.ru/pay/$task_id'\" style='max-width: 250px'>Готово</button></p><p><a href='https://push.unu.ru/add?task_id=$task_id'>← Изменить настройки раздачи</a>");
}

mysqli_query($linksql, "UPDATE tasks SET active='1' WHERE id='$task_id'");
header("Location: https://push.unu.ru/share/$task_id");

?>