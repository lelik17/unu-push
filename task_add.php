<?

include $_SERVER["DOCUMENT_ROOT"] . "/include/db_connect.php";
include $_SERVER["DOCUMENT_ROOT"] . "/auth.php";
include $_SERVER["DOCUMENT_ROOT"] . "/include/functions.php";

if (isset($_POST["submit"])) {
	$task_link = $_POST['task_link'];
	$task_coin = $_POST['task_coin'];
	$task_type = $_POST['task_type'];
	$task_coins_per_action = floatval($_POST['task_coins_per_action']);
	$task_limit = intval($_POST['task_limit']);
	
	$service = $_POST['service'];

	if (isset($_POST['task_limit_per_user'])) {
		$task_limit_per_user = intval($_POST['task_limit_per_user']);
		if (empty($task_limit_per_user) OR $task_limit_per_user==0) {
			$task_limit_per_user = 30;
		}
	} else {
		$task_limit_per_user = "";
	}

	if (isset($_POST['task_pay_period'])) {
		$task_pay_period = intval($_POST['task_pay_period']);
	} else {
		$task_pay_period = 0;
	}

	if (isset($_POST['task_password'])) {
		$task_password = $_POST['task_password'];
	} else {
		$task_password = "";
	}

	if (isset($_POST['task_text'])) {
		$task_text = $_POST['task_text'];
	} else {
		$task_text = "";
	}

	if (preg_match('/[^A-Za-z0-9]/', $task_coin)) {
		$error = "Некорректно указана монета";
	}

	if (empty($task_coins_per_action) OR $task_coins_per_action<=0) {
		$error = "Укажите корректное количество монет";
	}

	if (empty($task_limit) OR $task_limit<=0) {
		$error = "Укажите корректное колчиство ";
		if ($task_type=="followers") {
			$error .= "подписчиков";
		}
		if ($task_type=="view") {
			$error .= "просмотров";
		}

		if ($task_type=="manual") {
			$error .= "в поле Лимит выполнений";
		}
	}

	if (empty($error)) {
		if ($service!="telegram" AND $service!="vk" AND $service!="youtube" AND $service!="twitch" AND $service!="vimeo"  AND $service!="none") {
			$error = "Некорректный сервис";
		}
	}

	if (empty($error)) {
		if ($task_type!="followers" AND $task_type!="view" AND $task_type!="manual") {
			$error = "Некорректный тип задачи";
		}
	}

	if (empty($error) AND empty($task_link)) {
		$error = "Не указана ссылка";
	}

	if (empty($error)) {
		if (!empty($task_limit_per_user) AND $task_limit_per_user<=0) {
			$error = "Некорректно указан лимит минут";
		}
	}

	if (empty($error) AND $service!="telegram") {
		if (filter_var($task_link, FILTER_VALIDATE_URL) !== false) { 

		} else {
			$error = "Введён некорректный URL";
		}
	}

	if ($task_type=="manual") {
		if (empty($task_text)) {
			$error = "Вы не ввели текст задания";
		}
		if (empty($task_password)) {
			$error = "Вы не ввели пароль для получения награды";
		}
		if (strlen($task_text)>=5000) {
			$error = "Слишком длинный текст задания.";
		}
		if (strlen($task_password)>=5000) {
			$error = "Слишком длинный текст в поле Пароль.";
		}
	}

	if (empty($error)) {
		if (!check_minter_coin($task_coin)) {
			$error = "Некорректо указана монета";
		}
	}

	if ($service=="telegram" AND empty($error)) {
		$channel = clear_tg_link($task_link);
		if (!check_tg_link($channel)) {
			$error = "Указана некорректная ссылка на Telegram-канал";
		}

		if (empty($error)) {
			if (check_bot_access($channel)=="false") {
				$error = "Необходимо добавить @unu_push_bot в качестве администратора $channel для отслеживания количества подписчиков. <a href='https://telegra.ph/Kak-dobavit-bota-v-kanal-04-06' target='_blank' style='color: #FFF; text-decoration: underline'>Инструкция</a>";
			}
		}
	}

	if ($service=="vk" AND empty($error)) {
		$channel = $task_link;
		$res = vk_check_group_link($channel);
		if ($res["result"]=="error") {
			$error = $res["message"];
		} else {

			$group_id = $res["group_id"];

			$res = vk_is_closed_group($group_id);
			if ($res["result"]=="error") {
				$error = "Произошла ошибка при обращении к группе";
			} else {
				if ($res["is_closed"]==2) {
					$error = "Это закрытая группа VK, мы не можем отслеживать в ней подписчиков, пока вы не сделаете её открытой.";
				}
			}
		}

	}

	if ($service=="youtube" AND empty($error)) {
		$youtube_id = clear_youtube_link($task_link);
		if (empty($youtube_id) OR $youtube_id===false) {
			$error = "Указана некорректная ссылка на YouTube";
		} else {
			if (!check_youtube_id($youtube_id)) {
				$error = "Указана некорректная ссылка на YouTube";
			} else {
				$channel = "https://youtu.be/".$youtube_id;
			}
		}
	}

	if ($service=="vimeo" AND empty($error)) {
		$vimeo_id = clear_vimeo_link($task_link);
		if (empty($vimeo_id) OR $vimeo_id===false) {
			$error = "Указана некорректная ссылка на Vimeo";
		} else {
			if (!check_vimeo_id($vimeo_id)) {
				$error = "Указана некорректная ссылка на Vimeo";
			} else {
				$channel = "https://vimeo.com/".$vimeo_id;
			}
		}
	}

	if (empty($error)) {
		if ($service=="twitch") {
			$min_reward = get_minimal_reward($task_coin, $task_coins_per_action);
			$max_reward = $task_limit_per_user*$task_coins_per_action;
			if ($max_reward<=$min_reward) {
				$need_limit = ceil($min_reward/$task_coins_per_action);
				$need_coins_per_action = ceil($min_reward/$task_limit_per_user);
				$error = "Необходимо увеличить лимит минут до $need_limit, либо награду за минуту просмотра до $need_coins_per_action $task_coin";
			}

			if (empty($error)) {
				$channel = clear_twitch_link($task_link);
				if (empty($channel) OR $channel===false) {
					$error = "Указана некорректная ссылка на Twitch-канал";
				} 
				$channel = $task_link;
			}
		}
	}

	if (empty($error)) {
		$need_coins = round($task_limit * $task_coins_per_action, 2);
		$need_coins = round($need_coins/100*$sys_perc+$need_coins, 4); // добавляем комиссию сервиса

		if (empty($channel)) {
			$channel = $task_link;
		}

		if ($loggedin!=1) {
			$user_id = user_register();
		} else {
			$user_id = $loggedin_id;
		}

		mysqli_query($linksql, "INSERT INTO tasks SET user_id='$user_id', task_type='$task_type', service='$service', channel='$channel', coin='$task_coin', coins_per_action='$task_coins_per_action', task_limit='$task_limit', task_limit_per_user='$task_limit_per_user', task_text='".mysqli_real_escape_string($linksql, $task_text)."', task_password='".mysqli_real_escape_string($linksql, $task_password)."', need_coins='$need_coins', pay_period='$task_pay_period'");
		$task_id = mysqli_insert_id($linksql);
		$wallet = get_minter_wallet($task_id);
		web_show_message("Раздача добавлена", "<p>Для запуска раздачи отправьте <strong>$need_coins $task_coin</strong> на кошелёк <strong><span class='wrap'>$wallet</span></strong>.<br>В сумму включена комиссия сервиса $sys_perc%</p><p>После того, как сделаете перевод, нажмите на кнопку:<br><button class='sm-bt-purle' onclick=\"location.href='https://push.unu.ru/pay/$task_id'\" style='max-width: 250px'>Готово</button></p><p><a href='https://push.unu.ru/add?task_id=$task_id'>← Изменить настройки раздачи</a>");

	}

} else {
	if (isset($_GET['task_id'])) {
		$task_id = intval($_GET['task_id']);
		$res = mysqli_query($linksql, "SELECT * FROM tasks WHERE id='$task_id' AND user_id='$loggedin_id'");
		$task = mysqli_fetch_assoc($res);

		$task_link = $task["channel"];
		$task_coin = $task["coin"];
		$task_coins_per_action = $task["coins_per_action"];
		$task_limit = $task["task_limit"];
		$task_limit_per_user = $task["task_limit_per_user"];
		$task_pay_period = $task["pay_period"];
		$task_type = $task["task_type"];
		$task_password = $task["task_password"];
		$task_text = $task["task_text"];
		$service = $task["service"];
	} else {
		$service = $_GET['service'];
		$task_type = $_GET['type'];
	}
}

