<!DOCTYPE html>
<html>
    <body>
        <?php
            header("Content-type: text/html; charset=utf-8");

            # start session
            $_SESSION["search_SID"] = array();
            $_SESSION["user_id"] = -1;
            $_SESSION["cur_user"] = "";
            $_SESSION["identity"] = "";
            $_SESSION["phone"] = "";
            $_SESSION["latitude"] = "";
            $_SESSION["longitude"] = "";
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
                $query_user_valid = "SELECT * FROM user WHERE account=? AND password=?";
                $stmt = $link->prepare($query_user_valid);
                $stmt->bind_param('ss', $account, $encrypted_passwd); // 's' specifies the variable type => 'string'
                $stmt->execute();

                $query_result = $stmt->get_result();

                if(mysqli_num_rows($query_result) == 0){
                    mysqli_close($link);
                    echo "<script>alert('Login failed : ACCOUNT or PASSWORD error');</script>";
                    echo "<script>location.replace('../entrance.php');</script>";
                    //echo "<script>window.location.href = 'http://localhost/hw2/index.html'</script>";
                }
                else{

                    session_start();

                    # loading user info from query result
                    $obj = mysqli_fetch_assoc($query_result);
                    $_SESSION["account"] = $obj["account"];
                    $_SESSION["user_id"] = $obj["UID"];
                    $_SESSION["cur_user"] = $obj["name"];
                    $_SESSION["identity"] = $obj["identity"];
                    $_SESSION["phone"] = $obj["phone"];
                    $_SESSION["latitude"] = $obj["latitude"];
                    $_SESSION["longitude"] = $obj["longitude"];
                    $_SESSION["amount"] = $obj["amount"];
                    
                    # if user is shop manager, get the info of shop
                    /*if($_SESSION["identity"] == "manager"){
                        $cur_user_id = $_SESSION["user_id"];
                        $query_shop_info = "SELECT * FROM shop WHERE UID='$cur_user_id'";
                        $query_shop_result = mysqli_query($link, $query_shop_info);
                        if(mysqli_num_rows($query_shop_result) != 0){
                            $row_now = mysqli_fetch_assoc($query_shop_result);
                            $_SESSION["shop_id"] = $row_now["SID"];
                        }
                        else{
                            mysqli_close($link);
                            echo "<script>alert('Error : User is manager but not found shop associated in table shop');</script>";
                            echo "<script>location.replace('../nav_test2.php');</script>";
                            //echo "<script>window.location.href = 'http://localhost/hw2/nav_test.php'</script>";
                        }

                    }*/
                    mysqli_close($link);
                    echo "<script>alert('Login success');</script>";

                    # if user is shop manager, go to website can't register shop again
                    if($_SESSION["identity"] == "manager"){
                        echo "<script>location.replace('../shop_after_register.php');</script>";
                    }
                    else{
                        echo "<script>location.replace('../shop_before_register.php');</script>";
                    }
                    
                    //echo "<script>window.location.href = 'http://localhost/hw2/nav_test.php'</script>";
                }
                
            }


        ?>
    </body>
</htnl>