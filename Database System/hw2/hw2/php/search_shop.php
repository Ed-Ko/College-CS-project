<?php

    session_start();

    $host = "localhost";
    $dbuser = "root";
    $dbpasswd = "";
    $db_name = "hw2";

    $link = new mysqli($host,$dbuser,$dbpasswd,$db_name);

    if($_SERVER["REQUEST_METHOD"] == "POST"){

        $cur_user_id = $_SESSION["user_id"];
        $req_shop_name = $_POST["shop_name"];
        $req_lower_price = $_POST["lower_price"];
        $req_higher_price = $_POST["higher_price"];
        $req_meal_name = $_POST["meal_name"];
        $req_shop_category = $_POST["shop_category"];
        $req_distance = $_POST['distance'];

        # check search format
        $shop_sql = "SELECT * FROM shop";
        $menu_sql = "SELECT * FROM sell_object";
        $shop_case = "";
        $menu_case = "";

        if($req_shop_name!=''){
            $tmp = "name LIKE '%$req_shop_name%' ";
            if($shop_case == "")
                $shop_case = $shop_case." WHERE ".$tmp;
            else
                $shop_case = $shop_case." and ".$tmp;
        }

        $user_sql = "SELECT * FROM user WHERE UID = '$cur_user_id' ";
        $result = $link->query($user_sql);
        if ($result->num_rows > 0) {
            $row=$result->fetch_assoc();
            $user_latitude = $row['latitude'];	
            $user_longtitude = $row['longtitude'];
        }


        if($req_lower_price != ''){
            $tmp = "price>='$req_lower_price' ";
            if($menu_case=="")
                $menu_case = $menu_case." WHERE ".$tmp;
            else
                $menu_case = $menu_case." and ".$tmp;
        }

        if($req_higher_price!=''){
            $tmp = "price<='$req_higher_price' ";
            if($menu_case=="")
                $menu_case = $menu_case." WHERE ".$tmp;
            else
                $menu_case = $menu_case." and ".$tmp;
        }

        if($req_meal_name!=''){
            $tmp = "meal_name LIKE '%$req_meal_name%' ";
            if($menu_case==""){
                $menu_case = $menu_case." WHERE ".$tmp;
            }
            else{
                $menu_case = $menu_case." and ".$tmp;
            }
        }

        if($req_shop_category!=''){
            $sql = "SELECT * FROM sql WHERE shop_category LIKE '%$req_shop_category%' ";
            $tmp = "shop_category LIKE '%$req_shop_category%' ";
            if($shop_case==""){
                $shop_case = $shop_case." WHERE ".$tmp;
            }
            else{
                $shop_case = $shop_case." and ".$tmp;
            }
        }

        # search each selected shop and get their menu
        $shop_sql = $shop_sql.$shop_case;
        $shop_result = $link->query($shop_sql);
        $shop_SID = array();
        $search_SID = array();

        while($shop_row=$shop_result->fetch_assoc()){
            $distance_number = pow( ($shop_row['latitude'] - $user_latitude), 2) + pow( ($shop_row['longtitude'] - $user_longtitude), 2);
            
            if($distance_number < 800)
                $distance_case = "near";
            else if($distance_number >= 800 && $distance_number < 20000)
                $distance_case = "medium";
            else
                $distance_case = "far";


            if($req_distance == "all")
                array_push($shop_SID, $shop_row['SID']);
            else if($distance_case == $req_distance)
                array_push($shop_SID, $shop_row['SID']);
            
        }
        $shop_SID = array_unique($shop_SID);

        if($menu_case!=""){
            $menu_sql = $menu_sql.$menu_case;
            for ($index = 0; $index < count($shop_SID); $index++){
                $search_sql = $menu_sql." and SID='$shop_SID[$index]' ";
                $search_result = $link->query($search_sql);
                if ($search_result->num_rows > 0) 
                    array_push($search_SID, $shop_SID[$index]);
            }
            $_SESSION["search_SID"] = $search_SID;
        }
        else{
            $_SESSION["search_SID"] = $shop_SID;
        }

        for ($index = 0; $index < count($search_SID); $index++){
            echo $search_SID[$index];
        }

        echo "<script>window.location.href = 'https://localhost/hw2/nav_test.php'</script>";
              

    }


?>