<?php
// 華碩雲
$key="pthSRiJ6CEZisT4XHtfkzhczkhbwHgjp";
$iv="CXiNX6dQOHsJyIAP";
$mid="MS347402044";

$data1=http_build_query(array(
'MerchantID'=>$mid,
'TimeStamp'=>time(),
'Version'=>'2.0',
'RespondType'=>'JSON',
'MerchantOrderNo'=>"test0315001".time(),
'Amt'=>'30',
'VACC'=>'1',
'ALIPAY'=>'0',
'WEBATM'=>'1',
'CVS'=>'1',
'UNIONPAY'=>'1',
'CREDIT'=>'1',
'NotifyURL'=>'http://172.16.0.177/parking_yunlin/payments/newebpay/src/notify.php',
// 'NotifyURL'=>'https://hcparking.jotangi.net/parking_yunlin/payments/newebpay/src/notify.php',
// 'NotifyURL'=>'',
'ReturnURL'=>'',
'LoginType'=>'0',
'InstFlag'=>'0',
'ItemDesc'=>'test',
'Email'=>'m29110938@gmail.com',
));
echo "Data=[".$data1."]<br><br>";
$edata1=bin2hex(openssl_encrypt($data1, "AES-256-CBC", $key,
OPENSSL_RAW_DATA, $iv));
$hashs="HashKey=".$key."&".$edata1."&HashIV=".$iv;
$hash=strtoupper(hash("sha256",$hashs));
echo "MerchantID=".$mid."&";
echo "Version=2.0&";
echo "TradeInfo=".$edata1."&";
echo "TradeSha=".$hash;
?>
<!-- 正式 -->
<!-- <form method=post action="https://core.newebpay.com/MPG/mpg_gateway"> -->
<!-- 測試 -->
<form method=post action="https://ccore.newebpay.com/MPG/mpg_gateway">
MID: <input name="MerchantID" value="<?=$mid?>" readonly><br>
Version: <input name="Version" value="2.0" readonly><br>
TradeInfo:
<input name="TradeInfo" value="<?=$edata1?>" readonly><br>
TradeSha:
<input name="TradeSha" value="<?=$hash?>" readonly><br>
Amt: 30
<input type=submit>
</form>