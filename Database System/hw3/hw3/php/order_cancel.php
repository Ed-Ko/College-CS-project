<?php

    session_start();

    $link = new mysqli('localhost','root','','hw2');
    
    $order_id = $_GET['OIDs'];

    $total_price = $_GET['total_price'];
    $identity = $_SESSION["identity"];
    $walletbalance = $_SESSION["amount"];
    $account = $_SESSION["account"];
    $payment = "Payment";
    $receive = "ReceiveMoney";
    $pay_price = $total_price*(-1);
    date_default_timezone_set("PRC");
    $time = date("Y/m/d H:i:s");
    $None = "None";
    
    $order_sql="SELECT * FROM order_list WHERE OIDs ='$order_id' " ;
    $order_result = $link->query($order_sql);
    $order_row = $order_result->fetch_assoc();
    $shop_name = $order_row['trader_name'];

    if($order_row['status'] == 'Cancel')
        echo "<script>alert('order cancel action failed : order has is already cancelled');</script>";
    else{
        # update order info
        $done_sql = "UPDATE order_list SET status=?, end_time=? WHERE OIDs=?";
        $stmt = $link->prepare($done_sql);
        $status = 'Cancel';
        $end_time = date("Y/m/d H:i:s"); 
        $OIDs = $_GET['OIDs'];
        $stmt->bind_param('ssi', $status, $end_time, $OIDs);
        $res_insert_record = $stmt->execute();

        # insert user's transaction_record database
        $sql_insert_record="INSERT INTO transaction_record (action, time,  trader, amount_change, user_account, shop_name) VALUES (?,?,?,?,?,?)"; 
        $stmt = $link->prepare($sql_insert_record);
        $stmt->bind_param('sssiss', $receive, $time, $shop_name, $total_price, $account, $None);
        $res_insert_record = $stmt->execute();

        # insert shop's transaction_record database
        $sql_insert_record="INSERT INTO transaction_record (action, time,  trader, amount_change, user_account, shop_name) VALUES (?,?,?,?,?,?)"; 
        $stmt = $link->prepare($sql_insert_record);
        $stmt->bind_param('sssiss', $payment, $time, $account, $total_price, $None, $shop_name);
        $res_insert_record = $stmt->execute();

        # update walletballence in user
        $amount = $walletbalance - $total_price;
        $_SESSION["amount"] = $amount;
        $sql = " update user set amount = ? where account = ? ";
        $stmt = $link->prepare($sql);
        $stmt->bind_param("is", $amount, $account);
        $result = $stmt->execute();

    }


	if($identity=="customer"){
        echo "<script>location.replace('../shop_before_register.php');</script>";
    }
    else{
        echo "<script>location.replace('../shop_after_register.php');</script>";
    }


?>