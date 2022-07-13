<?php
	$link = new mysqli('localhost','root','','hw2');
	$PID = $_GET['PID'];
	$SID = $_GET['SID'];

	$shop_sql="SELECT * FROM shop WHERE SID ='$SID' " ;
    $shop_result = $link->query($shop_sql);
    $shop_row = $shop_result->fetch_assoc();
    $shop_name = $shop_row['shop_name'];

	$order_sql="SELECT * FROM order_list WHERE trader_name ='$shop_name' and status ='Unfinished' " ;
    $order_result = $link->query($order_sql);
    //$order_row = $order_result->fetch_assoc();
    if($order_result->num_rows > 0){
		echo "<script>alert('ERROR:Please finished or cancel all shop order first!');location.replace('../shop_after_register.php');</script>";
	}
	else{
		$sql = "delete from sell_object where PID = '$PID' and SID = '$SID' ";
		$result = $link->query($sql);
		if (!$result) {
			die($link->error);
		}
		echo "<script>location.replace('../shop_after_register.php');</script>";
	}
	
	//header('Location: ../shop_after_register.php');
?>