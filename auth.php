<?

if (isset($_COOKIE['push_id']) and isset($_COOKIE['push_hash'])) {    
    $query = mysqli_query($linksql, "SELECT * FROM users WHERE id = '".intval($_COOKIE['push_id'])."' LIMIT 1"); 
    if (mysqli_num_rows($query)!=0) {
        $loggedin_data = mysqli_fetch_assoc($query); 
    
        if(($loggedin_data['user_hash'] !== $_COOKIE['push_hash']) or ($loggedin_data['id'] !== $_COOKIE['push_id'])) { 
            setcookie("push_id", "", time() - 3600*24*30*12, "/"); 
            setcookie("push_hash", "", time() - 3600*24*30*12, "/"); 
        } else {
            $loggedin = 1;
            $loggedin_id = $loggedin_data["id"];
        }
    } else {
        $loggedin = 2;
    } 
} else { 
   $loggedin = 2;
} 

header("X-Frame-Options:sameorigin"); 
header("Set-Cookie: name=value; httpOnly" );

?>