<?

require $_SERVER["DOCUMENT_ROOT"] . '/vendor/autoload.php';

use Minter\MinterAPI;
use Minter\SDK\MinterTx;
use Minter\SDK\MinterWallet;
use Minter\SDK\MinterCoins\MinterSendCoinTx;
use GuzzleHttp\Exception\RequestException;

$nodeUrl = 'https://api.minter.one'; // minter node api
$api = new MinterAPI($nodeUrl);

$token = ""; // telegram token
$api_url = "https://api.telegram.org/bot";
$vk_token = "";
$sys_perc = 5;

function api_query($method=null, $params=null) {
    global $token, $api_url;
    
    $query = "$api_url$token/$method";
    
    $ch = curl_init($query);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $params); 
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('content-type: multipart/form-data'));
    $result = curl_exec($ch);
    curl_close($ch);    
    //print_r($result);
    return $result;
}

function bot_sendmessage($chat_id, $message, $markup=null) {
    $response = array(
        'chat_id' => $chat_id,
        'disable_web_page_preview'=>1,
        'parse_mode'=>'HTML',
        'text' => $message,
        'reply_markup' => $markup
    );  
    api_query("sendMessage", $response);
}

function bot_welcome_message($chat_id) {
    global $linksql;
    $res = mysqli_query($linksql, "SELECT * FROM users WHERE chat_id='$chat_id'");
    if (mysqli_num_rows($res)==0) {
        mysqli_query($linksql, "INSERT INTO users SET chat_id='$chat_id', register_date=NOW()");
    }
    bot_sendmessage($chat_id, "Привет, давайте начнём!");
}

function bot_show_menu($chat_id, $text=null) {

    $button1 = array('text' => '➕ Новая раздача');
    $button2 = array('text' => '📋 Мои раздачи');
    $button3 = array('text' => '🆘 Помощь');

    $keyboard = array('keyboard' => array(array($button1, $button2), array($button3)),'one_time_keyboard' => true, 'resize_keyboard' => true);
    $replyMarkup = json_encode($keyboard); 

    if (empty($text)) {
        $text = "Выберите дальнейшие действия в меню:";
    }

    bot_sendmessage($chat_id, $text, $replyMarkup);
}

function bot_task_add($chat_id) {
    bot_sendmessage($chat_id, "Создаём новую раздачу наград за подписку.");
    bot_sendmessage($chat_id, "<strong>Пришлите ссылку на канал или группу</strong> куда вы будете привлекать участников.\n<i>Например: @unuru_ann или https://t.me/unuru_ann</i>");
    save_state($chat_id, "add-task");
}

function save_state($chat_id, $state, $data=null) {
    global $linksql;
    mysqli_query($linksql, "INSERT INTO states SET state='$state', chat_id='$chat_id', date=NOW(), data='$data'");
    $id = mysqli_insert_id($linksql);
    return $id;
}

function clear_states($chat_id) {
    global $linksql;
    mysqli_query($linksql, "DELETE FROM states WHERE chat_id='$chat_id'");
}

function get_state($chat_id) {
    global $linksql;
    $res = mysqli_query($linksql, "SELECT * FROM states WHERE chat_id='$chat_id' ORDER BY id DESC");
    if (mysqli_num_rows($res)>0) {
        $data = mysqli_fetch_assoc($res);
        return $data["state"];
    } else {
        return "start";
    }
}

function get_state_data($chat_id, $state) {
    global $linksql;
    $res = mysqli_query($linksql, "SELECT * FROM states WHERE chat_id='$chat_id' AND state='$state' ORDER BY id DESC");
    if (mysqli_num_rows($res)>0) {
        $data = mysqli_fetch_assoc($res);
        return $data["data"];
    } 
}

function clear_tg_link($link) {
    trim ($link);

    $link = str_replace("https://t.me/", "", $link);
    $link = str_replace("http://t.me/", "", $link);
    $link = str_replace("https://telegram.me/", "", $link);

    if (substr($link, 0, 1)!="@") {
        $link = "@" . $link;
    }

    return $link;
}

