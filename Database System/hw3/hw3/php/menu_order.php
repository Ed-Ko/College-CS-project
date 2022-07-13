<?php
    session_start();
	$link = new mysqli('localhost','root','','hw2');
    $SID = $_GET['SID'];
    $ordernum = $_SESSION["ordernum"];
    $identity = $_SESSION["identity"];
    $walletbalance = $_SESSION["amount"];
    $account = $_SESSION["account"];

    $delivery_fee = $_GET['delivery_fee'];
    $payment = "Payment";
    $receive = "ReceiveMoney";
    $status = "Unfinished";
    date_default_timezone_set("PRC");
    $start_time = date("Y/m/d H:i:s");
    $None= "None";

    $menu_sql="SELECT * FROM sell_object WHERE SID='$SID'";
    $menu_result = $link->query($menu_sql);
    
    $shop_sql="SELECT * FROM shop WHERE SID='$SID'";
    $shop_result = $link->query($shop_sql);
    $shop_row = $shop_result->fetch_assoc();

    if ($menu_result->num_rows < count($ordernum)){
        echo "<script>alert('ERROR:商家商品有所更動，請重新下單');</script>";
        if($identity=="customer"){
            echo "<script>location.replace('../shop_before_register.php');</script>";
        }
        else{
            echo "<script>location.replace('../shop_after_register.php');</script>";
        }
    }

    $subtotal = 0;
    $menu_index = 0;
    if ($menu_result->num_rows > 0) {
        while($menu_row = $menu_result->fetch_assoc()){
            //if order number of sell_object > 0
            if($ordernum[$menu_index]>0){
                $subtotal+=($ordernum[$menu_index]*$menu_row['price']);
            }
            $menu_index += 1;
        }
    }
    $total_price = $subtotal + $delivery_fee;
    $pay_price = $total_price*(-1);

    //if has enough money
    if($total_price>$walletbalance){
        echo "<script>alert('ERROR:餘額不足，請先儲值再重新下單');</script>";
        if($identity=="customer"){
            echo "<script>location.replace('../shop_before_register.php');</script>";
        }
        else{
            echo "<script>location.replace('../shop_after_register.php');</script>";
        }
    }
    else{
        $menu_index = 0;
        
        //insert order_list database
        $sql_insert="insert into order_list (user_account, trader_name, action, status, start_time, end_time, delivery_fee, total_price) values (?,?,?,?,?,?,?,?)"; 
        $stmt = $link->prepare($sql_insert);
        $stmt->bind_param('ssssssii', $account, $shop_row['shop_name'], $payment, $status, $start_time, $None, $delivery_fee, $total_price); // 's' specifies the variable type => 'string'
        $res_insert = $stmt->execute();
        $last_id = $link->insert_id;

        $menu_sql="SELECT * FROM sell_object WHERE SID='$SID'";
        $menu_result = $link->query($menu_sql);

        //echo "<script>alert($ordernum[$menu_index]);</script>";
        if ($menu_result->num_rows > 0) {  
            while($menu_row = $menu_result->fetch_assoc()){

                //if order number of sell_object > 0
                if($ordernum[$menu_index]>0){

                    //insert data to order_list_detail
                    $img = $menu_row['img'];
                    $imgType = $menu_row['imgType'];
                    $meal_name = $menu_row['meal_name'];
                    $price = $menu_row['price'];
                    $order_quantity = $ordernum[$menu_index];
                    $sql_insert="insert into order_list_detail (OIDs, img, imgType, meal_name, price, order_quantity) values ($last_id,'$img','$imgType','$meal_name','$price','$order_quantity')"; 
                    $result = $link->query($sql_insert);

                    //update sell_object amount in shop
                    $remain_quantity = $menu_row['quantity'] - $ordernum[$menu_index];
                    $sql = " update sell_object set quantity = ? where PID =? ";
                    $stmt = $link->prepare($sql);
                    $stmt->bind_param("ii", $remain_quantity, $menu_row['PID']);
                    $result = $stmt->execute();
                    //echo "<script>alert($ordernum[$menu_index]);</script>";
                }
                $menu_index += 1;
            }
        }

        //insert user's transaction_record database
        $sql_insert_record="insert into transaction_record (action, time,  trader, amount_change, user_account, shop_name) values (?,?,?,?,?,?)"; 
        $stmt = $link->prepare($sql_insert_record);
        $stmt->bind_param('sssiss', $payment, $start_time, $shop_row['shop_name'], $pay_price, $account, $None);
        $res_insert_record = $stmt->execute();

        //insert shop's transaction_record database
        $sql_insert_record="insert into transaction_record (action, time,  trader, amount_change, user_account, shop_name) values (?,?,?,?,?,?)"; 
        $stmt = $link->prepare($sql_insert_record);
        $stmt->bind_param('sssiss', $receive, $start_time, $account, $total_price, $None, $shop_row['shop_name']);
        $res_insert_record = $stmt->execute();

        //update walletballence in user
        $amount = $walletbalance - $total_price;
        $_SESSION["amount"] = $amount;
        $sql = " update user set amount = ? where account = ? ";
        $stmt = $link->prepare($sql);
        $stmt->bind_param("is", $amount, $account);
        $result = $stmt->execute();

        echo "<script>alert('訂購成功');</script>";
        if($identity=="customer"){
            echo "<script>location.replace('../shop_before_register.php');</script>";
        }
        else{
            echo "<script>location.replace('../shop_after_register.php');</script>";
        }
    }

?>