$title = "Создать раздачу наград &mdash; UNU.push";
$h1 = "Создать раздачу наград";
$global_page = "add";

if (isset($service) AND $service=="vk") {
	$link_name = " на VK";
	$link_example = "Куда нужно привлечь подписчиков? Например: https://vk.com/unuru";
}

if (isset($service) AND $service=="telegram") {
	$link_name = " на Telegram-канал";
	$link_example = "Куда нужно привлечь подписчиков? Например: https://t.me/unuru_ann или @unuru_ann";
}

if (isset($service) AND $service=="youtube") {
	if ($task_type=="view") {
		$link_name = " на видео в YouTube";
		$link_example = "Какой видеоролик нужно посмотреть? Например: https://youtube.com/watch?v=apXeheoQmj0";
	}
}

if (isset($service) AND $service=="vimeo") {
	if ($task_type=="view") {
		$link_name = " на видео в Vimeo";
		$link_example = "Какой видеоролик нужно посмотреть? Например: https://vimeo.com/235746460";
	}
}

if (isset($service) AND $service=="twitch") {
	if ($task_type=="view") {
		$link_name = " на стрим в Twitch";
		$link_example = "Какой стрим нужно посмотреть? Например: https://twitch.tv/myth";
	}
}

if (isset($service) AND $service=="none") {
	if ($task_type=="manual") {
		$link_name = "";
		$link_example = "Сайт/страница, необходимая для выполнения задачи";
	}
}

