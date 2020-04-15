<?

include $_SERVER["DOCUMENT_ROOT"] . "/include/db_connect.php";

$resp = file_get_contents("https://api.coingecko.com/api/v3/simple/price?ids=bip&vs_currencies=usd");
$resp = json_decode($resp);
$bip_usd_rate = $resp->bip->usd;

$resp = file_get_contents("https://api.coingecko.com/api/v3/simple/price?ids=bip&vs_currencies=rub");
$resp = json_decode($resp);
$bip_rub_rate = $resp->bip->rub;

mysqli_query($linksql, "UPDATE coin_info SET bip_usd='$bip_usd_rate', bip_rub='$bip_rub_rate'");

?>