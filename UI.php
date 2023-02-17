<?php
 //////////有可能付款成功,也有可能失敗,都會呼叫此頁///////////////////////
    
    session_start();
    include("../db_tools.php");
    $err_msg = "";
    // print_r($_POST);

    function create_aes_decrypt($parameter = "", $key = "", $iv = "") {
        return strippadding(openssl_decrypt(hex2bin($parameter),'AES-256-CBC', $key, OPENSSL_RAW_DATA|OPENSSL_ZERO_PADDING, $iv));
    }
    $data1 = $_POST['TradeInfo'];
    
    $key="pthSRiJ6CEZisT4XHtfkzhczkhbwHgjp";
    $iv="CXiNX6dQOHsJyIAP";
    //去除 padding 副程式
    function strippadding($string) {
        $slast = ord(substr($string, -1));
        $slastc = chr($slast);
        $pcheck = substr($string, -$slast);
        if (preg_match("/$slastc{" . $slast . "}/", $string)) {
        $string = substr($string, 0, strlen($string) - $slast);
        return $string;
        } else {
        return false; 
    } }
    //主程式
    $edata1=strippadding(openssl_decrypt(hex2bin($data1), "AES-256-CBC",
    $key, OPENSSL_RAW_DATA|OPENSSL_ZERO_PADDING, $iv));
    // echo "解密後資料=[<font color=darkblue><gg id='outtt'>".$edata1."</gg></font>]<br>";
    $edata = json_decode($edata1, true);
    // print_r($edata);
    // echo "<br>";
    $status = $edata['Status'];
    // echo $edata['Result']['MerchantOrderNo'];

    if ($status != "SUCCESS"){
        echo $status."</br>";
        // echo "回到首頁";
        echo "<button class='btn btn_outline_green' onclick='gocheck()'>返回</button>";
    }else{

        $bill_no = $edata['Result']['MerchantOrderNo'];
        // echo $bill_no;
        $orderno = $bill_no;
        $transaction_id = $edata['Result']['TradeNo'];
        $bill_date = $edata['Result']['PayTime'];
        $bill_amount = $edata['Result']['Amt'];

        // $sql = "SELECT * FROM billinfo_E where bill_no='$bill_no' ";
        $sql = "SELECT * FROM billinfo_E where bill_no='$bill_no' and pay_status=-1 ";
        // echo $sql;
        if ($result = mysqli_query($link, $sql)) {
            if (mysqli_num_rows($result) > 0) {
                while ($row = mysqli_fetch_array($result)) {
                    $mid = $row['member_id'];
                }
                // 付款完成，更新ＤＢ
                $statuscode = 1;//-1:待付款, 1: 已付款, 2:已出貨
                $sql1 = "UPDATE billinfo_E SET pay_status='".$statuscode."',pay_type=3, transactionId='".$transaction_id."',bill_updated_at = NOW() where bill_no='".$bill_no."'";
                $result1 = mysqli_query($link,$sql1); //or die(mysqli_error($link));
                // echo $bill_no;
                if ($bill_no != '') {
                    $sql4 = "SELECT * FROM billinfo_E as a "; 
                    $sql4 = $sql4." left join (select * from member) as b on a.member_id = b.mid ";
                    $sql4 = $sql4." where bill_no='".$bill_no."'";
                    // echo $sql;
                    if ($result4 = mysqli_query($link, $sql4)) {
                        if (mysqli_num_rows($result4) > 0) {
                            while ($row4 = mysqli_fetch_array($result4)) {
                                $plateNo = $row4['plateNo'];
                                $bill_id = $row4['bill_id'];
                                $bill_pay = $row4['bill_pay'];
                                $transactionId = $row4['transactionId'];
                                $mobile = $row4['member_id'];
                                $email = $row4['member_email'];
                                $barcode = $row4['invoicephone'];//載具
                                $company_no = $row4['uniformno'];//統編
                                $title = $row4['companytitle'];//抬頭

                                // 開發票
                                $apiurl = "https://app-api.douliu.asusmaas.app/app-api/bills/";
                                $apiurl = $apiurl.$plateNo;
                                $apiurl = $apiurl."/invoice";
                                $cmd = "curl -X 'PATCH' '".$apiurl."' -H 'accept: application/json' -H 'Content-Type: application/json' -H 'X-API-KEY: Mj5VduJkP1wDqoH=Gr9rhe2pX+hNJm0K' -d '{".'"bill_id"'.': "'.$bill_id.'"'.",".'"mobile"'.': "'.$mobile.'"'.",".'"email"'.': "'.$email.'"'.",".'"barcode"'.': "'.$barcode.'"'.",".'"company_no"'.': "'.$company_no.'"'.",".'"title"'.': "'.$title.'"'."}'";
                                // echo $cmd;
                                $result = shell_exec($cmd);
                                $result_json = json_decode($result, true);
                                // save bill log
                                $sql="INSERT INTO billlog (bill_no,plateNo,cmd,result,log_date) VALUES ";
                                $sql=$sql." ('$orderno','$plateNo','".$apiurl."/PATCH"."','$result',now());";
                                mysqli_query($link, $sql) or die(mysqli_error($link));
                                if (isset($result_json['code'])){
                                    for ($i=0;$i<10;$i++){
                                        $result_json = json_decode($result, true);
                                        if (isset($result_json['code'])){
                                            usleep(500000); // 延遲0.5秒
                                            $result = shell_exec($cmd);
                                            $sql="INSERT INTO billlog (bill_no,plateNo,cmd,result,log_date) VALUES ";
                                            $sql=$sql." ('$orderno','$plateNo','".$apiurl."/POST"."','$result',now());";
                                            mysqli_query($link, $sql) or die(mysqli_error($link));
                                            continue;
                                        }else{
                                            $invoice_no = $result_json['invoice_no'];
                                            $invoice_random = $result_json['invoice_random'];
                                            $invoice_time = $result_json['invoice_time'];
                                            // $time = $result_json['time'];
                                            $sql="UPDATE billinfo_E SET invoice_no='$invoice_no',invoice_date='$invoice_time', random_no='$invoice_random' where bill_no='$orderno'";
                                            $result = mysqli_query($link,$sql); //or die(mysqli_error($link));

                                            $sql="INSERT INTO billlog (bill_no,plateNo,cmd,result,log_date) VALUES ";
                                            $sql=$sql." ('$orderno','$plateNo','".$apiurl."/POST"."','$result',now());";
                                            mysqli_query($link, $sql) or die(mysqli_error($link));
                                            break;
                                        }
                                    }
                                }else{
                                    $invoice_no = $result_json['invoice_no'];
                                    $invoice_random = $result_json['invoice_random'];
                                    $invoice_time = $result_json['invoice_time'];
                                    // $time = $result_json['time'];
                                    $sql="UPDATE billinfo_E SET invoice_no='$invoice_no',invoice_date='$invoice_time', random_no='$invoice_random' where bill_no='$orderno'";
                                    $result = mysqli_query($link,$sql); //or die(mysqli_error($link));
                                }

                                // 完成付款
                                $apiurl = "https://app-api.douliu.asusmaas.app/app-api/bills/";
                                $apiurl = $apiurl.$plateNo;
                                $cmd = "curl -X 'POST' '".$apiurl."' -H 'accept: application/json' -H 'Content-Type: application/json' -H 'X-API-KEY: Mj5VduJkP1wDqoH=Gr9rhe2pX+hNJm0K' -d '{".'"bill_id"'.': "'.$bill_id.'"'.",".'"status"'.': "paid"'.",".'"amount"'.': "'.$bill_pay.'"'.",".'"transaction_id"'.': "'.$transactionId.'"'.",".'"transaction_status"'.': "paid"'."}'";
                                // echo "<br>";
                                // echo $cmd;
                                $result = shell_exec($cmd);
                                $result_json = json_decode($result, true);
                                // save bill log
                                $sql="INSERT INTO billlog (bill_no,plateNo,cmd,result,log_date) VALUES ";
                                $sql=$sql." ('$bill_no','$plateNo','".$apiurl."/POST"."','$result',now());";
                                mysqli_query($link, $sql) or die(mysqli_error($link));
                                if (isset($result_json['code'])){
                                    for ($i=0;$i<10;$i++){
                                        $result_json = json_decode($result, true);
                                        if (isset($result_json['code'])){
                                            usleep(500000); // 延遲0.5秒
                                            $result = shell_exec($cmd);
                                            $sql="INSERT INTO billlog (bill_no,plateNo,cmd,result,log_date) VALUES ";
                                            $sql=$sql." ('$bill_no','$plateNo','".$apiurl."/POST"."','$result',now());";
                                            mysqli_query($link, $sql) or die(mysqli_error($link));
                                            continue;
                                        }else{
                                            $sql="INSERT INTO billlog (bill_no,plateNo,cmd,result,log_date) VALUES ";
                                            $sql=$sql." ('$bill_no','$plateNo','".$apiurl."/POST"."','$result',now());";
                                            mysqli_query($link, $sql) or die(mysqli_error($link));
                                            break;
                                        }
                                    }
                                }




                                // echo "<br>";
                                // echo $result;
                                // print_r($result);
                            }
                        }
                    }
                }

                // 訂單成功隨機發送店家優惠券
                // $sql2 = "SELECT * from coupon_E where coupon_trash=0 and coupon_type=1 and coupon_enddate > NOW() order by RAND() LIMIT 1";   //and coupon_storeid='".$shoppingarea."'"
                // // echo $sql2;
                // if ($result2 = mysqli_query($link, $sql2)){
                //     if (mysqli_num_rows($result2) > 0){
                //         $coupon_id="";
                //         while($row2 = mysqli_fetch_array($result2)){
                //             $coupon_id = $row2['coupon_id'];
                        
                //             // 店家優惠券
                //             $coupon_no = uniqid();
                //             $sql3="INSERT INTO mycoupon (mid, coupon_no, cid,coupon_id ,coupon_name, coupon_type, coupon_description, coupon_startdate, coupon_enddate, coupon_status, coupon_rule, coupon_discount, discount_amount, coupon_storeid, coupon_for, coupon_picture) ";
                //             $sql3=$sql3." select $mid,'$coupon_no',cid,coupon_id ,coupon_name, coupon_type, coupon_description, coupon_startdate, coupon_enddate, coupon_status, coupon_rule, coupon_discount, discount_amount, coupon_storeid, coupon_for, coupon_picture from coupon_E where coupon_id = '".$coupon_id."'";
                //             // echo $sql3;
                //             mysqli_query($link,$sql3) or die(mysqli_error($link));
                //             echo "<script>alert('恭喜獲得優惠券，詳細狀況請再app中查看，感謝您。')</script>";
                //         }
                //     }
                // }
            }
        }

        // 顯示ＵＩ
        $sql = "SELECT a.* FROM billinfo_E as a ";
        $sql = $sql." where a.bid>0 ";
        if (trim($bill_no) != "") {	
            $sql = $sql." and a.bill_no = '".trim($bill_no)."'";
        }	
        if ($result = mysqli_query($link, $sql)){
            if (mysqli_num_rows($result) > 0){
                while($row = mysqli_fetch_array($result)){
                    //echo "    <td>".$row['order_no']."</td>";
                    $bid = $row['bid'];
                    $bill_date = $row['bill_date'];
                    //$store_name = $row['store_name'];
                    $store_name = '雲林停車場';
                    // $memberid = $row['memberid'];
                    $bill_amount = $row['bill_amount'];
                    //echo $bill_amount;
                    switch ($row['pay_type']) {
                        case 1:
                            $pay_type="Linepay";
                            break;
                        case 2:
                            $pay_type="街口";
                            break;
                        case 3:
                            $pay_type="刷卡";
                            break;
                        case 4:
                            $pay_type="悠遊付";
                            break;
                        default:
                            $pay_type="&nbsp;";
                    }									
                    switch ($row['pay_status']) {
                        case 0:
                            $pay_status="未付款";
                            break;
                        case 1:
                            $pay_status="已付款";
                            break;
                        default:
                            $pay_status="處理中";
                    }									
                    switch ($row['bill_status']) {
                        case 0:
                            $bill_status="取消";
                            break;
                        case 1:
                            $bill_status="完成";
                            break;
                        default:
                            $bill_status="處理中";
                    }
                    $invoice_type = $row['invoicetype'];
                    switch ($row['invoicetype']) {
                        case 1:
                            $invoicetype="個人發票";
                            break;
                        case 2:
                            $invoicetype="手機載具";
                            break;
                        case 3:
                            $invoicetype="統一編號電子發票";
                            break;
                        default:
                            $invoicetype="";
                    }
                    $invoicephone = $row['invoicephone'];
                    $companytitle = $row['companytitle'];
                    $uniformno = $row['uniformno'];
                    $invoice_status = $row['invoicestatus'];	
    
                    switch ($row['invoicestatus']) {
                        case 0:
                            $invoicestatus="未開立";
                            break;
                        case 1:
                            $invoicestatus="已開立";
                            break;
                        case 2:
                            $invoicestatus="已作廢";
                            break;
                        default:
                            $invoicestatus="";
                    }
                    $invoice_no = $row['invoice_no'];
                    $invoice_date = $row['invoice_date'];
                    $random_no = $row['random_no'];
                    //$memberid = $row['memberid'];
                    // $member_name = $row['member_name'];
                    // $member_email = $row['member_email'];
                    // $member_address = $row['member_address'];
                    
                    $recipientname = $row['recipientname'];
                    $recipientaddr = $row['recipientaddr'];
                    $recipientphone = $row['recipientphone'];
                    $recipientmail = $row['recipientmail'];	
                    // echo $recipientmail;
                    //if ($member_email =='') $member_email = $recipientmail;
                    break;
                }
        
                
            }else{
                    $bill_date = "";
                    $store_name = "";
                    // $memberid = "";
                    $bill_amount = "";
                    $pay_type="";
                    $pay_status="";
                    $bill_status="";
                    exit;
            }
        }else{
            exit;
        }
        
        mysqli_close($link);
    }

    
    