include "design_header.php";
?>
<h1 class="page-title"><? print $h1; ?></h1></div>
<div class="container container-mobile">

<div class="flex-row justify-space align-start">

			<div class="edit-task">
				<form method="post" name="orderForm">
				<div class="edit-task__content main-tab active">
				
				<? 
				if (isset($error)) { 
					?><div class="form-user__error"><? print $error; ?></div><? 
				} 
				
				?>
				<div class="edit-task__item">
					<div class="edit-task__label">Ссылка<? print $link_name; ?>:</div>
					<div class="edit-task__hint"><span class="wrap"><? print $link_example; ?></span></div>
					<input type="text" name="task_link" value="<? if (isset($task_link)) { print $task_link; } ?>" />
				</div>
				<div class="edit-task__item">
						<div class="edit-task__label">Монета для награды:</div>
						<div class="edit-task__hint">Какую монету сети Minter раздавать в качестве награды? <a href="https://telegra.ph/CHto-takoe-monety-seti-Minter-04-06" target="_blank">Что за монеты Minter?</a></div>
						<select name="task_coin">
							<option value="bip">BIP</option>
							<?
							if (!isset($task_coin)) {
								$task_coin = "BIP";
							}

							$coins = get_minter_coins();
							foreach ($coins as $coin) {
								if ($task_coin==$coin) {
									print "<option selected>$coin</optin>";
								} else {
									print "<option>$coin</optin>";
								}
							}
							?>
						</select>
				</div>
				<div class="edit-task__item">
					<div class="edit-task__label">Количество монет</div>
					<div class="edit-task__hint">Сколько монет дать в качестве награды<? if ($service=="twitch") { print " <strong>за одну минуту просмотра</strong>"; } ?>.</div>
					<input type="number" min="0.001" step="0.001" name="task_coins_per_action" value="<? if (isset($task_coins_per_action)) { print $task_coins_per_action; } ?>" />
				</div>
				<div class="edit-task__item">
					<?
					if ($task_type=="followers") {
						?>
						<div class="edit-task__label">Количество подписчиков</div>
						<div class="edit-task__hint">Сколько подписчиков вы хотите привлечь (поощрить).</div>
						<?
					}

					if ($task_type=="view" AND ($service=="youtube" OR $service=="vimeo")) {
						?>
						<div class="edit-task__label">Количество просмотров</div>
						<div class="edit-task__hint">Сколько всего просмотров вы хотите привлечь (поощрить).</div>
						<?
					}

					if ($task_type=="view" AND $service=="twitch") {
						?>
						<div class="edit-task__label">Лимит зрителей</div>
						<div class="edit-task__hint">Сколько всего зрителей вы хотите привлечь (поощрить).</div>
						<?
					}

					if ($task_type=="manual" AND $service=="none") {
						?>
						<div class="edit-task__label">Лимит выполнений</div>
						<div class="edit-task__hint">Сколько всего пользователей вы хотите привлечь (наградить).</div>
						<?
					} 

					?>
					<input type="number" step="1" min="1" name="task_limit" value="<? if (isset($task_limit)) { print $task_limit; } ?>" />
				</div>
				<?
				if ($service=="twitch") {
					?>
					<div class="edit-task__item">
					<div class="edit-task__label">Лимит минут</div>
					<div class="edit-task__hint">Максимальное количество минут, которое вы готовы оплатить каждому зрителю.</div>
					<input type="number" min="1" step="1" name="task_limit_per_user" value="<? if (isset($task_limit_per_user)) { print $task_limit_per_user; } ?>" />
					</div>
					<?
				}

				if ($service=="none") {
					?>
					<div class="edit-task__item">
					<div class="edit-task__label">Задание</div>
					<div class="edit-task__hint">Подробно опишите, что именно должен сделать пользователь, чтобы получить награду.</div>
					<textarea name="task_text"><? if (isset($task_text)) { print $task_text; } ?></textarea>
					</div>
					<div class="edit-task__item">
					<div class="edit-task__label">Пароль</div>
					<div class="edit-task__hint">Пароль, который должен ввести пользователь, чтобы задание считалось выполненным. Можно указать несколько вариантов, каждый с новой строки.</div>
					<textarea name="task_password"><? if (isset($task_password)) { print $task_password; } ?></textarea>
					</div>
					<?
				}
				?>
				<input type="hidden" name="service" value="<? print $service; ?>" />
				<input type="hidden" name="task_type" value="<? print $task_type; ?>" />
				<input type="hidden" name="submit" value="1" />
				<button class="bt-purle" type="submit">Создать</button>
				</div>
			
			</div>

			<div class="user-side-note" style="margin-top: 30px">
				<p><?
				if ($service=="vk") {
					?>
					<img src="https://push.unu.ru/i/VK.com-logo.svg" alt="" style="width: 100px; height: auto">
					<?
				}

				if ($service=="telegram") {
					?>
					<img src="https://push.unu.ru/i/telegram_logo.svg" alt="" style="width: 100px; height: auto">
					<?
				}

				if ($service=="youtube") {
					?>
					<img src="https://unu.ru/i/YouTube_Logo_2017.svg" alt="" style="width: 200px; height: auto">
					<?
				}

				if ($service=="twitch") {
					?>
					<img src="https://unu.ru/i/twitch.svg" alt="" style="width: 200px; height: auto">
					<?
				}
				?>
				</p>
				<p>&nbsp;</p>
				<p>В данный момент сервис UNU.push работает в тестовом режиме. Пожалуйста, сообщайте обо всех найденных ошибках на <a href="mailto:support@unu.ru">support@unu.ru</a></p>

			</div>

		</div>
<?
include "design_footer.php";

?>