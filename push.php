<?

include $_SERVER["DOCUMENT_ROOT"] . "/include/db_connect.php";
include $_SERVER["DOCUMENT_ROOT"] . "/auth.php";
include $_SERVER["DOCUMENT_ROOT"] . "/include/functions.php";

if (isset($_GET['hash'])) {
	$hash = $_GET['hash'];
}

if (isset($_POST['hash'])) {
	$hash = $_POST['hash'];
}

if (!preg_match("/^[a-zA-Z0-9]+$/i",$hash)) {
    print "Некорректная ссылка"; exit();
}

$global_page = "push";

$res = mysqli_query($linksql, "SELECT * FROM tasks WHERE hash='".mysqli_real_escape_string($linksql, $hash)."' AND active='1'");
if (mysqli_num_rows($res)==0) {
	web_show_message("Ошибка", "<p>Раздача не найдена. Возможно, автор удалил её.</p>");
}
$task = mysqli_fetch_assoc($res);

$need_check_reports = 0;
if ($loggedin==1) {
	$res = mysqli_query($linksql, "SELECT * FROM tasks_reports WHERE task_id='".$task["id"]."' AND user_id='$loggedin_id'");
	if (mysqli_num_rows($res)==0) {
		$need_check_reports = 1;
	}
} else {
	$need_check_reports = 1;
}

if ($need_check_reports==1) {
	mysqli_query($linksql, "DELETE FROM tasks_reports WHERE date_upd<='".date("Y-m-d H:i:s", strtotime("-5 minutes"))."' AND date_upd!='0000-00-00 00:00:00' AND pay_link=''");
	$res = mysqli_query($linksql, "SELECT count(1) AS done FROM tasks_reports WHERE task_id='".$task["id"]."'");
	$data = mysqli_fetch_assoc($res);

	if ($data["done"]>=$task["task_limit"]) {
		web_show_message("Чуть-чуть не успели 😥", "<p>Эта раздача уже завершена.</p>");
	}
}

if ($task["task_type"]=="followers") {
	$title = "Получите " . $task["coins_per_action"] . " " . $task["coin"] . " за подписку";
	$h1 = "Получите " . $task["coins_per_action"] . " " . $task["coin"] . " (≈".convert($task["coin"], "rub", $task["coins_per_action"])." руб) за подписку";
} else if ($task["task_type"]=="view") {
	$title = "Получите " . $task["coins_per_action"] . " " . $task["coin"] . " за просмотр видео";
	$h1 = "Получите " . $task["coins_per_action"] . " " . $task["coin"] . " (≈".convert($task["coin"], "rub", $task["coins_per_action"])." руб) за просмотр видео";
	if ($task["service"]=="twitch") {
		$title = "Получите " . $task["coins_per_action"] . " " . $task["coin"] . " за каждую минуту просмотра";
		$h1 = "Получите " . $task["coins_per_action"] . " " . $task["coin"] . " (≈".convert($task["coin"], "rub", $task["coins_per_action"])." руб) за каждую минуту просмотра стрима";
	}
} else {
	$title = "Получите " . $task["coins_per_action"] . " " . $task["coin"];
	$h1 = "Получите " . $task["coins_per_action"] . " " . $task["coin"] . " (≈".convert($task["coin"], "rub", $task["coins_per_action"])." руб) за простое действие";
}


