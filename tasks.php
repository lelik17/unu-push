<?

include $_SERVER["DOCUMENT_ROOT"] . "/include/db_connect.php";
include $_SERVER["DOCUMENT_ROOT"] . "/auth.php";
include $_SERVER["DOCUMENT_ROOT"] . "/include/functions.php";

if ($loggedin!=1) {
	$loggedin_id = user_register();
}

$global_page = "tasks";
$title = "Мои раздачи";
include "design_header.php";



?> 
<h1 class="page-title">Мои раздачи</h1>
<?

$res = mysqli_query($linksql, "SELECT id FROM tasks WHERE user_id='$loggedin_id' AND active='1'");
$tasks_count = mysqli_num_rows($res);

if ($tasks_count==0) {
	print '<p class="preview-text">Вы ещё не создали ни одной раздачи. Самое время <a href="https://push.unu.ru">сделать это</a> ;)</p>';
	include "design_footer.php";
	exit();
} else {
	print '<p class="preview-text"><a href="https://push.unu.ru">Новая раздача наград</a></p>';
}

?>
</div>

<div class="container container-mobile">
<div class="orders-block">
<?

?>
<div class="orders-row" style="padding-top: 10px">
	<div class="orders-table">
			<div class="order-table__head">
				<div class="order-table__cell">Раздача</div>
				<div class="order-table__cell">Статус</div>
				<div class="order-table__cell">Выполнено</div>
				<div class="order-table__cell">Лимит</div>
				<div class="order-table__cell">Награда</div>
				<div class="order-table__cell"></div>
			</div>

			<?
			$res = mysqli_query($linksql, "SELECT * FROM tasks WHERE user_id='$loggedin_id' AND active='1'");
			while ($task = mysqli_fetch_array($res)) {

				if ($task["task_type"]=="followers") {
					$task_name = "Подписка в ";
					$link_text = "На что подписаться: ";
					if ($task["service"]=="vk") {
						$task_name .= "VK";
					}
					if ($task["service"]=="telegram") {
						$task_name .= "Telegram";
					} 
				} if ($task["task_type"]=="view") {
					$task_name = "Просмотры в ";
					$link_text = "Что посмотреть: ";
					if ($task["service"]=="youtube") {
						$task_name .= "YouTube";
					}
					if ($task["service"]=="twitch") {
						$task_name .= "Twitch";
					} 
					if ($task["service"]=="vimeo") {
						$task_name .= "Vimeo";
					} 
				} else {
					$task_name = "Произвольная задача";
					$link_text = "Ссылка: ";
				}

				$result = mysqli_query($linksql, "SELECT count(1) AS done FROM tasks_reports WHERE task_id='".$task["id"]."'");
				$reports = mysqli_fetch_assoc($result);

				$share_link = get_share_link($task["id"]);
				$follow_link = $task["channel"];

				if ($task["service"]=="telegram") {
					$follow_link = "https://t.me/" . substr($task["channel"], 1);
				}

				?>
				<div class="order-table__row">
					<div class="order-table__cell">
						<a href="#" class="order-name"><i></i><? print $task_name; ?></a> <a href="https://push.unu.ru/share/modal/<? print $task["id"]; ?>" rel="modal:open"><img src="https://unu.ru/i/share.svg" alt="" tooltip="Поделиться ссылкой на раздачу" style="width: 18px; height: auto"/></a>
						<div class="order-table__desc">
							<p><? print $link_text; ?><a href="<? print $follow_link; ?>" target="_blank"><? print $follow_link; ?></a></p>
							<p>Ссылка на раздачу: <a href="<? print $share_link; ?>"><? print $share_link; ?></a></p>
						</div>
					</div>
					<div class="order-table__cell">
						<?
						if ($reports["done"]>=$task["task_limit"]) {
							?>
							<span class="status-stop">Завершена</span>
							<?
						} else {
							?>
							<span class="status-work">Активна</span>
							<?
						}
						?>
					</div>
					<div class="order-table__cell">
						<? print $reports["done"]; ?>
					</div>
					<div class="order-table__cell">
						<? print $task["task_limit"]; ?>
					</div>
					<div class="order-table__cell"><? print $task["coins_per_action"] . " " . $task["coin"]; ?> ≈ <? print convert($task["coin"], "rub", $task["coins_per_action"]); ?> руб</div>
					<div class="order-table__cell">
						<div class="order-table__tools">
								<!--<a href="#" class="icon-settings" tooltip="Редактировать заказ"></a>-->
							</div>
					</div>
				</div>
				<?
			}
			?>					
	</div>	
</div>
<?

print "</div>";
include "design_footer.php";
?>
