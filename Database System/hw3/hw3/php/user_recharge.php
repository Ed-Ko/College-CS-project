<?php
    session_start();
	$link = new mysqli('localhost','root','','hw2');
	$AddValue = $_POST['AddValue'];
	$UID = $_SESSION["user_id"];
    $identity = $_SESSION["identity"];
    $amount = $_SESSION["amount"];
    $account = $_SESSION["account"];
    $error_num = 0;
    $error_string = '';
    date_default_timezone_set("PRC");
    $time = date("Y/m/d H:i");
    $None = "None";
    $action = "Recharge";

	if ($AddValue == '' || !is_numeric($AddValue) || $AddValue<=0){
        $error_string = '需輸入一個正整數';
        $error_num += 1;
    }
    else{
        $amount += $AddValue;
    }

    if($error_num == 0){
        //$sql = " update user set latitude = '$latitude', longitude = '$longitude' where UID = '$UID' ";
        $sql = " update user set amount = ? where UID = ? ";
        $stmt = $link->prepare($sql);
        $stmt->bind_param("ii", $amount,  $UID);
        $result = $stmt->execute();

        if ($result== TRUE) {
            $sql_insert="insert into transaction_record (action, time,  trader, amount_change, user_account, shop_name) values (?,?,?,?,?,?)"; 
            $stmt = $link->prepare($sql_insert);
            $stmt->bind_param('sssiss', $action, $time, $account, $AddValue, $account, $None); // 's' specifies the variable type => 'string'
            $res_insert = $stmt->execute();

            if($res_insert == TRUE){
                echo "<script>alert('儲值成功');</script>";
                $_SESSION["amount"] =  $amount;
                if($identity=="customer"){
                echo "<script>location.replace('../shop_before_register.php');</script>";
                }
                else{
                    echo "<script>location.replace('../shop_after_register.php');</script>";
                }
            }
            else{
                echo "<script>alert('儲值失敗，請重新嘗試');history.go(-1);</script>";
            }
            
        } 
        else {
            echo "<script>alert('系统繁忙，请稍候！');history.go(-1);</script>";
        }
    }
    else{
       echo "<script>alert('$error_string');</script>";
       if($identity=="customer"){
           echo "<script>location.replace('../shop_before_register.php');</script>";
       }
       else{
           echo "<script>location.replace('../shop_after_register.php');</script>";
       }
    }

	
	 
?>