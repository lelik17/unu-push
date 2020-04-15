<?

include $_SERVER["DOCUMENT_ROOT"] . "/include/db_connect.php";
include $_SERVER["DOCUMENT_ROOT"] . "/auth.php";
include $_SERVER["DOCUMENT_ROOT"] . "/include/functions.php";

$global_page = "help";
if (isset($_GET['question']) AND $_GET['question']=="manual") {
	web_show_message("Как работают &laquo;произвольные задачи&raquo;?", "<p>Всё достаточно просто:</p>
		<ul class='list'>
			<li>Вы создаёте задачу, в которой указываете один или несколько вариантов ответа, который будет являться паролем для получения награды.</li>
			<li>Когда пользователь выполнит ваше задание и получит пароль, он сможет забрать награду.</p>
		</ul>
		<h2>Как это применить к реальным задачам?</h2>
		<p>Несколько примеров:</p>
		<ul class='list'>
			<li><strong>Задача: подписка на рассылку</strong> &mdash; создаём задание, в котором просим подписаться на определённую почтовую рассылку, при этом сообщаем, что пароль можно будет найти в следующем выпуске рассылки. Необязательно указывать пароль в рассылке в явном виде. К примеру, это может быть просто первое слово, с которого начинается текст.</li>
			<li><strong>Задача: Регистрация на сайте</strong> &mdash; создаём задание, в котором просим зарегистрироваться на нужном нам сайте. Паролем может послужить, к примеру, количество букв в заголовке страницы, который увидит пользователь, авторизировавшийся на сайте.</li>
		</ul>
		<p><button class='sm-bt-purle' onclick=\"location.href='https://push.unu.ru/add?service=none&type=manual'\" style='max-width: 250px'>Создать задачу</button></p>
		");
} else {
	web_show_message("Помощь", "<p>Сервис UNU.push работает в тестовом режиме. По всем вопросам вы можете писать:</p>
	<ul class='list'>
	<li>На почту <a href='mailto:support@unu.ru'>support@unu.ru</a></li>
	<li>В Telegram <a href='https://telegram.me/lelik17'>@lelik17</a></li>
	</ul>");
}
?>