function clear_youtube_link($link) {
    trim ($link);
    preg_match("/^(?:http(?:s)?:\/\/)?(?:www\.)?(?:m\.)?(?:youtu\.be\/|youtube\.com\/(?:(?:watch)?\?(?:.*&)?v(?:i)?=|(?:embed|v|vi|user)\/))([^\?&\"'>]+)/", $link, $matches);

    if (isset($matches[1])) {
        return $matches[1];
    } else {
        return false;
    }
}

function clear_vimeo_link($link) {
    trim ($link);
    preg_match('#https?://vimeo\.com?/(\d+)#i', $link, $matches);

    if (isset($matches[1])) {
        return $matches[1];
    } else {
        return false;
    }
}


function clear_twitch_link($link) {
    trim ($link);
    $parsed = parse_url($link);

    print_r($parsed);

    if (!isset($parsed["host"])) {
        return false;
    } else {
        if ($parsed["host"]!="twitch.tv" AND $parsed["host"]!="www.twitch.tv") {
            return false;
        } else {
            if (!isset($parsed["path"]) OR $parsed["path"]=="/") {
                return false;
            } else {
                $channel = substr($parsed["path"], 1);
                if (strpos($channel, "/") !== false) {
                    $channel = substr($channel, 0, strpos($channel, "/"));
                }

                if (!preg_match("/^[a-zA-Z0-9_-]+$/i",$channel)) {
                    return false; 
                }
                    
                return $channel;
            }
        }
    }
}

function check_bot_access($channel, $chat_id=458414) {
    global $token, $api_url;

    $query = $api_url . $token . "/getChatMember?chat_id=$channel&user_id=$chat_id";
        
    $ch = curl_init($query);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HEADER, false);
    $result = curl_exec($ch);
    curl_close($ch);    
    $result = json_decode($result); 

    if (isset($result->ok) AND $result->ok==1) {
        return "true";
    } else {
        return "false";
    }
}

function bot_check_bot_access($chat_id) {
    global $token, $api_url;
    $channel = get_state_data($chat_id, "add-task-step2");

    if (check_bot_access($channel, $chat_id)=="true") {
        bot_sendmessage($chat_id, "👍 Спасибо, есть доступ.");
        bot_sendmessage($chat_id, "<strong>Какой монетой сети Minter вы хотите награждать новых подписчиков?</strong> <a href='https://telegra.ph/CHto-takoe-monety-seti-Minter-04-06'>Что за монеты Minter?</a>\n<i>Например: BIP, UNUCOIN или любая другая.</i>");
        save_state($chat_id, "add-task-step3", $channel);
    } else {
        $inline_button1 = array("text"=>"✅ Готово","callback_data"=>'check-bot-access');
        $inline_button2 = array("text"=>"Указать другой канал","callback_data"=>'change-channel');
        $inline_keyboard = [[$inline_button1],[$inline_button2]];
        $keyboard=array("inline_keyboard"=>$inline_keyboard);
        $replyMarkup = json_encode($keyboard); 
        bot_sendmessage($chat_id, "Вы не добавили @unu_push_bot в список участников $channel. Пожалуйста, сделайте это.", $replyMarkup);
    } 

}

function check_tg_link($link) {
    global $token, $api_url;
    
    $query = $api_url . $token . "/getChat?chat_id=" . $link;
        
    $ch = curl_init($query);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HEADER, false);
    $result = curl_exec($ch);
    curl_close($ch);    

    $result = json_decode($result); 
    $resp = $result->ok;

    if ($resp==1) {
        return true;
    } else {
        return false;
    }
}

function check_is_tg_member($chat_id, $chat) {
    global $token, $api_url;
    
    $query = $api_url . $token . "/getChatMember?chat_id=$chat&user_id=$chat_id";
        
    $ch = curl_init($query);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HEADER, false);
    $result = curl_exec($ch);
    curl_close($ch);    

    $result = json_decode($result); 

    if ($result->ok==1) {
        $status = $result->result->status;

        if ($status=="member" OR $status=="administrator" OR $status=="creator") {
            // подписан
            return true;
        } else {
            // не подписан
            return false;
        }   
    } else {
        return "error";
    }

}

