<?php
    session_start();
    if(!isset($_SESSION["user_id"])){
		echo "<script>location.replace('entrance.php');</script>";
	}
    $_SESSION['ordernum'] = array();
	$link = new mysqli('localhost','root','','hw2');
    $SID = $_GET['SID'];
    $type = $_POST["type"];
    $error_num = 0;
    $user_latitude = $_SESSION["latitude"];
    $user_longitude = $_SESSION["longitude"];
    $identity = $_SESSION["identity"];

    $ordernum = @$_POST["countnum".$SID];
    if($ordernum==null){
        echo "<script>alert('店家尚無商品，請待會再下單');</script>";
        if($identity=="customer"){
            echo "<script>location.replace('shop_before_register.php');</script>";
        }
        else{
            echo "<script>location.replace('shop_after_register.php');</script>";
        }
    }

    $menu_sql="SELECT * FROM sell_object WHERE SID='$SID'";
    $menu_result = $link->query($menu_sql);
    $menu_index = 0;
    $number = 0;

    if ($menu_result->num_rows != count($ordernum)){
        echo "<script>alert('ERROR:商家商品有所更動，請重新下單');</script>";
        if($identity=="customer"){
            echo "<script>location.replace('shop_before_register.php');</script>";
        }
        else{
            echo "<script>location.replace('shop_after_register.php');</script>";
        }
    }
    if ($menu_result->num_rows > 0) {
        while($menu_row = $menu_result->fetch_assoc()){
            if($ordernum[$menu_index]>$menu_row['quantity'] || $ordernum[$menu_index]<0 || !is_numeric($ordernum[$menu_index]) ){
                $error_num+=1;
            }
            if($ordernum[$menu_index]>0){
                $number+=1;
            }
            $menu_index += 1;
        }
    }
    if($number==0){
        echo "<script>alert('Error:至少要下訂一個商品');</script>";
        if($identity=="customer"){
            echo "<script>location.replace('shop_before_register.php');</script>";
        }
        else{
            echo "<script>location.replace('shop_after_register.php');</script>";
        }
    }
    if($error_num>0){
        echo "<script>alert('Error:有商品數目為非0或小於商品庫存的正整數');</script>";
        if($identity=="customer"){
            echo "<script>location.replace('shop_before_register.php');</script>";
        }
        else{
            echo "<script>location.replace('shop_after_register.php');</script>";
        }
    }
    else{

        echo '
            <!DOCTYPE html>
            <html>
            <head>
                <title>order</title>
                <script>
                    function cancel(){
                        history.go(-1);
                    }
                </script>
            </head>
            <body>
                <div style="text-align:center;widht:300px;line-height:60px; font-size:32px" >
                    Order
                </div>
                <hr align=center width="50%" size=1 color="black">
                    <table cellspacing="18" align="center">
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
                            $_SESSION['ordernum'] = $ordernum;
                            $shop_sql="SELECT * FROM shop WHERE SID='$SID'";
                            $shop_result = $link->query($shop_sql);    
                            $shop_row = $shop_result->fetch_assoc();
                            $shop_latitude = $shop_row['latitude'];
                            $shop_longitude = $shop_row['longitude'];

                            //calculate distance of kilometer
                            $theta = $user_longitude - $shop_longitude; 
                            $distance_value = (sin(deg2rad($user_latitude)) * sin(deg2rad($shop_latitude))) + (cos(deg2rad($user_latitude)) * cos(deg2rad($shop_latitude)) * cos(deg2rad($theta))); 
                            $distance_value = acos(min(max($distance_value,-1.0),1.0)); 
                            $distance_value = rad2deg($distance_value); 
                            $distance_value = $distance_value * 60 * 1.1515 * 1.609344;
                            

                            $menu_sql="SELECT * FROM sell_object WHERE SID='$SID'";
                            $menu_result = $link->query($menu_sql);
                            $menu_index = 0;
                            $menu_list_id = 0;
                            $subtotal = 0;
                            $delivery_fee = 0;
                            $total_price = 0;
                            if($type=="Delivery"){
                                $delivery_fee = $distance_value*10;
                                $delivery_fee = round($delivery_fee);
                                if($delivery_fee<10){
                                    $delivery_fee = 10;
                                }
                            }
                            if ($menu_result->num_rows > 0) {
                                while($menu_row = $menu_result->fetch_assoc()){
                                    if($ordernum[$menu_index]>0){
                                        $menu_PID = $menu_row['PID'];
                                        $menu_list_id += 1;                   
                                        echo '<tr>';
                                        echo '<th scope="row">'.$menu_list_id.'</th>';
                                        echo '<td>'.'<img width="100" height="100" src="data:'.$menu_row['imgType'].';base64,' . $menu_row['img'] . ' " />';
                                        echo '<td>'.$menu_row['meal_name'].'</td>';
                                        echo '<td>'.$menu_row['price'].'</td>';
                                        echo '<td>'.$ordernum[$menu_index].' </td>';
                                        echo '</tr>';
                                        $subtotal+=($ordernum[$menu_index]*$menu_row['price']);
                                    }
                                    $menu_index +=1;
                                }
                            }
                            $total_price = $subtotal + $delivery_fee;
            
                        echo '
                        </tbody>
                    </table>
                <div style="text-align:center;widht:300px;line-height:30px; font-size:22px" >
                    Subtotal &nbsp&nbsp&nbsp&nbsp&nbsp&nbsp$'.$subtotal.'
                </div>
                <div style="text-align:center;widht:300px;line-height:30px; font-size:16px" >
                    Delivery fee &nbsp&nbsp&nbsp$'.$delivery_fee.'
                </div>
                <div style="text-align:center;widht:300px;line-height:30px; font-size:22px" >
                    Total Price &nbsp&nbsp&nbsp&nbsp&nbsp&nbsp$'.$total_price.'
                </div>
                <form method="post" action="php/menu_order.php?SID='.$SID.'&delivery_fee='.$delivery_fee.'">
                <div align="center">
                    <button type="button" class="btn btn-default" style="margin: 30px" onclick="cancel()">Cancel</button>
                    <button type="submit" class="btn btn-default">Order</button>
                </div>
                </form>
            </body>
            </html>
            ';
    }
?>