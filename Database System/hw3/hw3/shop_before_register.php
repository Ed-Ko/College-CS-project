<!doctype html>
<html lang="en">

<head>
  <!-- Required meta tags -->
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- Bootstrap CSS -->

  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">

  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
  <title>Hello, world!</title>
  <script>
	function adder(PID){
        var id = PID;
        var str_PID = id.toString();
		var count_id = "countnumP".concat(str_PID);
        //var count_id = "countnumP1";
		var count=document.getElementById(count_id).value;
		count=parseInt(count)+1;
		document.getElementById(count_id).value=count;
        
	}
	function minuser(PID){
        var str_PID = PID.toString();
		var count_id = "countnumP".concat(str_PID);
		var count = document.getElementById(count_id).value;
		if(count<=0){
			count=0;
		}else{
			count=parseInt(count)-1;
		}	
		document.getElementById(count_id).value=count;
	}
    function order_search(){

    }
  </script>
</head>

<body>
    <?php 
		session_start();	
		if(!isset($_SESSION["user_id"])){
			echo "<script>location.replace('entrance.php');</script>";
		}
	?>

    <nav class="navbar navbar-inverse">
        <div class="container-fluid">
            <div class="navbar-header" style="text-align:center; width:1000px">
                <div >
                    <a class="navbar-brand " href="#">WebSiteName</a>
                    <a class="navbar-brand " style="width: 800px;"> </a>
                    <a class="navbar-brand" href="./php/logout.php" style="text-align:right;right: 0 auto">logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container">
        <ul class="nav nav-tabs">
            <li class="active"><a href="#home">Home</a></li>
            <li><a href="#menu1">shop</a></li>
            <li><a href="#myorder">MyOrder</a></li>
            <li><a href="#shop_order">Shop Order</a></li>
            <li><a href="#transaction_record">Transaction Record</a></li>
        </ul>

    <div class="tab-content">
        <div id="home" class="tab-pane fade in active">
            <h3>Profile</h3>
            <div class="row">
                <div class="col-xs-12">
                    <?php
                    echo "Account : " . $_SESSION["account"]  .", ";  # name
                    echo "identity: " . $_SESSION["identity"]  .", ";
                    echo "Name: " . $_SESSION["cur_user"] .", ";  # role(user, manager)
                    echo "Phone : " . $_SESSION["phone"] .", ";  # phone
                    echo "Location : " .  $_SESSION["latitude"] .", ".  $_SESSION["longitude"];  # location
                    echo '<button type="button " style="margin-left: 5px;" class=" btn btn-info " data-toggle="modal" data-target="#location">
                            Edit location
                          </button>';
                    echo " WalletBallence: " . $_SESSION["amount"];
                    echo '<button type="button " style="margin-left: 5px;" class=" btn btn-info " data-toggle="modal" data-target="#recharge">
                            Recharge
                          </button>';
                    ?>

                    <div class="modal fade" id="location" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdropLabel" aria-hidden="true">
                        <div class="modal-dialog  modal-sm">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                                    <h4 class="modal-title">Edit location</h4>
                                </div>

                                <form method="post" action="./php/user_edit.php">
                                    <div class="modal-body">
                                        <label class="control-label " for="latitude">latitude</label>
                                        <input type="text" class="form-control" id="latitude" placeholder="enter latitude" name="latitude">
                                        <br>
                                        <label class="control-label " for="longitude">longitude</label>
                                        <input type="text" class="form-control" id="longitude" placeholder="enter longitude" name="longitude">
                                    </div>
                                    <div class="modal-footer">
                                        <button type="submit" class="btn btn-default">Edit</button>
                                    </div>
                                </form>

                            </div>
                        </div>
                    </div>

                    <div class="modal fade" id="recharge" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdropLabel" aria-hidden="true">
                        <div class="modal-dialog  modal-sm">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                                    <h4 class="modal-title">Recharge</h4>
                                </div>

                                <form method="post" action="./php/user_recharge.php">
                                    <div class="modal-body">
                                        <input type="text" class="form-control" id="AddValue" placeholder="enter add value" name="AddValue">
                                    </div>
                                    <div class="modal-footer">
                                        <button type="submit" class="btn btn-default">Add</button>
                                    </div>
                                </form>

                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <h3>Search</h3>
            
            <div class=" row  col-xs-8">
                <form class="form-horizontal" action="./php/shop_search.php" method="post">
                    <div class="form-group">
                        <label class="control-label col-sm-1" for="Shop">Shop</label>
                        <div class="col-sm-5">
                            <input type="text" class="form-control" placeholder="Enter Shop name" name="shop">
                        </div>
                        <label class="control-label col-sm-1" for="distance">distance</label>
                        <div class="col-sm-5">
                            <select class="form-control" id="sel1" name="distance">
                                <option>all</option>
                                <option>near</option>
                                <option>medium </option>
                                <option>far</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="control-label col-sm-1" for="Price">Price</label>
                        <div class="col-sm-2">
                            <input type="text" class="form-control" name="price_low">
                        </div>
                        <label class="control-label col-sm-1" for="~">~</label>
                        <div class="col-sm-2">
                            <input type="text" class="form-control" name="price_high">
                        </div>
                        <label class="control-label col-sm-1" for="Meal">Meal</label>
                        <div class="col-sm-5">
                            <input type="text" list="Meals" class="form-control" id="Meal" placeholder="Enter Meal" name="meal">
                            <datalist id="Meals">
                                <option value="Hamburger">
                                <option value="coffee">
                            </datalist>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="control-label col-sm-1" for="category"> category</label>
                        <div class="col-sm-5">
                            <input type="text" list="categorys" class="form-control" id="category" placeholder="Enter shop category" name="category">
                            <datalist id="categorys">
                                <option value="fast food">
                            </datalist>
                        </div>
                        <button type="submit" style="margin-left: 18px;" class="btn btn-primary">Search</button>
                    </div>
                </form>
            </div>

            <div class="row">
                <div class="  col-xs-8">
                    <table class="table" style=" margin-top: 15px;">
                        <thead>
                            <tr>
                                <th scope="col">#</th>
                                <th scope="col">shop name</th>
                                <th scope="col">shop category</th>
                                <th scope="col">Distance</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                                if(!empty($_SESSION["search_SID"])){

                                    $host = "localhost";
                                    $dbuser = "root";
                                    $dbpasswd = "";
                                    $db_name = "hw2";

                                    $link = new mysqli($host, $dbuser, $dbpasswd, $db_name);
                                    $search_SID = array();
                                    $search_SID = $_SESSION["search_SID"];
                                    $shop_list_id = 0;
                                    $user_latitude = $_SESSION["latitude"];
                                    $user_longitude = $_SESSION["longitude"];

                                    for ($index = 0; $index < count($search_SID); $index++){
                                        $tmp_shop_id = $search_SID[$index];
                                        $shop_sql="SELECT * FROM shop WHERE SID='$search_SID[$index]' " ;
                                        $shop_result=$link->query($shop_sql);
        
                                        if ($shop_result->num_rows > 0) {
                                            while($row = $shop_result->fetch_assoc()){
                                                $distance_number = pow( ($row['latitude'] - $user_latitude),2) + pow( ($row['longitude'] - $user_longitude),2);  
                                                if($distance_number < 800)
                                                    $distance_case = "near";
                                                else if($distance_number >= 800 && $distance_number < 20000)
                                                    $distance_case = "medium";
                                                else
                                                    $distance_case = "far";
            
                                                $shop_list_id = $shop_list_id + 1;
                                                echo '<tr> ';
                                                echo '<th scope="row">'.$shop_list_id.'</th>';
                                                echo '<td>'.$row['shop_name'].'</td>';
                                                echo '<td>'.$row['shop_category'].'</td>';
                                                echo '<td>'.$distance_case.' </td>';
                                                echo '
                                                    <td>
                                                        <button type="button" class="btn btn-info" data-toggle="modal" data-target="#shop' . $shop_list_id .'">
                                                            Open menu
                                                        </button>
                                                    </td>
                                                    </tr>';
                                            }
        
                                        }
                                    }

                                    echo '
                                        </tbody>
                                    </table>';

                                    $shop_list_id = 0;
                                    for ($index = 0; $index < count($search_SID); $index++){

                                        $tmp_shop_id = $search_SID[$index];
                                        $shop_sql="SELECT * FROM shop WHERE SID='$search_SID[$index]' " ;
                                        $shop_result=$link->query($shop_sql);

                                        if ($shop_result->num_rows > 0) {

                                            while($row = $shop_result->fetch_assoc()){
                                                $shop_list_id = $shop_list_id + 1;
                                                echo '
                                                <!-- Modal menu -->

                                                <div class="modal fade" id="shop' . $shop_list_id .'"  data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdropLabel" aria-hidden="true">
                                                    <div class="modal-dialog">
            
                                                        <!-- Modal content-->
                                                        <div class="modal-content">

                                                            <div class="modal-header">
                                                                <button type="button" class="close" data-dismiss="modal">&times;</button>
                                                                <h4 class="modal-title">menu</h4>
                                                            </div>
                                                            
                                                            <form method="post" action="menu_calculate.php?SID='.$tmp_shop_id.'">
                                                            <div class="modal-body">
                                                                <div class="row">
                                                                    <div class="  col-xs-12">
                                                                        <table class="table" style=" margin-top: 15px;">
                                                                            <thead>
                                                                            <tr>
                                                                                <th scope="col">#</th>
                                                                                <th scope="col">Picture</th>
                        
                                                                                <th scope="col">meal name</th>
                        
                                                                                <th scope="col">price</th>
                                                                                <th scope="col">Quantity</th>
                        
                                                                                <th scope="col">Order check</th>
                                                                            </tr>
                                                                            </thead>
                                                                            
                                                                            <tbody>';

                                                                                $menu_sql="SELECT * FROM sell_object WHERE SID='$tmp_shop_id'";
                                                                                $menu_result = $link->query($menu_sql);
                                                                                $menu_list_id = 0;
                                                                                if ($menu_result->num_rows > 0) {
                                                                                    while($menu_row = $menu_result->fetch_assoc()){
                                                                                        $menu_list_id = $menu_list_id + 1;
                                                                                        $menu_PID = $menu_row['PID'];
                                                                                        echo '<tr>';
                                                                                        echo '<th scope="row">'.$menu_list_id.'</th>';
                                                                                        echo '<td>'.'<img width="100" height="100" src="data:'.$menu_row['imgType'].';base64,' . $menu_row['img'] . ' " />';
                                                                                        echo '<td>'.$menu_row['meal_name'].'</td>';
                                                                                        echo '<td>'.$menu_row['price'].'</td>';
                                                                                        echo '<td>'.$menu_row['quantity'].' </td>';
                                                                                        //echo '<td><input type="checkbox" id=" '.$menu_list_id.' " value=" '.$menu_row['meal_name'].' "></td> ';
                                                                                        echo '<td>
                                                                                                
                                                                                                    <button type="button" id="minus" onclick="minuser('.$menu_PID.')">-</button>
                                                                                                    <input type="text" id="countnumP'.$menu_PID.'" name="countnum'.$tmp_shop_id.'[]" value=0 size=3 ></input>
                                                                                                    <button type="button" id="plus" onclick="adder('.$menu_PID.')">+</button>
                                                                                                
                                                                                              </td>';
                                                                                        echo '</tr>';
                                                                                    }
                                                                                }
            
                                                                            echo '
                                                                            </tbody>
                                                                        </table>
                                                                    </div>
                                                                </div>

                                                                <label>Type:
                                                                    <select id="type" name="type">
                                                                        <option>Delivery</option>
                                                                        <option>Pick-up</option>
                                                                    </select>
                                                                </label>
                                                                

                                                            </div>
                                                            
                                                            <div class="modal-footer">
                                                                <button type="submit" class="btn btn-default" >Calculate the price</button>
                                                            </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>';

                                            }
                                        }
                                    }
                                }
                            ?>

                        </tbody>
                    </table>

                </div>

            </div>
        </div>



        <div id="menu1" class="tab-pane fade">

            <h3> Start a business </h3>
            <div class="shop-register">
                <form action="./php/shop_register.php" method="post">
                    <div class="form-group">
                        <div class="row">
                            <div class="col-xs-2">
                                <label for="ex5">shop name</label>
                                <input class="form-control" id="ex5" placeholder="macdonald" type="text" name="shop_name">
                            </div>
                            <div class="col-xs-2">
                                <label for="ex5">shop category</label>
                                <input class="form-control" id="ex5" placeholder="fast food" type="text" name="shop_category">
                            </div>
                            <div class="col-xs-2">
                                <label for="ex6">latitude</label>
                                <input class="form-control" id="ex6" placeholder="24.78472733371133" type="text" name="latitude">
                            </div>
                            <div class="col-xs-2">
                                <label for="ex8">longitude</label>
                                <input class="form-control" id="ex8" placeholder="121.00028167648875" type="text" name="longitude">
                            </div>
                        </div>
                    </div>

                    <div class="row" style=" margin-top: 25px;">
                        <div class=" col-xs-3">
                            <!--<button type="button" class="btn btn-primary" onclick="this.disabled = 'disabled';">register</button>-->
                            <button type="submit" class="btn btn-primary">register</button>
                        </div>
                    </div>
                </form>
            </div>

            <hr>

        </div>

        <div id="myorder" class="tab-pane fade">
            <h3></h3>
            <label class="col-sm-1">distance</label>
            <form class="form-horizontal" action="./php/myorder_search.php" method="post">
                <div class="col-sm-3">
                    <select class="form-control" id="status" name="status">
                        <option>All</option>
                        <option>Finished</option>
                        <option>Unfinished</option>
                        <option>Cancel</option>
                    </select>
                </div>
                <button type="submit" style="margin-left: 18px;" class="btn btn-primary" >Search</button>
            </form>

            <div class="row">
                <div class="  col-xs-12">
                    <table class="table" style=" margin-top: 15px;">
                        <thead>
                            <tr>
                                <th scope="col">Order ID</th>
                                <th scope="col">Status</th>
                                <th scope="col">Start</th>
                                <th scope="col">End</th>
                                <th scope="col">Shop name</th>
                                <th scope="col">Total Price</th>
                                <th scope="col">Oder Details</th>
                                <th scope="col">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                            if(!empty($_SESSION["search_OID"])){
                                $link = new mysqli('localhost','root','','hw2');
                                $search_OID = array();
                                $search_OID = $_SESSION["search_OID"];
                                $order_list_id = 0;

                                for ($index = 0; $index < count($search_OID); $index++){
                                    //$tmp_shop_id = $search_SID[$index];
                                    $order_sql="SELECT * FROM order_list WHERE OIDs='$search_OID[$index]' " ;
                                    $order_result=$link->query($order_sql);
        
                                    if ($order_result->num_rows > 0) {
                                        while($row = $order_result->fetch_assoc()){
                                            $order_list_id = $order_list_id + 1;
                                            echo '<tr> ';
                                            echo '<th scope="row">'.$order_list_id.'</th>';
                                            echo '<td>'.$row['status'].'</td>';
                                            echo '<td>'.$row['start_time'].'</td>';

                                            if($row['end_time']=="0000-00-00 00:00:00"){
                                                echo '<td></td>';
                                            }
                                            else{
                                                echo '<td>'.$row['end_time'].'</td>';
                                            }

                                            echo '<td>'.$row['trader_name'].'</td>';
                                            echo '<td>'.$row['total_price'].'</td>';
                                            echo '
                                                <td>
                                                    <button type="button" class="btn btn-info" data-toggle="modal" data-target="#order' . $order_list_id .'">
                                                        order details
                                                    </button>
                                                </td>';

                                            if($row['status']=="Unfinished"){
                                                echo '
                                                    <form method="post" action="./php/user_cancel.php?OIDs='.$row['OIDs'].'&total_price='.$row['total_price'].'">
                                                    <td><button type="submit" class="btn btn-danger">Cancel</button></td>
                                                    </form>';
                                            }
                                            
                                            echo '</tr>';
                                        }
        
                                    }
                                }

                                echo '
                                    </tbody>
                                </table>';

                                $order_list_id = 0;
                                for ($index = 0; $index < count($search_OID); $index++){
                                    $order_list_id = $order_list_id + 1;
                                    $detail_sql="SELECT * FROM order_list_detail WHERE OIDs='$search_OID[$index]'";
                                    $detail_result=$link->query($detail_sql);

                                    $order_sql="SELECT * FROM order_list WHERE OIDs='$search_OID[$index]' " ;
                                    $order_result=$link->query($order_sql);
                                    $order_row = $order_result->fetch_assoc();
                                    $delivery_fee = $order_row['delivery_fee'];
                                    $total_price = $order_row['total_price'];
                                    $subtotal = $total_price - $delivery_fee;

                                    echo '
                                    <!-- Modal menu -->

                                    <div class="modal fade" id="order'.$order_list_id.'"  data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdropLabel" aria-hidden="true">
                                        <div class="modal-dialog">
            
                                            <!-- Modal content-->
                                            <div class="modal-content">

                                                <div class="modal-header">
                                                    <button type="button" class="close" data-dismiss="modal">&times</button>
                                                    <h4 class="modal-title">Order</h4>
                                                </div>
                                                            
                                                <div class="modal-body">
                                                    <div class="row">
                                                        <div class=" col-xs-12">
                                                            <table class="table" style=" margin-top: 15px">
                                                                <thead>
                                                                <tr>
                                                                    <th scope="col">#</th>
                                                                    <th scope="col">Picture</th>
                                                                    <th scope="col">meal name</th>
                                                                    <th scope="col">price</th>
                                                                    <th scope="col">Order Quantity</th>
                                                                </tr>
                                                                </thead>
                                                                            
                                                                <tbody>';
                                                                $menu_list_id = 0;
                                                                if ($detail_result->num_rows > 0) {
                                                                    while($detail_row = $detail_result->fetch_assoc()){
                                                                        
                                                                        $menu_list_id += 1;
                                                                        echo '<tr>';
                                                                        echo '<th scope="row">'.$menu_list_id.'</th>';
                                                                        echo '<td>'.'<img width="100" height="100" src="data:'.$detail_row['imgType'].';base64,' . $detail_row['img'] . ' " />';
                                                                        echo '<td>'.$detail_row['meal_name'].'</td>';
                                                                        echo '<td>'.$detail_row['price'].'</td>';
                                                                        echo '<td>'.$detail_row['order_quantity'].' </td>';
                                                                        echo '</tr>';
                                                                        
                                                                    }
                                                                }
                                                            echo '
                                                                </tbody>
                                                            </table>
                                                            <hr>
                                                            <div style="text-align:right;widht:300px;line-height:30px; font-size:22px" >
                                                                Subtotal &nbsp&nbsp&nbsp&nbsp&nbsp&nbsp$'.$subtotal.'
                                                            </div>
                                                            <div style="text-align:right;widht:300px;line-height:30px; font-size:16px" >
                                                                Delivery fee &nbsp&nbsp&nbsp$'.$delivery_fee.'
                                                            </div>
                                                            <div style="text-align:right;widht:300px;line-height:30px; font-size:22px" >
                                                                Total Price &nbsp&nbsp&nbsp&nbsp&nbsp&nbsp$'.$total_price.'
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>';

                                }

                            }
                        ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

        <div id="shop_order" class="tab-pane fade">
            <h3></h3>
            <label class="col-sm-1">distance</label>
            <form class="form-horizontal" action="/php/shop_order.php" method="post">
                <div class="col-sm-3">
                    <select class="form-control" id="shop_order_status" name="shop_order_status">
                        <option>All</option>
                        <option>Finished</option>
                        <option>Unfinished</option>
                        <option>Cancel</option>
                    </select>
                </div>
                <button type="submit" style="margin-left: 18px;" class="btn btn-primary" >Search</button>
            </form>

            <div class="row">
                <div class="  col-xs-12">
                    <table class="table" style=" margin-top: 15px;">
                        <thead>
                            <tr>
                                <th scope="col">Order ID</th>
                                <th scope="col">Status</th>
                                <th scope="col">Start</th>
                                <th scope="col">End</th>
                                <th scope="col">Shop name</th>
                                <th scope="col">Total Price</th>
                                <th scope="col">Order Details</th>
                                <th scope="col">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

        <div id="transaction_record" class="tab-pane fade">
            <h3></h3>
            <label class="col-sm-1">Action</label>
            <form class="form-horizontal" action="./php/transaction_search.php" method="post">
                <div class="col-sm-3">
                    <select class="form-control" id="transaction_status" name="transaction_status">
                        <option>All</option>
                        <option>Payment</option>
                        <option>ReceiveMoney</option>
                        <option>Recharge</option>
                    </select>
                </div>
                <button type="submit" style="margin-left: 18px;" class="btn btn-primary" >Search</button>
            </form>

            <div class="row">
                <div class="  col-xs-12">
                    <table class="table" style=" margin-top: 15px;">
                        <thead>
                            <tr>
                                <th scope="col">Record ID</th>
                                <th scope="col">Action</th>
                                <th scope="col">Time</th>
                                <th scope="col">Trader</th>
                                <th scope="col">Amount change</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                            if(!empty($_SESSION['transaction_record'])){
                                $link = new mysqli('localhost','root','','hw2');
                                $transaction_search_OID = array();
                                $transaction_search_OID =$_SESSION['transaction_record'];
                                $record_list_id = 0;

                                for ($index = 0; $index < count($transaction_search_OID); $index++){
                                    $record_sql="SELECT * FROM transaction_record WHERE RID='$transaction_search_OID[$index]' " ;
                                    $records_result=$link->query($record_sql);
        
                                    if ($records_result->num_rows > 0) {
                                        while($row = $records_result->fetch_assoc()){
                                            $record_list_id = $record_list_id + 1;
                                                echo '<tr> ';
                                                echo '<th scope="row">'.$record_list_id.'</th>';
                                                echo '<td>'.$row['action'].'</td>';
                                                echo '<td>'.$row['time'].'</td>';
                                                echo '<td>'.$row['trader'].'</td>';
                                                echo '<td>'.$row['amount_change'].'</td>';
                                                echo '</tr>';
                                            
                                        }
                                    }
                                }

                                echo '
                                    </tbody>
                                </table>';

                                

                            }
                        ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

      </div>
  </div>

  <!-- Option 1: Bootstrap Bundle with Popper -->
  <!-- <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ka7Sk0Gln4gmtz2MlQnikT1wXgYsOg+OMhuP+IlRH9sENBO0LRn5q+8nbTov4+1p" crossorigin="anonymous"></script> -->
  <script>
    $(document).ready(function () {
      $(".nav-tabs a").click(function () {
        $(this).tab('show');
      });
    });
  </script>

  <!-- Option 2: Separate Popper and Bootstrap JS -->
  <!--
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.10.2/dist/umd/popper.min.js" integrity="sha384-7+zCNj/IqJ95wo16oMtfsKbZ9ccEh31eOz1HGyDuCQ6wgnyJNSYdrPa03rtR1zdB" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.min.js" integrity="sha384-QJHtvGhmr9XOIpI6YVutG+2QOK9T+ZnN4kzFN1RtK3zEFEIsxhlmWl5/YESvpZ13" crossorigin="anonymous"></script>
    -->
</body>

</html>