function get_user_info($chat_id) {
    global $linksql;
    $res = mysqli_query($linksql, "SELECT * FROM users WHERE chat_id='$chat_id' LIMIT 1");
    $data = mysqli_fetch_assoc($res);
    return $data;
}

function check_minter_coin($coin) {
    global $api;

    $coin = strtoupper(trim($coin)); 

    if ($coin=="BIP") {
        return true;
        exit();
    }
    
    try {
    // success response
        $response = $api->getCoinInfo($coin);
        return true;
    } catch(RequestException $exception) {
        return false;            
    }
}

function get_minter_wallet($task_id) {
    global $api, $linksql;

    $res = mysqli_query($linksql, "SELECT wallet FROM tasks WHERE id='$task_id'");
    $data = mysqli_fetch_assoc($res);

    if (!empty($data["wallet"])) {
        return $data["wallet"];
    } else {
        $wallet = MinterWallet::create();
        $address = $wallet['address'];
        $mnemonic = $wallet['mnemonic'];
        mysqli_query($linksql, "UPDATE tasks SET wallet='$address', mnemonic='$mnemonic' WHERE id='$task_id'");
        return $address;
    }
}

function check_minter_balance($task_id) {
    global $api, $linksql;

    $res = mysqli_query($linksql, "SELECT * FROM tasks WHERE id='$task_id'");
    $task = mysqli_fetch_assoc($res);

    $result = $api->getBalance($task["wallet"]);
    $coins = $result->result->balance;
    $coins = (array)$coins;

    $have_coin = 0;
    foreach($coins as $key =>$value) {
        $value = $value / 1000000000000000000;
        if ($key==$task["coin"]) {
            $have_coin = 1;
            if ($task["need_coins"]<=$value) {
                return 0;
            } else {
                $need_new = round($task["need_coins"] - $value, 4);
                return $need_new;
            }
        }
    }

    if ($have_coin==0) {
        return $task["need_coins"];
    }          

}

function check_task_balance($chat_id, $task_id) {
    global $linksql;

    $res = mysqli_query($linksql, "SELECT * FROM tasks WHERE id='$task_id'");
    $task = mysqli_fetch_assoc($res);

    $need = check_minter_balance($task_id);

    if ($need==0) {
        bot_sendmessage($chat_id, "👍 Перевод получен.");
        save_state($chat_id, "add-task-finish", $task_id);
        mysqli_query($linksql, "UPDATE tasks SET active='1' WHERE id='$task_id'");
        share_link($chat_id, $task_id);
        exit();
    } else {
        $inline_button1 = array("text"=>"✅ Готово","callback_data"=>'check-payment');
        $inline_keyboard = [[$inline_button1]];
        $keyboard=array("inline_keyboard"=>$inline_keyboard);
        $replyMarkup = json_encode($keyboard); 
        bot_sendmessage($chat_id, "Недостаточно средств на балансе. Необходимо пополнить кошелёк на $need " . $task["coin"]. "\n\nПожалуйста, сделайте перевод на <code>".$task["wallet"]."</code>\n\nПосле того, как сделаете перевод, нажмите на кнопку Готово.", $replyMarkup);
        exit();
    }

}

function get_share_link($task_id) {
    global $linksql;

    $res = mysqli_query($linksql, "SELECT * FROM tasks WHERE id='$task_id'");
    $task = mysqli_fetch_assoc($res);

    if ($task["service"]=="telegram") {
        $link = "https://telegram.me/unu_push_bot?start=$task_id";
    } else {
        if (empty($task["hash"])) {
            $hash = generateCode(5);
        } else {
            $hash = $task["hash"];
        }
        $link = "https://push.unu.ru/push/" . $hash;
        mysqli_query($linksql, "UPDATE tasks SET hash='$hash' WHERE id='$task_id'");
    }

    return $link;
}

