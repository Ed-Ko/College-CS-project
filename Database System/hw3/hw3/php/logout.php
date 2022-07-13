<?php
    session_start();
    /*unset($_SESSION["user_id"]);
    
    unset($_SESSION["cur_user"]);
    unset($_SESSION["identity"]);
    unset($_SESSION["phone"]);
    unset($_SESSION["latitude"]);
    unset($_SESSION["longitude"]);
    unset($_SESSION["amount"]);
    unset($_SESSION["shop_id"]);
    unset($_SESSION["shop_name"]);
    unset($_SESSION["shop_category"]);
    unset($_SESSION["shop_latitude"]);
    unset($_SESSION["shop_longitude"]);*/
    session_destroy();

    echo "<script>alert('Sign out success');</script>";
    echo "<script>location.replace('../entrance.php');</script>";
    //echo "<script>window.location.href = 'http://localhost/hw2/'</script>";

?>