<?php
// require __DIR__.'/../vendor/autoload.php';

// use Monolog\Handler\StreamHandler;
// use Monolog\Level;
// use Monolog\Logger;
// use Src\Config\Config;
// use Xup6m6fu04\NewebPay\NewebPay;

// // 顯示錯誤訊息
// ini_set('display_errors', '1');
// error_reporting(E_ALL);

// /**
//  * 藍新回調記錄到 Log
//  */
// $config = Config::get();

// $newebPay = new NewebPay($config);
// $post = $newebPay->decodeFromRequest($_POST);

// $log = new Logger('logger');
// $log->pushHandler(new StreamHandler('Log/api.log', Level::Debug));

// // add records to the log
// $log->debug("callback: ", $post);
// $log->debug(file_get_contents('php://input'));

// echo "SUCCESS";



function wh_log($log_msg)
{
    $log_filename = "../log";
    if (!file_exists($log_filename)) 
    {
        // create directory/folder uploads.
        mkdir($log_filename, 0777, true);
    }
    $log_file_data = $log_filename.'/log_members_' . date('d-M-Y') . '.log';
    // if you don't add `FILE_APPEND`, the file will be erased each time you add a log
    file_put_contents($log_file_data, date("Y-m-d H:i:s")."  ------  ".$log_msg . "\n", FILE_APPEND);
} 

/*HashKey AES 解密 */
function create_aes_decrypt($parameter = "", $key = "", $iv = "") {
    return strippadding(openssl_decrypt(hex2bin($parameter),'AES-256-CBC', $key, OPENSSL_RAW_DATA|OPENSSL_ZERO_PADDING, $iv));
}

$TradeInfo = file_get_contents("php://input");

$arr = mb_split("&",$TradeInfo);
$get_aes = str_replace("TradeInfo=","",$arr[2]);

$data = create_aes_decrypt($get_aes,$hashKey,$hashIV);
$json = json_decode($data);
wh_log($json);
if($json->Status == "SUCCESS"){
	
}