if ($task["task_type"]=="followers") {
	if ($task["service"]=="vk") {

		// Параметры приложения
		$clientId     = '7399525'; // ID приложения
		$clientSecret = '01qyWIrApXk3OdtZ5hgt'; // Защищённый ключ
		$redirectUri  = 'https://push.unu.ru/push/'.$hash; // Адрес, на который будет переадресован пользователь после прохождения авторизации

		// Формируем ссылку для авторизации
		$params = array(
			'client_id'     => $clientId,
			'redirect_uri'  => $redirectUri,
			'response_type' => 'code',
			'v'             => '5.74', // (обязательный параметр) версия API, которую Вы используете https://vk.com/dev/versions
			'scope'         => 'offline,friends',
		);

		if (isset($_GET['code'])) {

			$params = array(
				'client_id'     => $clientId,
				'client_secret' => $clientSecret,
				'code'          => $_GET['code'],
				'redirect_uri'  => $redirectUri
			);
				
			$ch = curl_init(); 
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET'); 
			curl_setopt($ch, CURLOPT_URL, 'https://oauth.vk.com/access_token?' . http_build_query($params)); 
			$content = curl_exec($ch);
			curl_close($ch);
		 
			$response = json_decode($content);
			
			if (isset($response->error)) {
				header("Location: https://push.unu.ru/push/$hash");
				exit();
			}
			
			$vk_user_id = $response->user_id; // ID авторизовавшегося пользователя
			$token = $response->access_token; // Токен

			$res = vk_check_group_link($task["channel"]);
			$group_id = $res["group_id"];

			$res = vk_check_is_member($group_id, $vk_user_id);

			if ($res["result"]=="success") {
				if ($res["is_member"]==1) {

					if ($loggedin!=1) {
						$loggedin_id = user_register();
						mysqli_query($linksql, "UPDATE users SET vk_id='$vk_user_id' WHERE id='$loggedin_id'");
					}

					$res = check_already_paid(null, $task["id"], $vk_user_id);
					
					if ($res["fail"]==1) {
						$error = "Вам уже было отправлено вознаграждение за подписку на " . $task["channel"]. ". Если вы не получили вознаграждение ранее, перейдите по ссылке: <a href='" . $res["info"] . "'>" . $res["info"] . "</a>";
					} else {
						$link = pay($task["coins_per_action"], $task["coin"], $task["id"], $loggedin_id);
						web_show_message("Спасибо, заберите награду!", "<p>Для того, чтобы получить вознаграждение, перейдите по ссылке:</p><p><a href='$link' style='font-size: 18px'>$link</a></p>");
					}
				}
			} else {
				$error = "Вы не подписались";
			}

		}


		$vk_link = 'https://oauth.vk.com/authorize?' . http_build_query($params);

		$text = '<ol class="task_list">
				<li>Подпишитесь на эту страницу в VK: <a href="'.$task["channel"].'" target="_blank">'.$task["channel"].'</a></li>
				<li>После подписки нажмите на кнопку и получите вознаграждение</li>
			</ol>
			<button class="sm-bt-purle" onclick="location.href=\''.$vk_link.'\'" style="max-width: 250px">Проверить подписку</button>';

		if (!empty($error)) {
			$text = '<div class="form-user__error">'.$error.'</div>' . $text;
		}
			
		web_show_message($h1, $text);
	}	
	
}

if ($task["task_type"]=="manual") {
	

	if ($loggedin!=1) {
		$loggedin_id = user_register();
		$loggedin = 1;
	} 

	$res = check_already_paid(null, $task["id"], null, $loggedin_id); 
	if ($res["fail"]==1) {
		$link = $res["info"];
		web_show_message("Заберите награду", "<p>Для того, чтобы получить вознаграждение, перейдите по ссылке:</p><p><a href='$link' style='font-size: 18px'>$link</a></p>");
	}
	
	include "design_header.php";
	?>
	<h1 class="page-title"><? print $h1; ?></h1>
	<div class="page">
		<p>Выполните задание, а затем нажмите на кнопку Получить награду</p>
		<h3>Текст задания</h3>
		<p>Ссылка: <a href='<? print $task["channel"]; ?>' target='_blank'><? print $task["channel"]; ?></a></p>
		<p><? print $task["task_text"]; ?></p>
		<button class='sm-bt-purle' onclick='$("#modal").modal();' style='max-width: 250px'>Получить награду</button>
	</div>
	<div id='modal' style='display:none'>
		<form method='post' id='pass_form'>
			<div id='form_error'></div>
			<p>Введите пароль для получения награды и подтвердите, что вы не робот</p>
			<p><strong>Пароль:</strong><br><input type='text' name='pass' style='width: 100%; padding: 5px; border: 1px solid #00003c; font-size: 16px; border-radius: 3px' /></p>
			<p><div class='g-recaptcha' data-sitekey='6LcokbkUAAAAAAUL9EFh9KThJcDhyvz582qLeWYf'></div></p>
			<p><button class='sm-bt-purle' type='submit' onclick='send_form(); return false;'>Отправить</button></p>
			<input type='hidden' name='hash' value='<? print $hash; ?>' />
		</form>
	</div>
	<script>
		function send_form() {
			var _formData = $("#pass_form").serialize();
			_formData['g-recaptcha-response'] = grecaptcha.getResponse();

			$.ajax({
  			"type":   "post",
  			"url" :   "https://push.unu.ru/include/task_password.php",
  			"data": _formData,
  			"success": function (result) {
  				grecaptcha.reset();
  				if (result == "done") {
  					window.location.reload();
  				} else {
  					alert(result);
  				}
  			}
  			});
		}
	</script>
	<?
	include "design_footer.php";
	exit();
}

