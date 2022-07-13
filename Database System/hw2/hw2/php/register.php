<!DOCTYPE html>
<html>
    <body>
        <?php

            session_start();

            $host = "localhost";
            $dbuser = "root";
            $dbpasswd = "";
            $db_name = "hw2";

            if($_SERVER["REQUEST_METHOD"] == "POST"){

                $shop_name = $_POST["shop_name"];
                $shop_category = $_POST["shop_category"];
                $latitude = (float)$_POST["latitude"];
                $longtitude = (float)$_POST["longtitude"];
                # user_id & user_phone from session variable
                $user_id = $_SESSION["user_id"];
                $user_phone = $_SESSION["phone"];

                $link = mysqli_connect($host, $dbuser, $dbpasswd, $db_name);
                if(!$link)
                    echo "*** ERROR *** : MySQL not connected </br>". mysqli_connect_error();
                

                

                # check if some value in form is blank
                if($shop_name == '' || $shop_category == '' || $latitude == '' || $longtitude == ''){
                    echo "<script>alert('Register failed : value of some property in form is blank');</script>";
                    echo "<script>window.location.href = 'https://localhost/hw2/nav_test.php#menu1'</script>";
                } 
                
                # check if format of from is correct


                # check if shop name registered
                $row_count = 0;
                $query_shop_duplicate = "SELECT * FROM shop WHERE name='$shop_name'";
                $result = mysqli_query($link, $query_shop_duplicate);
                if($result){
                    $row_count = mysqli_num_rows($result);
                    if($row_count > 0){
                        echo "<script>alert('Register failed : Shop name registered');</script>";
                        echo "<script>window.location.href = 'https://localhost/hw2/nav_test.php#menu1'</script>";
                    }
                }
                else
                    echo "*** ERROR *** : target table return error </br>". mysqli_connect_error();

                # get the length of table
                $row_count = 0;
                $query_row_count = "SELECT * FROM shop";
                $result = mysqli_query($link, $query_row_count);
                if($result)
                    $row_count = mysqli_num_rows($result);
                else
                    echo "*** ERROR *** : target table return error </br>". mysqli_connect_error();

                                 
                # insert tuple into table
                $insert_msg = "INSERT INTO shop(SID, UID, name, latitude, longtitude, phone, order_type) VALUES('$row_count','$user_id','$shop_name', $latitude, $longtitude, '$user_phone','$shop_category')";
                if(!mysqli_query($link, $insert_msg)){
                    mysqli_close($link);
                    echo "** ERROR *** : insert new shop into shop is failed";
                    echo "<script>window.location.href = 'https://localhost/hw2/nav_test.php#menu1'</script>";
                }
                else{
                    $_SESSION["shop_id"] = $row_count;
                    $_SESSION["shop_name"] = $shop_name;
                    $_SESSION["shop_category"] = $shop_category;
                    $_SESSION["shop_latitude"] = $latitude;
                    $_SESSION["shop_longtitude"] = $longtitude;

                    # change identity of current user
                    $cur_user_id = $_SESSION["user_id"];
                    $update_user_identity = "UPDATE user SET identity='manager' WHERE UID='$cur_user_id'";
                    if(!mysqli_query($link, $update_user_identity)){
                        mysqli_close($link);
                        echo "** ERROR *** : user identity change failed";
                        echo "<script>window.location.href = 'https://localhost/hw2/nav_test.php#menu1'</script>";
                    }
                    else{
                        mysqli_close($link);
                        $_SESSION["identity"] = "manager";
                        echo "<script>alert('Register success');</script>";
                        echo "<script>window.location.href = 'https://localhost/hw2/nav_test.php#menu1'</script>";
                    }


                    
                }

                
                
                

            }

            
        ?>
    </body>
</html>

