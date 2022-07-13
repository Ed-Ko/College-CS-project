<!DOCTYPE html>
<html>
    <body>
        <?php

            # start session
            $_SESSION["user_id"] = -1;
            $_SESSION["account"] = "";
            $_SESSION["identity"] = "";
            $_SESSION["phone"] = "";
            $_SESSION["latitude"] = "";
            $_SESSION["longtitude"] = "";
            $_SESSION["amount"] = -1;
            

            $host = "localhost";
            $dbuser = "root";
            $dbpasswd = "";
            $db_name = "hw2";

            if($_SERVER["REQUEST_METHOD"] == "POST"){

                $account = $_POST['account_name'];
                $passwd = $_POST['passwd'];
                
                $link = mysqli_connect($host, $dbuser, $dbpasswd, $db_name);
                if(!$link)
                    echo "*** ERROR *** : MySQL not connected </br>". mysqli_connect_error();

                # encrypt user password
                $encrypted_passwd = hash('sha512', $passwd);

                # query into database to find user info
                $query_user_valid = "SELECT * FROM user WHERE account='$account' AND password='$encrypted_passwd'";
                $query_result = mysqli_query($link, $query_user_valid);

                if(mysqli_num_rows($query_result) == 0){
                    mysqli_close($link);
                    echo "<script>alert('Login failed : user not exist');</script>";
                    echo "<script>window.location.href = 'https://localhost/hw2/'</script>";
                }
                else{

                    session_start();

                    # loading user info from query result
                    $obj = mysqli_fetch_assoc($query_result);
                    $_SESSION["user_id"] = $obj["UID"];
                    $_SESSION["account"] = $obj["name"];
                    $_SESSION["identity"] = $obj["identity"];
                    $_SESSION["phone"] = $obj["phone"];
                    $_SESSION["latitude"] = $obj["latitude"];
                    $_SESSION["longtitude"] = $obj["longtitude"];
                    $_SESSION["amount"] = $obj["amount"];
                    
                    # if user is shop manager, get the info of shop
                    if($_SESSION["identity"] == "manager"){
                        $cur_user_id = $_SESSION["user_id"];
                        $query_shop_info = "SELECT * FROM shop WHERE UID='$cur_user_id'";
                        $query_shop_result = mysqli_query($link, $query_shop_info);
                        if(mysqli_num_rows($query_shop_result) != 0){
                            $row_now = mysqli_fetch_assoc($query_shop_result);
                            $_SESSION["shop_id"] = $row_now["SID"];
                            $_SESSION["shop_name"] = $row_now["name"];
                            $_SESSION["shop_category"] = $row_now["order_type"];
                            $_SESSION["shop_latitude"] = $row_now["latitude"];
                            $_SESSION["shop_longtitude"] = $row_now["longtitude"];




                        }
                        else{
                            mysqli_close($link);
                            echo "<script>alert('Error : User is manager but not found shop associated in table shop');</script>";
                            echo "<script>window.location.href = 'https://localhost/hw2/nav_test.php'</script>";
                        }

                    }
                    mysqli_close($link);
                    echo "<script>alert('Login success');</script>";
                    echo "<script>window.location.href = 'https://localhost/hw2/nav_test.php'</script>";
                }


                
            }


        ?>
    </body>
</htnl>