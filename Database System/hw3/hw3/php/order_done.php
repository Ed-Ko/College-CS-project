<?php
    session_start();
    $link = new mysqli('localhost','root','','hw2');

    $identity = $_SESSION["identity"];
    $check_OID = $_GET['OIDs'];

    $check_sql = "SELECT * FROM order_list WHERE OIDs=?";
    $stmt = $link->prepare($check_sql);
    $OIDs = $check_OID;
    $stmt->bind_param('i', $OIDs);
    $stmt->execute();
    $check_result = $stmt->get_result();
    while($row = $check_result->fetch_assoc()){
        if($row['status'] == 'Finished')
            echo "<script>alert('order done action failed : order has is already done');</script>";
        else{
            $done_sql = "UPDATE order_list SET status=?, end_time=? WHERE OIDs=?";
            $stmt = $link->prepare($done_sql);
            $status = 'Finished';
            date_default_timezone_set("PRC");
            $end_time = date("Y/m/d H:i:s"); 
            $OIDs = $_GET['OIDs'];
            $stmt->bind_param('ssi', $status, $end_time, $OIDs);
            $res_insert_record = $stmt->execute();
        }
	}

    
    

    if($identity=="customer"){
        echo "<script>location.replace('../shop_before_register.php');</script>";
    }
    else{
        echo "<script>location.replace('../shop_after_register.php');</script>";
    }

?>