function share_link($chat_id, $task_id) {
    global $linksql;

    $res = mysqli_query($linksql, "SELECT * FROM tasks WHERE id='$task_id'");
    $task = mysqli_fetch_assoc($res);

    $link = get_share_link($task_id);
    $link_title = "Получите ".$task["coins_per_action"]." ".$task["coin"]." за подписку в Телеграм";

    $text = "Поделитесь ссылкой с теми, кого хотите поощрить за подписку. После перехода пользователя по ссылке ему будет предложено подписаться на " . $task["channel"] . ". В случае успешной подписки, ему будет отправлено вознаграждение " . $task["coins_per_action"] . " " . $task["coin"] . "\n\n" . "<strong>Ссылка:</strong> $link\n\nВы также можете поделиться ссылкой в соц. сетях:";

    $inline_button1 = array("text"=>"VK","url"=>'https://vk.com/share.php?url='.urlencode($link).'&title='.urlencode($link_title));
    $inline_button2 = array("text"=>"Facebook","url"=>'https://www.facebook.com/sharer.php?src=sp&u='.urlencode($link).'&title='.urlencode($link_title));
    $inline_button3 = array("text"=>"Twitter","url"=>'https://twitter.com/intent/tweet?url='.urlencode($link).'&text='.urlencode($link_title));
    $inline_button4 = array("text"=>"Reddit","url"=>'https://www.reddit.com/submit?url='.urlencode($link));

    $inline_keyboard = [[$inline_button1,$inline_button2],[$inline_button3,$inline_button4]];
    $keyboard=array("inline_keyboard"=>$inline_keyboard);
    $replyMarkup = json_encode($keyboard); 

    bot_sendmessage($chat_id, $text, $replyMarkup);
    bot_show_menu($chat_id, "Сделить за динамикой подписок можно во вкладке Мои раздачи.");
}

function check_already_paid($chat_id=null, $task_id, $vk_id=null, $user_id=null) {
    global $linksql;

    $res = mysqli_query($linksql, "SELECT * FROM tasks WHERE id='$task_id'");
    $task = mysqli_fetch_assoc($res);

    if (empty($vk_id) AND !empty($chat_id) AND empty($user_id)) {
        $res = mysqli_query($linksql, "SELECT id FROM users WHERE chat_id='$chat_id'");
        $user = mysqli_fetch_assoc($res);
        $user_id = $user["id"];
    }

    if (empty($chat_id) AND !empty($vk_id) AND empty($user_id)) {
        $res = mysqli_query($linksql, "SELECT id FROM users WHERE vk_id='$vk_id'");
        $user = mysqli_fetch_assoc($res);
        $user_id = $user["id"];
    }

    $already_paid = 0;
    $res = mysqli_query($linksql, "SELECT * FROM tasks WHERE channel='".$task["channel"]."'");
    while ($t = mysqli_fetch_array($res)) {
        $result = mysqli_query($linksql, "SELECT * FROM tasks_reports WHERE user_id='".$user_id."' AND task_id='".$t["id"]."' AND pay_link!='' LIMIT 1");
        if (mysqli_num_rows($result)>0) {
            $data = mysqli_fetch_assoc($result);
            $already_paid = 1;
            $old_link = $data["pay_link"];
        }
    }

    if ($already_paid==1) {
        return array("fail"=>1, "info"=>$old_link);
    } else {
        return array("fail"=>0, "info"=>"");
    }
} 

function minter_transfer($from, $to, $mnemonic, $amount, $coin) {
    global $api;

    $seed = MinterWallet::mnemonicToSeed($mnemonic);
    $privateKey = MinterWallet::seedToPrivateKey($seed);

    $nonce = $api->getNonce($from);

    $tx = new MinterTx([
        'nonce' => $nonce,
        'chainId' => MinterTx::MAINNET_CHAIN_ID, // or MinterTx::TESTNET_CHAIN_ID
        'gasPrice' => 1,
        'gasCoin' => $coin,
        'type' => MinterSendCoinTx::TYPE,
        'data' => [
            'coin' => $coin,
            'to' => $to,
            'value' => $amount
        ],
        'payload' => '',
        'serviceData' => '',
        'signatureType' => MinterTx::SIGNATURE_SINGLE_TYPE // or SIGNATURE_MULTI_TYPE
    ]);

    // Sign tx
    $tx = $tx->sign($privateKey);

    try {
        $response = $api->send($tx);
    } catch(RequestException $exception) {
        // handle error

        $message = $exception->getMessage();

        // error response in json
        $content = $exception->getResponse()
                    ->getBody()
                    ->getContents();

        // error response as array
        $error = json_decode($content, true);   

        //print_r($error);
    }

}


