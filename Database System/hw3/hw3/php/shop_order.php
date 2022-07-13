<?php
	session_start();
	$link = new mysqli('localhost','root','','hw2');
	$status = $_POST['shop_order_status'];
	$shop_id = $_SESSION["SID"];
	$identity = $_SESSION["identity"];
    $shop_name = "";

    $search_shop_name = "SELECT * FROM shop WHERE SID='$shop_id'";
    $search_result = $link->query($search_shop_name);
	if ($search_result->num_rows > 0) {
        while($row = $search_result->fetch_assoc())
		    $shop_name = $row["shop_name"];
	}

	echo $status;
	$search_OID = array();
	if($status != "All"){
		$sql = "SELECT * FROM order_list WHERE trader_name='$shop_name' and action = 'Payment' and status = '$status' ";
	}
	else{
		$sql = "SELECT * FROM order_list WHERE trader_name='$shop_name' and action = 'Payment'";
	}
	$result = $link->query($sql);
	if ($result->num_rows > 0) {
		while($row = $result->fetch_assoc()){
			array_push($search_OID, $row['OIDs']);
		}
	}
	$_SESSION["shop_search_OID"] = $search_OID;
	//print_r($_SESSION["search_OID"]);
	if($identity=="customer"){
        echo "<script>location.replace('../shop_before_register.php');</script>";
        //header('Location: ../nav_test.php');
    }
    else{
        echo "<script>location.replace('../shop_after_register.php');</script>";
    }
	
?>