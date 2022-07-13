<?php

    session_start();

    $host = "localhost";
    $dbuser = "root";
    $dbpasswd = "";
    $db_name = "hw2";
    
    if($_SERVER["REQUEST_METHOD"] == "POST"){
        $meal_id = (int)$_POST["commodity_id"];
        $new_price = (int)$_POST["edit_price"];
        $new_quantity = (int)$_POST["edit_quantity"];
        
        $error_msg = '';

        if($new_price == '')
            $error_msg = $error_msg . "*** ERROR *** : price in the form is blank\\n";
        else if((int)$new_price < 0)
            $error_msg = $error_msg . "*** ERROR *** : price in the form must be non-negative\\n";
        
        
        if($new_quantity == '')
            $error_msg = $error_msg . "*** ERROR *** : quantity in the form is blank\\n";
        else if((int)$new_quantity < 0)
            $error_msg = $error_msg . "*** ERROR *** : quantity in the form must be non-negative\\n";
        

        


        # change the price and quantity of commodity
        # sql injection protect
        $update_commodity = "UPDATE commodity SET price=?, quantity=? WHERE PID=$meal_id";
        $mysqli = new mysqli($host, $dbuser, $dbpasswd, $db_name);
        $stmt = $mysqli->prepare($update_commodity);
        $stmt->bind_param("ii", $price, $quantity);
        $price = $new_price;
        $quantity = $new_quantity;
        $stmt->execute();
       
        
        echo "<script>alert('Meal edit success');</script>";
        echo "<script>window.location.href = 'https://localhost/hw2/nav_test.php'</script>";


    }
     


?>