function pay($amount, $coin, $task_id, $user_id, $report_id=null) {
    global $linksql;

    $query = "https://push.money/api/push/create";

    $post_data = array("sender"=>"@unu_push_bot");

    $ch = curl_init($query);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    curl_setopt($ch, CURLOPT_HEADER, false);
    $result = curl_exec($ch);
    curl_close($ch);    
    $result = json_decode($result); 

    $address = $result->address;
    $link = $result->link_id;
    $link = "https://yyy.cash/push/" . $link;

    $res = mysqli_query($linksql, "SELECT * FROM tasks WHERE id='$task_id'");
    $task = mysqli_fetch_assoc($res);

    minter_transfer($task["wallet"], $address, $task["mnemonic"], $task["coins_per_action"], $task["coin"]);
    if (empty($report_id)) {
        mysqli_query($linksql, "INSERT INTO tasks_reports SET task_id='$task_id', date=NOW(), pay_link='$link', pay_wallet='$address', user_id='$user_id'");
    } else {
        mysqli_query($linksql, "UPDATE tasks_reports SET pay_link='$link', pay_wallet='$address' WHERE id='$report_id'");
    }
    return $link;
}

function convert($from, $to, $amount) {
    global $linksql, $api;
    $res = mysqli_query($linksql, "SELECT * FROM coin_info ORDER BY id DESC LIMIT 1");
    $bip = mysqli_fetch_assoc($res);

    if ($from=="BIP") {
        $bip_rate = $amount;
    } else {
        $response = $api->getCoinInfo($from);

        $coin_reserve = $response->result->reserve_balance;
        $coin_volume = $response->result->volume;
        $coin_crr = $response->result->crr;

        $coin_reserve = $coin_reserve / 1000000000000000000;
        $coin_volume = $coin_volume / 1000000000000000000;

        $coin_price_bip = $coin_reserve * (1 - pow((1 - 1 / $coin_volume), (100 / $coin_crr))); 

        $bip_rate = $amount * $coin_price_bip;
    }

    if ($to=="usd") {
        return round($bip_rate*$bip["bip_usd"], 3);
    } 

    if ($to=="rub") {
        return round($bip_rate*$bip["bip_rub"], 3);
    } 

    if ($to=="bip") {
        return $bip_rate;
    }

}

function get_minimal_reward($coin, $amount) {

    $inbip = convert($coin, "bip", $amount);

    if ($inbip<=0.02) {
        $min = round($amount*(0.02/$inbip), 3);
    } else {
        $min = $amount;
    }
    return $min;
}

function get_minter_coins() {
    $result = file_get_contents("https://explorer-api.minter.network/api/v1/coins");
    $result = json_decode($result);

    $coins = array();
    foreach ($result->data AS $coin) {	
		$coins[] = $coin->symbol;
	}

	asort($coins);
	return $coins;
}

function web_show_message($h1, $text) {
    global $loggedin, $loggedin_id, $linksql, $title, $keywords, $description, $global_page;

    $title = $h1;

    include $_SERVER["DOCUMENT_ROOT"]."/design_header.php";
    ?>
    <h1 class="page-title"><? print $h1; ?></h1>
    <div class="page">
        <? print $text; ?>
    </div>
    <?
    include $_SERVER["DOCUMENT_ROOT"]."/design_footer.php";
    exit();
}

function generateCode($length=6, $onlysmall=0) { 
    if (isset($onlysmall) AND $onlysmall==1) {
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
    } else {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPRQSTUVWXYZ0123456789"; 
    }

    $code = ""; 
    $clen = strlen($chars) - 1;   
    while (strlen($code) < $length) { 
        $code .= $chars[mt_rand(0,$clen)];   
    } 
    return $code; 
} 

function user_register() {
    global $linksql;

    $hash = md5(generateCode(10)); 
    mysqli_query($linksql, "INSERT INTO users SET user_hash='$hash', register_date=NOW(), user_ip='".get_user_ip()."'");
    $user_id = mysqli_insert_id($linksql);

    setcookie("push_id", $user_id, time()+60*60*24*30, "/", "push.unu.ru", NULL, 1);
    setcookie("push_hash", $hash, time()+60*60*24*30, "/", "push.unu.ru", NULL, 1);
    return $user_id;
}