?>
<?php if ($status == 'SUCCESS'){
    
?>
<!DOCTYPE html>
<html lang="en">
<!-- InstanceBegin template="/Templates/_Layout.dwt" codeOutsideHTMLIsLocked="false" -->

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <!-- InstanceBeginEditable name="doctitle" -->
    <title>雲林停車</title>
    <!-- InstanceEndEditable -->
    <!-- slick -->
    <link rel="stylesheet" href="../css/slick.css" />
    <link rel="stylesheet" href="../css/slick-theme.css" />
    <!-- all.css -->
    <link rel="stylesheet" href="../css/all.css" />
    <!-- InstanceBeginEditable name="head" -->
    <!-- InstanceEndEditable -->
</head>

<body>
    <main class="main">
        <!-- content -->
        <!-- InstanceBeginEditable name="main" -->
        <div class="Good">
            <div class="Good_order_established">
                <div class="text_center">
                    <img src="../css/checked_g.svg" alt="">
                    <div class="text_green mt_1d5 mb_0d5 size_20 font_med">
						付款完成！
                    </div>

                </div>
				<table width='100%'>
					<tr>
						<td width='10%'>&nbsp;</td>
						<td width='80%'>
							<div class="Member_profile">
								<div class="form_wrap mb_1 ">
									<label for="" class="form_label"> 付款日期: <?=$bill_date;?></label>
								</div>
								<div class="form_wrap mb_1 ">
									<label for="" class="form_label" id="orderno" value=<?php echo$bill_no;?>> 訂單序號: <?=$bill_no;?></label>
								</div>
								<div class="form_wrap mb_1 ">
									<label for="" class="form_label"> 消費店家: <?=$store_name;?></label>
								</div>
								<div class="form_wrap mb_1 ">
									<label for="" class="form_label"> 付款方法: 信用卡</label>
								</div>
								<div class="form_wrap mb_1 ">
									<label for="" class="form_label"> 付款金額: <font color='red'>NT$ <?=$bill_amount;?></font></label>
								</div>
							</div>
						</td>
						<td width='10%'>&nbsp;</td>
					</tr>
				</table>


				<!-- add-2022-05-30 按鈕導向網頁 -->
                <button class="btn btn_green mb_1" onclick="gohome()">確認</button>
                <button class="btn btn_outline_green " onclick="gocheck()">查看訂單</button>
				
				
				


            </div>
			
        </div>
        <!-- InstanceEndEditable -->
        <!-- end content -->
    </main>
    <!-- jquery 
    <script src="https://code.jquery.com/jquery-3.6.0.js"
        integrity="sha256-H+K7U5CnXl1h5ywQfKtSj8PCmoN9aaq30gDh27Xc0jk=" crossorigin="anonymous"></script>-->
    <!-- InstanceBeginEditable name="Scripts" -->

    <!-- InstanceEndEditable -->
</body>
<!-- InstanceEnd -->
	<SCRIPT LANGUAGE=javascript>
		// add-2022-05-30 按鈕導向網頁
		function gocheck() 
		{
			// window.location = "http://172.16.0.130/jtgshop/order.php";
			// window.location = "https://shop.jotangi.net/jtgshop/order.php";
		}
		function gohome() 
		{
			// alert('go home');
			// window.location = "http://172.16.0.130/jtgshop/index.php";
			// window.location = "https://shop.jotangi.net/jtgshop/index.php";
		}
	</SCRIPT>
</html>
<!-- 結束 -->

<?php }else{
    echo "錯誤代碼為：".$status."<br>請洽服務人員。";
}
?>