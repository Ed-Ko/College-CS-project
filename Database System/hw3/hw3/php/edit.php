<?php
	$link = new mysqli('localhost','root','','hw2');
	$price = $_POST['price'];
    $quantity = $_POST['quantity'];
	$PID = $_GET['PID'];
	$SID = $_GET['SID'];
	
	$error_num = 0;
    $error_string = '';

	if ($price == ''){
        $tmp_string = 'price 欄位空白\n';
        $error_string = $error_string.$tmp_string;
        $error_num = $error_num + 1;
    }
    else if( !(preg_match("/^[1-9][0-9]*$/",$price) || $price == 0) ){
        $tmp_string = 'price 格式錯誤，只能是正整數\n';
        $error_string = $error_string.$tmp_string;
        $error_num = $error_num + 1;
    }
    if ($quantity == ''){
        $tmp_string = 'quantity 欄位空白\n';
        $error_string = $error_string.$tmp_string;
        $error_num = $error_num + 1;
    }
    else if( !(preg_match("/^[1-9][0-9]*$/",$quantity) || $quantity == 0)){
        $tmp_string = 'quantity 格式錯誤，只能是正整數\n';
        $error_string = $error_string.$tmp_string;
        $error_num = $error_num + 1;
    }

    if($error_num == 0){
        //$sql = " update sell_object set price = '$price', quantity = '$quantity' where PID = '$PID' and SID = '$SID' ";
        $sql = " update sell_object set price = ?, quantity = ? where PID = ? and SID = ? ";
        $stmt = $link->prepare($sql);
        $stmt->bind_param("iiii", $price, $quantity, $PID, $SID);
        $exe = $stmt->execute();

        if ($exe== TRUE) {
            echo "<script>alert('修改成功');</script>";
            echo "<script>location.replace('../shop_after_register.php');</script>";
        } 
        else {
            echo "<script>alert('系统繁忙，请稍候！');history.go(-1);</script>";
        }
    }
    else{
       echo "<script>alert('$error_string');history.go(-1);</script>";
    }

	
	 
?>