function clear_vk_link($link) {
    $link = str_replace("https://vk.com/", "", $link);
    $link = str_replace("https://m.vk.com/", "", $link);
    $link = str_replace("http://vk.com/", "", $link);
    $link = str_replace("http://m.vk.com/", "", $link);
    $link = trim($link);
    return $link;
}

function vk_check_group_link($link) {
    global $vk_token;

    $error = "";
    $group_name = clear_vk_link($link);
    $resp = file_get_contents("http://api.vk.com/method/utils.resolveScreenName?screen_name=$group_name&access_token=$vk_token&v=5.74");
    if ($resp) {
        $resp = json_decode($resp);
        if (isset($resp->response->type)) {
            if ($resp->response->type!="group") {
                $error = "Указанная ссылка не ведёт на сообщество или публичную страницу VK.";
            }
        } else {
            $error = "Указанная ссылка не ведёт на сообщество или публичную страницу VK.";
        }
    } else {
        $error = "Произошла ошибка получения информации о сообществе для подписки. Пожалуйста, обратитесь в службу поддержки.";
    }

    if (empty($error)) {
        return array("result"=>"success", "group_id"=>$resp->response->object_id);
    } else {
        return array("result"=>"error", "message"=>$error);
    }

}

function vk_is_closed_group($group_id) {
    global $vk_token;

    $error = "";
    $is_closed = 1;

    $resp = file_get_contents("https://api.vk.com/method/groups.getById?group_id=$group_id&access_token=$vk_token&v=5.74");
    if ($resp) {
        $resp = json_decode($resp);
        if (isset($resp->response[0]->is_closed)) {
            $is_closed = $resp->response[0]->is_closed;
        } else {
            $error = "Произошла ошибка при определении приватности группы";
        }
    } else {
        $error = "Произошла ошибка при определении приватности группы";
    }

    if (!empty($error)) {
        return array("result"=>"error", "message"=>$error);
    } else {
        return array("result"=>"success", "is_closed"=>$is_closed);
    }
}

function vk_check_is_member($group_id, $user_id) {
    global $vk_token;

    $resp = file_get_contents('https://api.vk.com/method/groups.isMember?group_id='.$group_id.'&user_id='.$user_id.'&extended=0&access_token='.$vk_token.'&v=5.74');
    if ($resp) {
        $resp = json_decode($resp);
        if (!isset($resp->response)) {
            $error = "Прошла ошибка";
        }
    } else {
        $error = "Произошла ошибка получения информации о подписке";
    }
    if (!empty($error)) {
        return array("result"=>"error", "message"=>$error);
    } else {
        return array("result"=>"success", "is_member"=>$resp->response);
    }
}

function get_user_ip() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])){
        //check ip from share internet
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    }elseif(!empty($_SERVER['HTTP_X_FORWARDED_FOR'])){
        //to check ip is pass from proxy
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
return $ip;
}

function check_google_captcha($post) {
    $secretKey = "";
    $responseKey = $post;
    $userIP = get_user_ip();
    $url = "https://www.google.com/recaptcha/api/siteverify?secret=$secretKey&response=$responseKey&remoteip=$userIP";
    $response = file_get_contents($url);
    $res = json_decode($response);

    if ($res->success) {
        return true;
    } else {
        return false;
    }
}

function check_youtube_id($id) {
    $key = "";
    $query = "https://www.googleapis.com/youtube/v3/videos?part=id&id=$id&key=".$key;

    $ch = curl_init($query);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HEADER, false);
    $result = curl_exec($ch);
    curl_close($ch);    
    $result = json_decode($result); 

    if ($result->pageInfo->totalResults==0) {
        return false;
    } else {
        return true;
    }

}

function check_vimeo_id($id) {

    $query = "https://vimeo.com/api/oembed.json?url=https://vimeo.com/$id";

    $ch = curl_init($query);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HEADER, false);
    $result = curl_exec($ch);
    curl_close($ch);    

    if ($result=="404 Not Found") {
        return false;
    } else {
        return true;
    }

}
?>