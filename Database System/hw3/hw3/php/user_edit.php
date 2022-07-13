<?php
    session_start();
	$link = new mysqli('localhost','root','','hw2');
	$latitude = $_POST['latitude'];
    $longitude = $_POST['longitude'];
	$UID = $_SESSION["user_id"];
    $identity = $_SESSION["identity"];
	$error_num = 0;
    $error_string = '';

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
        //$sql = " update user set latitude = '$latitude', longitude = '$longitude' where UID = '$UID' ";
        $sql = " update user set latitude = ?, longitude = ? where UID =? ";
        $stmt = $link->prepare($sql);
        $stmt->bind_param("ddi", $latitude, $longitude,  $UID);
        $result = $stmt->execute();

        if ($result== TRUE) {
            echo "<script>alert('修改成功');</script>";
            $_SESSION["latitude"] =  $latitude;
            $_SESSION["longitude"] =  $longitude;
            if($identity=="customer"){
                echo "<script>location.replace('../shop_before_register.php');</script>";
                //header('Location: ../nav_test.php');
            }
            else{
                echo "<script>location.replace('../shop_after_register.php');</script>";
            }
        } 
        else {
            echo "<script>alert('系统繁忙，请稍候！');history.go(-1);</script>";
        }
    }
    else{
       echo "<script>alert('$error_string');history.go(-1);</script>";
    }

	
	 
?>