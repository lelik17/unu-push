 </div>
</div>

<footer class="footer">
	<div class="container">

		<div class="flex-row">

			<div class="footer-logo">
				<a href="https://push.unu.ru"><img src="https://unu.ru/i/unu-logo3-1.svg" alt=""></a>
			</div>

			<nav class="footer-nav flex-row">
				<ul>
					<li><a href="#">Добавить раздачу</a></li>
					<li><a href="#">Мои раздачи</a></li>
					<li><a href="#">Помощь</a></li>
				</ul>
			</nav>

		</div>

		<div class="footer-copy">
			© UNU <? print date("Y"); ?>. Сайт может содержать материалы для лиц старше 18 лет. <br/>
			<!--Используя сайт вы полностью и безоговорочно принимаете <a href="https://unu.ru/docs/oferta" rel="nofollow">оферту</a>.--> 
		</div>

	</div>	
</footer>
<script src="https://push.unu.ru/js/jquery.placeholder.min.js"></script>
<script src="https://push.unu.ru/js/jquery.toshowhide.js"></script>
<script src="https://push.unu.ru/js/jquery.maskedinput.js"></script>
<script src="https://push.unu.ru/js/slick.min.js"></script>
<script src="https://push.unu.ru/js/jquery.nice-select.min.js"></script>
<script src="https://push.unu.ru/js/main.js"></script>
<script src="https://push.unu.ru/js/blazy.min.js"></script>
<script>
	var bLazy = new Blazy({
  	//опции
	});
</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-modal/0.9.1/jquery.modal.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery-modal/0.9.1/jquery.modal.min.css" />
<link href="https://fonts.googleapis.com/css?family=PT+Sans:400,400i,700,700i&display=swap&subset=cyrillic-ext" rel="stylesheet"><link href="https://unu.ru/css/fonts.css" rel="stylesheet">
<link href="https://unu.ru/css/adapt.css" rel="stylesheet">
<?
// записываем fingerprint
if (isset($loggedin) AND $loggedin==1) {
	?>
	<script src='https://cdnjs.cloudflare.com/ajax/libs/fingerprintjs2/2.1.0/fingerprint2.min.js'></script>
	<script>
		if (window.requestIdleCallback) {
		    requestIdleCallback(function () {
		        Fingerprint2.get(function (components) {
		          var murmur = Fingerprint2.x64hash128(components.map(function (pair) { return pair.value }).join(), 31);
		          
		          $.post('https://push.unu.ru/include/fingerprint.php', 'hash=' + murmur, function(){
				  });	
		        })
		    })
		} else {
		    setTimeout(function () {
		        Fingerprint2.get(function (components) {
		          var murmur = Fingerprint2.x64hash128(components.map(function (pair) { return pair.value }).join(), 31);
		          $.post('https://push.unu.ru/include/fingerprint.php', 'hash=' + murmur, function(){
				  })
		        })  
		    }, 500)
		}
	</script>
	<?
}

if (isset($dop_foot)) {
	print $dop_foot;
}
?>
</body>
</html>