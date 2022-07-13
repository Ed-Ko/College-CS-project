<?php
    session_start();
    $link = new mysqli('localhost','root','','hw2');
    if ($link->connect_error){
        echo '數據庫連接失败！';
        exit(0);
    }
    header("Content-type: text/html; charset=utf-8");
    

    $shop_name = $_POST['shop_name'];
    $shop_category = $_POST['shop_category'];
    $latitude = $_POST['latitude'];
    $longitude = $_POST['longitude'];


    $error_num = 0;
    //$sql = "select shop_name from shop where shop_name = '$shop_name'";
    $sql = "SELECT shop_name FROM shop WHERE shop_name=? ";
    $stmt = $link->prepare($sql);
    $stmt->bind_param('s', $shop_name); // 's' specifies the variable type => 'string'
    $stmt->execute();

    $result = $stmt->get_result();
    $number = mysqli_num_rows($result);
    $error_string = '';

    if ($number) {
        $tmp_string = '店名已被註冊\n';
        $error_string = $error_string.$tmp_string;
        $error_num = $error_num + 1;
    }
    if ($shop_name == ''){
        $tmp_string = 'shop name 欄位空白\n';
        $error_string = $error_string.$tmp_string;
        $error_num = $error_num + 1;
    }
    if ($shop_category == ''){
        $tmp_string = 'shop category 欄位空白\n';
        $error_string = $error_string.$tmp_string;
        $error_num = $error_num + 1;
    }
    if ($latitude == ''){
        $tmp_string = 'latitude 欄位空白\n';
        $error_string = $error_string.$tmp_string;
        $error_num = $error_num + 1;
    }
    else if($latitude > 90 || $latitude < -90 || !is_numeric($latitude)){
        $tmp_string = 'latitude 格式錯誤，只能是數字，且在 -90 ~ 90間\n';
        $error_string = $error_string.$tmp_string;
        $error_num = $error_num + 1;
    }
    if ($longitude == ''){
        $tmp_string = 'longitude 欄位空白\n';
        $error_string = $error_string.$tmp_string;
        $error_num = $error_num + 1;
    }
    else if($longitude > 180 || $longitude < -180 || !is_numeric($longitude)){
        $tmp_string = 'longitude 格式錯誤，只能是數字，且在 -180 ~ 180間\n';
        $error_string = $error_string.$tmp_string;
        $error_num = $error_num + 1;
    }
    if($error_num == 0){
        $UID = $_SESSION["user_id"];

        //$sql_insert = "insert into shop (shop_name, shop_category, latitude, longitude,UID) values('$shop_name', '$shop_category', '$latitude', '$longitude', '$UID') ";
        $sql_insert = "insert into shop (shop_name, shop_category, latitude, longitude,UID) values(?, ?, ?, ?, ?) ";
        $stmt = $link->prepare($sql_insert);
        $stmt->bind_param('ssddi',$shop_name, $shop_category, $latitude, $longitude, $UID); // 's' specifies the variable type => 'string'
        $res_insert = $stmt->execute();

        //$sql_update = " update user set identity = 'manager' where UID = '$UID' ";
        $sql_update = " update user set identity = 'manager' where UID = ? ";
        $stmt = $link->prepare($sql_update);
        $stmt->bind_param('i', $UID); // 's' specifies the variable type => 'string'
        $res_update = $stmt->execute();

        
        if ($res_insert && $res_update) {
            $_SESSION["identity"] = 'manager';
            echo "<script>alert('註冊成功');</script>";
            echo "<script>location.replace('../shop_after_register.php');</script>";
        } 
        else {
            echo "<script>alert('系统繁忙，请稍候！');history.go(-1);</script>";
        }
    }
    else{
       echo "<script>alert('$error_string');history.go(-1);</script>";
       
        echo $_SESSION['UserName'];
    }
    
 ?>