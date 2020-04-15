<?

include $_SERVER["DOCUMENT_ROOT"] . "/include/db_connect.php";
include $_SERVER["DOCUMENT_ROOT"] . "/include/functions.php";

$tg_data = file_get_contents('php://input');
$tg_data = json_decode($tg_data, true);

if (isset($tg_data['message'])) { // прислали сообщение
	$message = $tg_data['message']['text'];
	$chat_id = $tg_data['message']['chat']['id'];
	$type = "message";
}

if (isset($tg_data['callback_query'])) { // прислали callback
	$callback_query = $tg_data['callback_query'];
	$message = $callback_query['data'];
	$message_id = ['callback_query']['message']['message_id'];
	$chat_id = $callback_query['message']['chat']['id'];
	$type = "callback";
}

if (isset($tg_data["callback_query"]["message"]["chat"]['type'])) {
	if ($tg_data["callback_query"]["message"]["chat"]['type']=="group") {
		// это группа, ничего не пишем
		exit();
	}
}

if (isset($tg_data["message"]["chat"]['type'])) {
	if ($tg_data["message"]["chat"]['type']=="group") {
		// это группа, ничего не пишем
		exit();
	}
}

$state = get_state($chat_id);

if ($type=="message") { // если юзер прислал сообщение
	switch($message) {
	    case '/start':  
		    bot_welcome_message($chat_id);
		    bot_task_add($chat_id);
			break;
		case '🆘 Помощь':  
			bot_sendmessage($chat_id, "Бот работает в тестовом режиме. Сообщить о найденных багах или задать вопросы можно @lelik17");
			break;
		case '➕ Новая раздача':  
			bot_task_add($chat_id);
			break;
		case '📋 Мои раздачи':  
			$user = get_user_info($chat_id);
			$res = mysqli_query($linksql, "SELECT * FROM tasks WHERE user_id='".$user["id"]."' AND active='1'");
			$tasks = mysqli_num_rows($res);

			if ($tasks>0) {
				$n = 0;
				$keyboard = '{"inline_keyboard":[';
				while ($task = mysqli_fetch_array($res)) {
					$n++;

					$result = mysqli_query($linksql, "SELECT count(1) AS total FROM tasks_reports WHERE task_id='".$task["id"]."'");
					$reports = mysqli_fetch_assoc($result);

					$task_name = "🔸" . $task["channel"] . " - ".$reports["total"]." из ". $task["task_limit"] . " подписчиков";
					$keyboard .= '[{"text":"'.$task_name.'","callback_data":"get-task-'.$task["id"].'"}]';
					if ($n!=mysqli_num_rows($res)) {
						$keyboard .= ",";
					}
				}
				$keyboard .= ']}';
				bot_sendmessage($chat_id, "Ваши активные раздачи:", $keyboard);
			} else {
				$inline_button1 = array("text"=>"➕ Создать","callback_data"=>'add-task');
				$inline_keyboard = [[$inline_button1]];
				$keyboard=array("inline_keyboard"=>$inline_keyboard);
				$replyMarkup = json_encode($keyboard); 
				bot_sendmessage($chat_id, "У вас нет активных раздач. Создайте новую", $replyMarkup);
			}
			break;
		default: // если команда не распознана
			
			if (substr($message, 0, 6)=="/start") { 
			// если передан id в start (юзер пришёл по ссылке и хочет получть вознаграждение за подписку)

				$task_id = intval(substr($message, 7));
				bot_welcome_message($chat_id);

				$res = mysqli_query($linksql, "SELECT * FROM tasks WHERE id='$task_id' AND active='1'");
				if (mysqli_num_rows($res)==0) {
					bot_sendmessage($chat_id, "Раздача не найдена 😢 Похоже, автор раздачи удалил её.");
					exit();
				} 
				$task = mysqli_fetch_assoc($res);

				// проверяем активна ли ещё раздача
				$res = mysqli_query($linksql, "SELECT count(1) AS done FROM tasks_reports WHERE task_id='$task_id'");
				$data = mysqli_fetch_assoc($res);

				if ($data["done"]>=$task["task_limit"]) {
					bot_sendmessage($chat_id, "Вы чуть-чуть не успели 😥\nЭта раздача уже завершена.");
					exit();
				}

				// проверяем не получал ли пользователь оплату ранее
				$res = check_already_paid($chat_id, $task_id);

				if ($res["fail"]==1) {
					bot_sendmessage($chat_id, "Вам уже было отправлено вознаграждение за подписку на " . $task["channel"]. ". Если вы не получили вознаграждение ранее, перейдите по ссылке: " . $res["info"]);
					exit();
				}

				// выводим сообщение о предложении подписаться
				$inline_button1 = array("text"=>"✅ Подписался","callback_data"=>'check-follower');
				$inline_keyboard = [[$inline_button1]];
				$keyboard=array("inline_keyboard"=>$inline_keyboard);
				$replyMarkup = json_encode($keyboard); 

				bot_sendmessage($chat_id, "Подпишитесь на ".$task["channel"]." и получите за это <strong>" . $task["coins_per_action"] . " " . $task["coin"] . "</strong> (".convert($task["coin"], "rub", $task["coins_per_action"])." руб).", $replyMarkup);
				clear_states($chat_id);
				save_state($chat_id, "check-follower", $task_id);
				
			}

			switch($state) {
				case 'add-task':
					$message = clear_tg_link($message);

					if (check_tg_link($message)) {

						$inline_button1 = array("text"=>"✅ Готово","callback_data"=>'check-bot-access');
						$inline_keyboard = [[$inline_button1]];
						$keyboard=array("inline_keyboard"=>$inline_keyboard);
						$replyMarkup = json_encode($keyboard); 

						bot_sendmessage($chat_id, "👌");
						bot_sendmessage($chat_id, "<strong>Добавьте @unu_push_bot в качестве администратора на $message</strong> чтобы мы могли отслеживать новых подписчиков. Достаточно установить самые минимальные права.\n\nЕсли не знаете как добавить бота в канал, вот <a href='https://telegra.ph/Kak-dobavit-bota-v-kanal-04-06'>простая инструкция</a>.\n\nКогда сделаете это, нажмите на кнопку готово.", $replyMarkup);
						save_state($chat_id, "add-task-step2", $message);

					} else {
						bot_sendmessage($chat_id, "Передана некорректная ссылка на канал/группу. Попробуйте ещё раз, пожалуйста.");
					}
					break;
				case 'add-task-step2':
					bot_check_bot_access($chat_id);
					break;
				case 'add-task-step3':
					$coin = trim($message);
					if (check_minter_coin($coin)) {
						bot_sendmessage($chat_id, "<strong>Сколько $coin вы хотите дать за 1 подписку?</strong>");
						$user = get_user_info($chat_id);
						$channel = get_state_data($chat_id, "add-task-step3");
						mysqli_query($linksql, "INSERT INTO tasks SET user_id='".$user["id"]."', channel='$channel', coin='$coin', service='telegram'");
						$task_id = mysqli_insert_id($linksql);
						save_state($chat_id, "add-task-step4", $task_id);
					} else {
						bot_sendmessage($chat_id, "Такой моенты не существует. Введите корректную.");
					}
					break;
				case 'add-task-step4':
					$per_follower = trim($message);
					if (is_numeric($per_follower) AND $per_follower>0) {
						$per_follower = round($per_follower, 4);
						$task_id = get_state_data($chat_id, "add-task-step4");
						mysqli_query($linksql, "UPDATE tasks SET coins_per_action='$per_follower' WHERE id='$task_id'");
						save_state($chat_id, "add-task-step5", $task_id);
						bot_sendmessage($chat_id, "<strong>Сколько подписчиков вы хотите привлечь?</strong> (поощрить монетами)");
					} else {
						bot_sendmessage($chat_id, "Некорректно указана сумма. Сколько монет вы хотите дать за 1 подписку?");
					} 
					break;
				case 'add-task-step5':
					$total_followers = trim($message);
					if (is_numeric($total_followers) AND $total_followers>0) {
						$total_followers = intval($total_followers);
						$task_id = get_state_data($chat_id, "add-task-step5");
						save_state($chat_id, "add-task-step6", $task_id);
						
						$res = mysqli_query($linksql, "SELECT * FROM tasks WHERE id='$task_id'");
						$task = mysqli_fetch_assoc($res);

						$need_coins = ceil($total_followers * $task["coins_per_action"]);
						$need_coins = round($need_coins/100*$sys_perc+$need_coins, 2); // добавляем комиссию сервиса
						mysqli_query($linksql, "UPDATE tasks SET task_limit='$total_followers', need_coins='$need_coins' WHERE id='$task_id'");

						$wallet = get_minter_wallet($task["id"]);

						$inline_button1 = array("text"=>"✅ Готово","callback_data"=>'check-payment');
						$inline_keyboard = [[$inline_button1]];
						$keyboard=array("inline_keyboard"=>$inline_keyboard);
						$replyMarkup = json_encode($keyboard); 

						bot_sendmessage($chat_id, "👌");
						bot_sendmessage($chat_id, "<strong>Отправьте $need_coins " . $task["coin"] . " на кошелёк</strong> <code>$wallet</code>. В сумму включена комиссия сервиса $sys_perc%\n\nПосле того, как сделаете перевод, нажмите на кнопку Готово", $replyMarkup);

					} else {
						bot_sendmessage($chat_id, "Некорректное значение. Сколько подписчиков вы хотите привлечь?");
					} 
					break;
				case 'add-task-step6':
					$task_id = get_state_data($chat_id, "add-task-step6");
					check_task_balance($chat_id, $task_id);
					break;
				case 'check-follower':
					$inline_button1 = array("text"=>"✅ Готово","callback_data"=>'check-follower');
					$inline_button1 = array("text"=>"Отказаться","callback_data"=>'dont-want-follow');
					$inline_keyboard = [[$inline_button1],[$inline_button2]];
					$keyboard=array("inline_keyboard"=>$inline_keyboard);
					$replyMarkup = json_encode($keyboard); 

					bot_sendmessage($chat_id, "Подпишитесь на ".$task["channel"].". После подписки нажмите на кнопку Готово.", $replyMarkup);
					break;
			}
	}
}

