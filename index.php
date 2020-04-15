<?

$title = "UNU.push &mdash; награждайте ваших подписчиков!";
$global_page = "main";
include "design_header.php";
?>
<h1 class="page-title">Раздавайте награды за действия!</h1>

	<div class="page">
		<!--<img src="https://unu.ru/i/rotation.svg" style="float: left; width: 55px; height: auto; margin-right: 20px" />-->
		UNU.push позволяет в автоматическом режиме раздавать награды, имеющие реальную ценность, за полезные для вас действия. Например, вы можете назначить награду за подписку на вашу страничку в соц. сети. При этом вы сами определяете, какую награду и в каком размере дать пользователю.   
		<div class="clear-paym"></div>
	</div>

	<h2 class="page-title" style="font-size: 26px; margin-top: 20px">Создать раздачу наград</h2>
    
	<div class="order-list flex-row">
			<div class="order-list__item">
				<a href="#modal_follower" class="full-link" rel="modal:open"></a>
				<div class="order-list__item-icon">
					<img src="https://unu.ru/i/rotation.svg" alt="">
				</div>
				<p><span class="bold">За подписку</span> в социальных сетях</p>
			</div>
			<div class="order-list__item">
				<a href="#modal_view" class="full-link" rel="modal:open"></a>
				<div class="order-list__item-icon">
					<img src="https://unu.ru/i/video2.svg" alt="">
				</div>
				<p><span class="bold">За просмотр видео</span> <br/> или стрима</p>
			</div>
			<div class="order-list__item">
				<a href="help?question=manual" class="full-link"></a>
				<div class="order-list__item-icon">
					<img src="https://unu.ru/i/notes.svg" alt="">
				</div>
				<p> <span class="bold">Произвольная задача</span><br/>  всё остальное</p>
			</div>
	</div>

	<div class="platform">
		<h2 class="page-title" style="font-size: 26px">Или выберите сервис, с которым связано задание</h2>
		<div class="flex-row">
			<a href="add?service=telegram&type=followers" class="item-platform"><img src="https://unu.ru/i/telegram_logo.svg" alt=""></a>
			<a href="add?service=vk&type=followers" class="item-platform"><img src="https://unu.ru/i/VK.com-logo.svg" alt=""></a>
			<a href="add?service=youtube&type=view" class="item-platform"><img src="https://unu.ru/i/YouTube_Logo_2017.svg" alt=""></a>
			<a href="add?service=vimeo&type=view" class="item-platform"><img src="https://push.unu.ru/i/vimeo.svg" alt=""></a>
			<a href="add?service=twitch&type=view" class="item-platform"><img src="https://unu.ru/i/twitch.svg" alt=""></a>
			<!--<a href="help?question=manual" class="item-platform" style="font-size: 18px; padding-left: 15px">Произвольная задача</a>-->
		</div>
	</div>

	<div id="modal_follower" style="display:none; font-size: 18px;">
				<p>Подписчики в каком сервисе ваc интересуют?</p>
				<ul class="list" style="margin-left: 20px;">
					<li style="list-style: circle"><a href="add?service=telegram&type=followers">Telegram</a></li>
					<li style="list-style: circle"><a href="add?service=vk&type=followers">VK</a></li>
				</ul>
	</div>

	<div id="modal_view" style="display:none; font-size: 18px;">
				<p>Подписчики в каком сервисе ваc интересуют?</p>
				<ul class="list" style="margin-left: 20px;">
					<li style="list-style: circle"><a href="add?service=youtube&type=view">YouTube</a></li>
					<li style="list-style: circle"><a href="add?service=twitch&type=view">Twitch</a></li>
					<li style="list-style: circle"><a href="add?service=vimeo&type=view">Vimeo</a></li>
				</ul>
	</div>
<?
include "design_footer.php";

?>