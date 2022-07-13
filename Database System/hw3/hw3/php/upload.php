<?php
    
    //取得上傳檔案資訊
    //$filename=$_FILES['myFile']['name'];
    //$tmpname=$_FILES['myFile']['tmp_name'];
    //$filetype=$_FILES['myFile']['type'];
    //$filesize=$_FILES['myFile']['size'];    
    //$file=NULL;
    $link = new mysqli('localhost','root','','hw2');
    session_start();

    $SID = $_SESSION["SID"];
    $meal_name = $_POST['meal_name'];
    $price = $_POST['price'];
    $quantity = $_POST['quantity'];

    $error_num = 0;
    //$sql = "select meal_name from sell_object where meal_name = '$meal_name' and SID = '$SID' ";
    $sql = "SELECT meal_name FROM sell_object WHERE meal_name = ? and SID = ? ";
    $stmt = $link->prepare($sql);
    $stmt->bind_param('si', $meal_name, $SID); // 's' specifies the variable type => 'string'
    $stmt->execute();
    $result = $stmt->get_result();

    $number = mysqli_num_rows($result);
    $error_string = '';
    
    //開啟圖片檔
    if($_FILES["myFile"]["tmp_name"] == ''){
        $tmp_string = 'picture 欄位空白\n';
        $error_string = $error_string.$tmp_string;
        $error_num = $error_num + 1;
    }
    else{
         $file = fopen($_FILES["myFile"]["tmp_name"], "rb");
        // 讀入圖片檔資料
        $fileContents = fread($file, filesize($_FILES["myFile"]["tmp_name"])); 
        $imgType=$_FILES["myFile"]["type"];
        //關閉圖片檔
        fclose($file);
        //讀取出來的圖片資料必須使用base64_encode()函數加以編碼：圖片檔案資料編碼
        $fileContents = base64_encode($fileContents);
    }
   

    if ($number) {
        $tmp_string = '商品名已被註冊\n';
        $error_string = $error_string.$tmp_string;
        $error_num = $error_num + 1;
    }
    if ($meal_name == ''){
        $tmp_string = 'meal name 欄位空白\n';
        $error_string = $error_string.$tmp_string;
        $error_num = $error_num + 1;
    }
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
        //$sql_insert="insert into sell_object (img,imgType,meal_name,price,quantity,SID) values ('$fileContents','$imgType','$meal_name','$price','$quantity','$SID')"; 
        $sql_insert="insert into sell_object (img,imgType,meal_name,price,quantity,SID) values ('$fileContents','$imgType',?,?,?,?)"; 
        $stmt = $link->prepare($sql_insert);
        $stmt->bind_param('siii', $meal_name, $price, $quantity, $SID); // 's' specifies the variable type => 'string'
        $res_insert = $stmt->execute();

        if ($res_insert == TRUE) {
            echo "<script>alert('添加成功');</script>";
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