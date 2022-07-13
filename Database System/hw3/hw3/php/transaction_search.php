<?php
    session_start();
	$link = new mysqli('localhost','root','','hw2');
    $cur_user = $_SESSION["account"];
    $identity = $_SESSION["identity"];

    # search user transaction
    $transaction_status = $_POST['transaction_status'];
    if($transaction_status != "All"){
		$search_user_sql = "SELECT * FROM transaction_record WHERE user_account=? AND action=?";
        $stmt = $link->prepare($search_user_sql);
        $user_account = $_SESSION["account"];
        $action = $transaction_status;
        $stmt->bind_param('ss', $user_account, $action);
    }
	else{
        $search_user_sql = "SELECT * FROM transaction_record WHERE user_account=?";
        $stmt = $link->prepare($search_user_sql);
        $user_account = $_SESSION["account"];
        $action = $transaction_status;
        $stmt->bind_param('s', $user_account);
    }
    
    
    
    $stmt->execute();
    $check_result = $stmt->get_result();

    $search_transaction = array();
    while($row = $check_result->fetch_assoc())
        array_push($search_transaction, $row['RID']);
	
    $_SESSION['transaction_record'] = $search_transaction;

    if($identity=="customer"){
        echo "<script>location.replace('../shop_before_register.php');</script>";
        //header('Location: ../nav_test.php');
    }
    else{
        echo "<script>location.replace('../shop_after_register.php');</script>";
    }




?>