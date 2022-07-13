<?php
	
    session_start();

    $host = "localhost";
    $dbuser = "root";
    $dbpasswd = "";
    $db_name = "hw2";
    
	$link = new mysqli($host,$dbuser,$dbpasswd,$db_name);

	$shop = $_POST['shop'];
    $distance = $_POST['distance'];
	$price_low = $_POST['price_low'];
    $price_high = $_POST['price_high'];
    $meal= $_POST['meal'];
	$category = $_POST['category'];
	$UID = $_SESSION["user_id"];
	$identity = $_SESSION["identity"];
	

	$shop_sql = "SELECT * FROM shop";
	$menu_sql = "SELECT * FROM sell_object";
	$shop_case = "";
	$menu_case = "";

	if($shop!=''){
		
		$tmp = "shop_name LIKE '%$shop%' ";
		if($shop_case==""){
			$shop_case = $shop_case." WHERE ".$tmp;
		}
		else{
			$shop_case = $shop_case." and ".$tmp;
		}
	}

	$user_sql = "SELECT * FROM user WHERE UID = '$UID' ";
	$result = $link->query($user_sql);
    if ($result->num_rows > 0) {
        $row=$result->fetch_assoc();
		$user_latitude = $row['latitude'];	
		$user_longtitude = $row['longtitude'];
	}


	if($price_low!=''){
		$tmp = "price>='$price_low' ";
		if($menu_case=="")
			$menu_case = $menu_case." WHERE ".$tmp;
		else{
			$menu_case = $menu_case." and ".$tmp;
		}
	}

	if($price_high!=''){
		$tmp = "price<='$price_high' ";
		if($menu_case==""){
			$menu_case = $menu_case." WHERE ".$tmp;
		}
		else{
			$menu_case = $menu_case." and ".$tmp;
		}
	}

	if($meal!=''){
		$tmp = "meal_name LIKE '%$meal%' ";
		if($menu_case==""){
			$menu_case = $menu_case." WHERE ".$tmp;
		}
		else{
			$menu_case = $menu_case." and ".$tmp;
		}
	}

	if($category!=''){
		$sql = "SELECT * FROM sql WHERE shop_category LIKE '%$category%' ";
		$tmp = "shop_category LIKE '%$category%' ";
		if($shop_case==""){
			$shop_case = $shop_case." WHERE ".$tmp;
		}
		else{
			$shop_case = $shop_case." and ".$tmp;
		}
	}
	
	#search case for sql
	$shop_sql = $shop_sql.$shop_case;
	$shop_result = $link->query($shop_sql);
	$shop_SID = array();
	$search_SID = array();
	while($shop_row=$shop_result->fetch_assoc()){
		$distance_number = pow( ($shop_row['latitude'] - $user_latitude),2) + pow( ($shop_row['longitude'] - $user_longtitude),2);
		
		if($distance_number<800){
			$distance_case = "near";
				
		}
		else if($distance_number>=800 && $distance_number<20000){
			$distance_case = "medium";
		}
		else{
			$distance_case = "far";
		}

		if($distance=="all"){
			array_push($shop_SID, $shop_row['SID']);
		}
		else if($distance_case==$distance){
			array_push($shop_SID, $shop_row['SID']);
		}
		
	}
	$shop_SID = array_unique($shop_SID);

	if($menu_case!=""){
		$menu_sql = $menu_sql.$menu_case;
		for ($index=0; $index<count($shop_SID); $index++){
			$search_sql = $menu_sql." and SID='$shop_SID[$index]' ";
			$search_result = $link->query($search_sql);
			if ($search_result->num_rows > 0) {
				array_push($search_SID, $shop_SID[$index]);
			}
		}
		 $_SESSION["search_SID"] = $search_SID;
	}
	else{
		$_SESSION["search_SID"] = $shop_SID;
	}
	//print_r($_SESSION["search_SID"] );

	if($identity=="customer"){
        echo "<script>location.replace('../shop_before_register.php');</script>";
        //header('Location: ../nav_test.php');
    }
    else{
        echo "<script>location.replace('../shop_after_register.php');</script>";
    }
	
?>