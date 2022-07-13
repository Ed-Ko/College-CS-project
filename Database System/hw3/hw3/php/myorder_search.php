<?php
	session_start();
	$link = new mysqli('localhost','root','','hw2');
	$status = $_POST['status'];
	$user_account = $_SESSION["account"];
	$identity = $_SESSION["identity"];

	echo $status;
	$search_OID = array();
	if($status != "All"){
		$sql = "SELECT * FROM order_list WHERE user_account = '$user_account' and action = 'Payment' and status = '$status' ";
	}
	else{
		$sql = "SELECT * FROM order_list WHERE user_account = '$user_account' and action = 'Payment'";
	}
	$result = $link->query($sql);
	if ($result->num_rows > 0) {
		while($row = $result->fetch_assoc()){
			array_push($search_OID, $row['OIDs']);
		}
	}
	$_SESSION["search_OID"] = $search_OID;
	//print_r($_SESSION["search_OID"]);
	if($identity=="customer"){
        echo "<script>location.replace('../shop_before_register.php');</script>";
        //header('Location: ../nav_test.php');
    }
    else{
        echo "<script>location.replace('../shop_after_register.php');</script>";
    }
	
?>