if ($type=="callback") { // если юзер кликнул по кнопке
	switch($message) {
		case 'check-bot-access':
			bot_check_bot_access($chat_id);
			break;
		case 'check-payment':
			$task_id = get_state_data($chat_id, "add-task-step6");
			check_task_balance($chat_id, $task_id);
			break;
		case 'change-channel':
			clear_states($chat_id);
			bot_task_add($chat_id);
			break;
		case 'check-follower':
			
			$task_id = get_state_data($chat_id, "check-follower");

			// проверяем не получал ли пользователь оплату ранее
			$res = check_already_paid($chat_id, $task_id);

			if ($res["fail"]==1) {
				bot_sendmessage($chat_id, "Вам уже было отправлено вознаграждение за подписку на " . $task["channel"]. ". Если вы не получили вознаграждение ранее, перейдите по ссылке: " . $res["info"]);
				exit();
			}
			
			$res = mysqli_query($linksql, "SELECT * FROM tasks WHERE id='$task_id'");
			$task = mysqli_fetch_assoc($res);

			$res = check_is_tg_member($chat_id, $task["channel"]);
			if ($res) {
				// можно платить
				bot_sendmessage($chat_id, "👍");

				$user = get_user_info($chat_id);
				$link = pay($task["coins_per_action"], $task["coin"], $task_id, $user["id"]);
				bot_sendmessage($chat_id, "Ваше вознаграждение можно забрать по ссылке: $link");
				clear_states($chat_id);
			} else {
				$inline_button1 = array("text"=>"✅ Готово","callback_data"=>'check-follower');
				$inline_button2 = array("text"=>"Отказаться","callback_data"=>'dont-want-follow');
				$inline_keyboard = [[$inline_button1],[$inline_button2]];
				$keyboard=array("inline_keyboard"=>$inline_keyboard);
				$replyMarkup = json_encode($keyboard); 

				bot_sendmessage($chat_id, "Вы не подписались на ".$task["channel"].". Подпишитесь и получите за это <strong>" . $task["coins_per_action"] . " " . $task["coin"] . "</strong>.\n\nПосле подписки нажмите на кнопку Готово.", $replyMarkup);
			}
			break;
		case 'dont-want-follow':
			clear_states($chat_id);
			$inline_button1 = array("text"=>"➕ Создать раздачу","callback_data"=>'add-task');
			$inline_keyboard = [[$inline_button1]];
			$keyboard=array("inline_keyboard"=>$inline_keyboard);
			$replyMarkup = json_encode($keyboard); 

			bot_sendmessage($chat_id, "Если не хотите подписываться, возможно хотите создать собственную раздачу наград за подписку на канал?", $replyMarkup);
			break;
		case 'add-task':
			bot_task_add($chat_id);
			break;
		default: // если callback не распознана

			if (substr($message, 0, 9)=="get-task-") { 
				$task_id = substr($message, 9);

				$res = mysqli_query($linksql, "SELECT * FROM tasks WHERE id='$task_id'");
				$task = mysqli_fetch_assoc($res);

				$result = mysqli_query($linksql, "SELECT count(1) AS total FROM tasks_reports WHERE task_id='".$task["id"]."'");
				$reports = mysqli_fetch_assoc($result);

				$text = "<strong>Раздача монет за подписку на " . $task["channel"] . "</strong>\n\n";
				$text .= "Награда подписчику: " . $task["coins_per_action"] . " " . $task["coin"] . "\n";
				$text .= "Подписалось: " . $reports["total"] . "\n";
				$text .= "Осталось: " . ($task["task_limit"] - $reports["total"]) . "\n";

				$inline_button1 = array("text"=>"Удалить раздачу","callback_data"=>'delete-task-'.$task_id);
				$inline_keyboard = [[$inline_button1]];
				$keyboard=array("inline_keyboard"=>$inline_keyboard);
				$replyMarkup = json_encode($keyboard); 

				bot_sendmessage($chat_id, $text, $replyMarkup);
			}

			if (substr($message, 0, 12)=="delete-task-") { 
				$task_id = substr($message, 12);
				$inline_button1 = array("text"=>"Да, удалить","callback_data"=>'go-delete-task-'.$task_id);
				$inline_keyboard = [[$inline_button1]];
				$keyboard=array("inline_keyboard"=>$inline_keyboard);
				$replyMarkup = json_encode($keyboard); 

				bot_sendmessage($chat_id, "Точно удалить?", $replyMarkup);
			}

			if (substr($message, 0, 15)=="go-delete-task-") { 
				$task_id = substr($message, 15);

				$user = get_user_info($chat_id);
				mysqli_query($linksql, "UPDATE tasks SET active='0' WHERE id='$task_id' AND user_id='".$user["id"]."'");

				$inline_button1 = array("text"=>"➕ Создать раздачу","callback_data"=>'add-task');
				$inline_keyboard = [[$inline_button1]];
				$keyboard=array("inline_keyboard"=>$inline_keyboard);
				$replyMarkup = json_encode($keyboard); 

				bot_sendmessage($chat_id, "<strong>Раздача удалена</strong>. Может хотите создать новую?", $replyMarkup);
			}
	}
}

?>