if ($task["task_type"]=="view") {

	if ($loggedin!=1) {
		$loggedin_id = user_register();
		$loggedin = 1;
	} 

	$res = check_already_paid(null, $task["id"], null, $loggedin_id); 
	if ($res["fail"]==1) {
		$link = $res["info"];
		web_show_message("Заберите награду", "<p>Для того, чтобы получить вознаграждение, перейдите по ссылке:</p><p><a href='$link' style='font-size: 18px'>$link</a></p>");
	}

	$res = mysqli_query($linksql, "SELECT * FROM tasks_reports WHERE task_id='".$task["id"]."' AND user_id='$loggedin_id' ORDER BY id ASC LIMIT 1");
	if (mysqli_num_rows($res)>0) {
		$report = mysqli_fetch_assoc($res);
		$report_id = $report["id"];
	} else {
		$report_id = "";
	}

	if ($task["service"]=="youtube") {
		
		include "design_header.php";

		?>
		<h1 class="page-title"><? print $h1; ?></h1>
	    <div class="page">
	    	<ol class="task_list">
				<li>Полностью посмотрите видео, размещённое ниже, не покидая эту страницу</li>
				<li>Получите награду сразу после завершения просмотра</li>
			</ol>
	    	<div id="player"></div>

	    	<script>
	    		function go_submit() {
			        var Forma = document.getElementById("robot_form");
			        Forma.submit(); 
			    }
		    </script>

			<div id="modal" style="font-size: 20px; padding: 20px; display:none;">
		    	<p>Подтвердите, что вы не робот:</p>
				<form method="post" action="https://push.unu.ru/include/view_done.php" id="robot_form">
					<div class="g-recaptcha" data-sitekey="6LcokbkUAAAAAAUL9EFh9KThJcDhyvz582qLeWYf" data-callback="go_submit"></div>
		    		<input type="hidden" name="hash" value="<? print $hash; ?>" />
		    		<input type="hidden" name="report_id" value="<? if (!empty($report_id)) { print $report_id; } ?>" id="report_input" />
				</form>
			</div>


	    	<script src="https://www.youtube.com/player_api"></script>
			<script>

			    // create youtube player
			    var player;
			    var duration;
			    var _Seconds = 0;
			    var sec = 0;
			    var timer_status = "pause";
			    var active_window = 1;
			    
			    function onYouTubePlayerAPIReady() {
			        player = new YT.Player('player', {
			          height: '390',
			          width: '100%',
			          videoId: '<? print clear_youtube_link($task["channel"]); ?>',
			          events: {
			            'onReady': onPlayerReady,
			            'onStateChange': onPlayerStateChange
			          }
			        });
			    }

			    // autoplay video
			    function onPlayerReady(event) {
			        event.target.playVideo();
			        duration = player.getDuration();
			    }

			    // when video ends
			    function onPlayerStateChange(event) {  
			    	if(event.data === 1) { 
			        	// началось воспроизведение видео, включаем таймер
			          timer_status = "run";
			        }
			        
			        if(event.data === 2) { 
			        	// видео на паузе, ставим таймер на паузу
			          timer_status = "pause";
			        }
			        
			        if(event.data === 3) { 
			        	// буферизация видео, ставим таймер на паузу
			           timer_status = "pause";
			        }
			        
			        if(event.data === -1) { 
			        	// воспроизведение видео не началось. таймер на паузу
			           timer_status = "pause";
			        }
			    	
			        if(event.data === 0) {       
			        		// просмотр завершён. проверяем значение таймера. если таймер больше или равен duration, показываем капчу
			        	timer_status = "pause";
			        	
			    		if(_Seconds >= (duration-1))
			    		{
			    			$("#modal").modal({
						  		fadeDuration: 100
							});
			    		}
			    		else {
			    			document.getElementById('player').style.display='none';
				    		alert("Необходимо посмотреть видео полностью, без перемотки. Обновите страницу и сделайте это.");
				    		window.location.reload();
			    		}
			        }
			    }

			  	function start()
			    {

			    	int = setInterval(function() { // запускаем интервал
			  	  	  if (timer_status == "run") {
			  	  	    _Seconds++; 
			  	  	    sec++;
			  	  	    if (sec == 60) {
			  	  	    	sec = 0;
			  	  	    	report_id = $('input[id=report_input]').val();
				          	$.ajax({
				  			"type":   "post",
				  			"url" :   "https://push.unu.ru/include/view_start.php",
				  			"data": {hash: "<? print $hash; ?>", report: report_id},
				  			"success": function (result) {
				  				if (result == "error") {
				  					window.location.reload();
				  				}
				  			}
				  			});
			  	  	    }
			  	  	  }
			  	  	}, 1000);
			    }

			  	start();

			  	  <?
				  if (empty($report_id)) {
				  ?>
				  $.ajax({
				  			"type":   "post",
				  			"url" :   "https://push.unu.ru/include/view_start.php",
				  			"data": {hash: "<? print $hash; ?>"},
				  			"success": function (result) {
				  	 			report_id = result;
				  	 			document.getElementById("report_input").value = report_id;
				  			}
				  		});
				  <?
				  }
				  ?>
			</script>
	    </div>
		<?			
		include "design_footer.php";
		exit();
	}

	if ($task["service"]=="vimeo") {
		
		include "design_header.php";

		?>
		<h1 class="page-title"><? print $h1; ?></h1>
	    <div class="page">
	    	<ol class="task_list">
				<li>Полностью посмотрите видео, размещённое ниже, не покидая эту страницу</li>
				<li>Получите награду сразу после завершения просмотра</li>
			</ol>
			<div class='embed-container'>
	    	<div id="myVideo"></div>
	    	</div>
	    	<style>
	    		.embed-container {
  position: relative;
  padding-bottom: 56.25%;
  height: 0;
  overflow: hidden;
  max-width: 100%;
}

.embed-container iframe,
.embed-container object,
.embed-container embed {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
}
	    	</style>
	    	<script>
	    		function go_submit() {
			        var Forma = document.getElementById("robot_form");
			        Forma.submit(); 
			    }
		    </script>

			<div id="modal" style="font-size: 20px; padding: 20px; display:none;">
		    	<p>Подтвердите, что вы не робот:</p>
				<form method="post" action="https://push.unu.ru/include/view_done.php" id="robot_form">
					<div class="g-recaptcha" data-sitekey="6LcokbkUAAAAAAUL9EFh9KThJcDhyvz582qLeWYf" data-callback="go_submit"></div>
		    		<input type="hidden" name="hash" value="<? print $hash; ?>" />
		    		<input type="hidden" name="report_id" value="<? if (!empty($report_id)) { print $report_id; } ?>" id="report_input" />
				</form>
			</div>

			<script src="https://player.vimeo.com/api/player.js"></script>
			<script>
			  var options = {
			    url: "<? print $task["channel"]; ?>",
			    width: 600,
			  };

			  var videoPlayer = new Vimeo.Player('myVideo', options);
			  var duration;
			  var _Seconds = 0;
			  var sec = 0;
			  var timer_status = "pause";


			  videoPlayer.on('play', function() {
			    timer_status = "run";
			  });
			  
			  videoPlayer.on('pause', function() {
			    timer_status = "pause";
			  });
			  
			  videoPlayer.on('stop', function() {
			    timer_status = "pause";
			  });

			  videoPlayer.on('ended', function() {
			    timer_status = "pause";

	    		if(_Seconds >= (duration-1))
	    		{
	    			$("#modal").modal({
				  		fadeDuration: 100
					});
	    		}
	    		else {
	    			document.getElementById('myVideo').style.display='none';
		    		alert("Необходимо посмотреть видео полностью, без перемотки. Обновите страницу и сделайте это.");
		    		window.location.reload();
	    		}

			  });
			  
			  
			  videoPlayer.getDuration().then(function(dur) {
			    duration = dur;
			  });

			  function start()
			    {

			    	int = setInterval(function() { // запускаем интервал
			  	  	  if (timer_status == "run") {
			  	  	    _Seconds++; 
			  	  	    sec++;
			  	  	    if (sec == 60) {
			  	  	    	sec = 0;
			  	  	    	report_id = $('input[id=report_input]').val();
				          	$.ajax({
				  			"type":   "post",
				  			"url" :   "https://push.unu.ru/include/view_start.php",
				  			"data": {hash: "<? print $hash; ?>", report: report_id},
				  			"success": function (result) {
				  				if (result == "error") {
				  					window.location.reload();
				  				}
				  			}
				  			});
			  	  	    }
			  	  	  }
			  	  	}, 1000);
			    }

			  	start();

			  	  <?
				  if (empty($report_id)) {
				  ?>
				  $.ajax({
				  			"type":   "post",
				  			"url" :   "https://push.unu.ru/include/view_start.php",
				  			"data": {hash: "<? print $hash; ?>"},
				  			"success": function (result) {
				  	 			report_id = result;
				  	 			document.getElementById("report_input").value = report_id;
				  			}
				  		});
				  <?
				  }
				  ?>
			  
			</script>
	    </div>
		<?			
		include "design_footer.php";
		exit();
	}

	if ($task["service"]=="twitch") {
		
		include "design_header.php";

		$max_reward = round($task["task_limit_per_user"] * $task["coins_per_action"], 4);

		?>
		<h1 class="page-title"><? print $h1; ?></h1>
	    <div class="page">
	    	<ol class="task_list">
				<li>Включите воспроизведение и смотрите стрим. Не закрывайте вкладку.</li>
				<li>Получите <? print $task["coins_per_action"] . " " . $task["coin"]; ?> за каждую минуту просмотра. Минимальная сумма для получения выплаты награды &mdash; <? print get_minimal_reward($task["coin"], $task["coins_per_action"]). " " . $task["coin"]; ?>, максимальная &mdash; <? print $max_reward . " " . $task["coin"]; ?></li>
			</ol>
			<script>
	    		function go_submit() {
			        var Forma = document.getElementById("robot_form");
			        Forma.submit(); 
			    }
		    </script>

			<div id="modal" style="font-size: 20px; padding: 20px; display:none;">
		    	<p>Подтвердите, что вы не робот:</p>
				<form method="post" action="https://push.unu.ru/include/view_done.php" id="robot_form">
					<div class="g-recaptcha" data-sitekey="6LcokbkUAAAAAAUL9EFh9KThJcDhyvz582qLeWYf" data-callback="go_submit"></div>
		    		<input type="hidden" name="hash" value="<? print $hash; ?>" />
		    		<input type="hidden" name="coins" value="" id="form_coins" />
		    		<input type="hidden" name="report_id" value="<? if (!empty($report_id)) { print $report_id; } ?>" id="report_input" />
				</form>
			</div>

			<div class="timer" id="timer">
				Время: <span class="minutes">0</span> мин <span class="seconds">0</span> сек<br>
				Начислено: <span class="coins">0</span> <a href="javascript:void(0);" onclick="withdrawal();" style="text-decoration: underline; color: #FFF">Получить монеты</a>
			</div>
	    	
		    <script src= "https://player.twitch.tv/js/embed/v1.js"></script>

			<div id="twitch-player" style="margin-left: -24px; margin-right: -24px"></div>
			<script>
			    var total_seconds = 0;
			    var seconds = 0;
			    var minutes = 0;
				var timer_status = "pause";
			    var options = {width: '100%', height: '400', channel: 'brax', playsinline: 'false'};
			    var id = 'twitch-player';
			    var coin = '<? print $task["coin"]; ?>';
			    var coins_per_minute = <? print $task["coins_per_action"]; ?>;
			    var coins;
			    var min_to_withdrawal = <? print get_minimal_reward($task["coin"], $task["coins_per_action"]); ?>; 
			    var max_to_withdrawal = <? print $max_reward; ?>; 
			    var report_id;

			    var player = new Twitch.Player(id, options);
			    player.setVolume(1);

			    player.addEventListener(Twitch.Player.ONLINE, function () {
			        timer_status = "run";
			    });
			    player.addEventListener(Twitch.Player.OFFLINE, function () {
			        timer_status = "pause";
			    });
			    player.addEventListener(Twitch.Player.PAUSE, function () {
			        timer_status = "pause";
			    });
			    player.addEventListener(Twitch.Player.ENDED, function () {
			        timer_status = "pause";
			        alert("Трансляция уже завершилась");
			        document.getElementById('twitch-player').style.display='none';
			    });
			    player.addEventListener(Twitch.Player.PLAY, function () {
			        timer_status = "run";
			    });
			    player.addEventListener(Twitch.Player.READY, function () {
			        timer_status = "pause";
			    });
			    
			    function start()
			  	{

			      int = setInterval(function() { // запускаем интервал
			        if (timer_status == "run") {
			          total_seconds++; 
			          seconds++;

			          if (seconds == 60) {
			          	minutes++;
			          	seconds = 0;
			          	report_id = $('input[id=report_input]').val();
			          	$.ajax({
			  			"type":   "post",
			  			"url" :   "https://push.unu.ru/include/view_start.php",
			  			"data": {hash: "<? print $hash; ?>", report: report_id},
			  			"success": function (result) {
			  				if (result == "error") {
			  					window.location.reload();
			  				}
			  			}
			  			});
			          }

			          $('.seconds').text(seconds);
			          $('.minutes').text(minutes);
			          coins = Math.floor(total_seconds/5)*(coins_per_minute/12);
			          coins = truncated(coins);
			          $('.coins').text(coins+" "+coin);
			        }
			      }, 1000);
			  	}

			  	function truncated(num) {
				    return Math.trunc(num * 100) / 100;
				}

				function withdrawal() {
					if (coins<min_to_withdrawal) {
						timer_status = "pause";
						alert("Минимальная сумма для получения не набрана, продолжайте смотреть трансляцию.");
						timer_status = "run";
					} else {
						timer_status = "pause";
						document.getElementById("form_coins").value = coins;
						$("#modal").modal({
						  fadeDuration: 100
						});
					}
				}

			  start();
			  <?
			  if (empty($report_id)) {
			  ?>
			  $.ajax({
			  			"type":   "post",
			  			"url" :   "https://push.unu.ru/include/view_start.php",
			  			"data": {hash: "<? print $hash; ?>"},
			  			"success": function (result) {
			  	 			report_id = result;
			  	 			document.getElementById("report_input").value = report_id;
			  			}
			  		});
			  <?
			  }
			  ?>
			</script>

	    </div>
		<?			
		include "design_footer.php";
		exit();
	}
}
	
?>