<?

$mysql_host='';
$mysql_user='';
$mysql_pasd='';
$mysql_db='';

$linksql = mysqli_connect($mysql_host, $mysql_user, $mysql_pasd);

if (mysqli_connect_errno()) {    
    printf("Произошла ошибка. Приносим извинения, наши специалисты уже работают над решением проблемы: %s\n", mysqli_connect_error());
    exit();
}


$db_selected = mysqli_select_db($linksql, $mysql_db);
if (!$db_selected) {
    printf("Произошла ошибка. Приносим извинения, наши специалисты уже работают над решением проблемы: %s\n", mysqli_error());
    exit();
}

mysqli_query($linksql, "SET NAMES utf8");
  
?>