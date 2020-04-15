<!doctype html>
<html lang="ru">
<head>
<meta charset=utf-8>
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="format-detection" content="telephone=no">
<title><? print $title; ?></title>
<?
if (isset($description)) {
	?>
	<meta name="description" content="<? print $description; ?>" />
	<?
}
?>
<link href="https://push.unu.ru/css/style.css?09044" rel="stylesheet">
<link href="https://push.unu.ru/css/tooltip.css" rel="stylesheet">
<link rel="icon" type="image/png" href="https://unu.ru/favicon/512x512.png" sizes="512x512">
<link rel="icon" type="image/png" href="https://unu.ru/favicon/96x96.png" sizes="96x96">
<link rel="icon" type="image/png" href="https://unu.ru/favicon/32x32.png" sizes="32x32">
<link rel="icon" type="image/png" href="https://unu.ru/favicon/16x16.png" sizes="16x16">
<script src="https://www.google.com/recaptcha/api.js"></script>
<? if (isset($dop_head)) { print $dop_head; } ?>
<style>
	.task_list {
		margin-left: 20px;
	}
	.task_list li {
		margin-bottom: 15px;
		font-size: 16px;
	}
</style>
<script src="https://push.unu.ru/js/jquery-3.3.1.min.js"></script>

</head>
<body<? if (isset($dop_body)) { print " $dop_body"; } ?>>
<header class="header">
	<div class="container">
		<div class="flex-row">

			<div class="header-logo">
				<a href="https://push.unu.ru"><span><img src="https://unu.ru/i/unu-logo1.svg" alt=""></span></a>
			</div>
				<nav class="header-menu">
					<ul class="flex-row align-start">
						<li><a href="https://push.unu.ru"<? if ($global_page=="add") { print 'class="active"'; } ?>>Добавить раздачу</a></li>
						<li><a href="https://push.unu.ru/tasks"<? if ($global_page=="tasks") { print 'class="active"'; } ?>>Мои раздачи</a></li>
						<li><a href="https://push.unu.ru/help"<? if ($global_page=="help") { print 'class="active"'; } ?>>Помощь</a></li>
					</ul>
				</nav>
			<div class="header-user">
		
				<div class="header-user__bar">
					<div class="header-user__photo"></div>
					<div class="header-user__name"> <img src="https://push.unu.ru/i/arrow-down-sign-to-navigate.svg" alt=""></div>
				</div>
				

				<div class="user-nav">
					<nav class="user-nav__menu">
						<ul>
							<li><a href="https://push.unu.ru">Добавить раздачу</a></li>
							<li><a href="https://push.unu.ru/tasks">Мои раздачи</a></li>
							<li><a href="https://push.unu.ru/help">Помощь</a></li>
						</ul> 
					</nav>
				</div>

			</div>

			<div class="bt-menu">
				<i></i>
				<i></i>
				<i></i>
			</div>
		</div>
	</div>
</header>

<div class="site-body">
	<div class="container">