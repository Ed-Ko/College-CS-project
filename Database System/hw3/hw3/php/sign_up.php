<!DOCTYPE html>
<html>
    <body>
        <?php
            
            $host = "localhost";
            $dbuser = "root";
            $dbpasswd = "";
            $db_name = "hw2";

            if($_SERVER["REQUEST_METHOD"] == "POST"){
                
                $user_name = $_POST["user_name"];
                $phone = $_POST["phone"];
                $account_name = $_POST["account_name"];
                $passwd = $_POST["passwd"];
                $re_passwd = $_POST["re_passwd"];
                $insert_latitude = $_POST["latitude"];
                $insert_longitude = $_POST["longitude"];

                $link = mysqli_connect($host, $dbuser, $dbpasswd, $db_name);
                if(!$link)
                    echo "*** ERROR *** : MySQL not connected </br>". mysqli_connect_error();

                # check if form is empty
                
                $check = true;
                $error_msg = "";
                # check user name format
                if($user_name == ''){
                    $error_msg = $error_msg . "Sign up failed : NAME 欄位空白\\n";
                    $check = false;
                }
                else if(!preg_match("/^[A-Za-z]+ ?[A-Za-z]+$/", $user_name)){
                    $error_msg = $error_msg . "Sign up failed : NAME format error\\n";
                    $check = false;
                }

                # check password format
                if($passwd == ''){
                    $error_msg = $error_msg . "Sign up failed : PASSWORD 欄位空白\\n";
                    $check = false;
                }
                else if(!preg_match("/^[A-Za-z0-9]*$/", $passwd)){
                    $error_msg = $error_msg . "Sign up failed : PASSWORD format error\\n";
                    $check = false;
                }

                # check if password and re-typed password are the same
                if($re_passwd == ''){
                    $error_msg = $error_msg . "Sign up failed : RE-TYPE PASSWORD 欄位空白\\n";
                    $check = false;
                }
                else if($passwd != $re_passwd){
                    $error_msg = $error_msg . "Sign up failed : RE-TYPE PASSWORD mismatched PASSWORD\\n";
                    $check = false;
                }
                        
                    
                # check user account
                if($account_name == ''){
                    $error_msg = $error_msg . "Sign up failed : ACCOUNT 欄位空白\\n";
                    $check = false;
                }
                else if(!preg_match("/^[A-Za-z0-9]*$/", $account_name)){
                    $error_msg = $error_msg . "Sign up failed : ACCOUNT format error\\n";
                    $check = false;
                }
                    
                # check user phone 
                if($phone == ''){
                    $error_msg = $error_msg . "Sign up failed : PHONE 欄位空白\\n";
                    $check = false;
                }
                else if(!preg_match("/^\d{10}$/", $phone)){
                    $error_msg = $error_msg . "Sign up failed : PHONE format error\\n";
                    $check = false;
                }

                if($insert_latitude == ''){
                    $error_msg = $error_msg . "Sign up failed : LATITUDE 欄位空白\\n";
                    $check = false;
                }
                else if((float)$insert_latitude > 90 || (float)$insert_latitude < -90 || !is_numeric($insert_latitude)){
                    $error_msg = $error_msg . "Sign up failed : user position(LATITUDE) format error\\n";
                    $check = false;
                }
                    
                if($insert_longitude == ''){
                    $error_msg = $error_msg . "Sign up failed : LONGITUDE 欄位空白\\n";
                    $check = false;
                }
                else if($insert_longitude > 180 || $insert_longitude < -180 || !is_numeric($insert_longitude)){
                    $error_msg = $error_msg . "Sign up failed : user position(LONGITUDE) format error\\n";
                    $check = false;
                }

                if($check == false){
                    echo '<script type="text/javascript">alert("'.$error_msg.'");</script>';
                    echo "<script>history.go(-1);</script>";
                    //echo "<script>window.location.href = 'http://localhost/hw2/sign-up.html'</script>";
                }
                else{
                    # check if user is already registered
                    $query_user_registered = "SELECT * FROM user WHERE account='$account_name'";
                    $query_result = mysqli_query($link, $query_user_registered);
                    if($query_result){
                            
                        # user name already registered
                        if(mysqli_num_rows($query_result) != 0){
                            echo "<script>alert('Sign up failed : ACCOUNT already registered');</script>";
                            echo "<script>history.go(-1);</script>";
                            //echo "<script>window.location.href = 'http://localhost/hw2/sign-up.html'</script>";
                        }
                        # user name not registered
                        else{
                            $row_count = 0;
                            $query_row_count = "SELECT * FROM user";
                            $result = mysqli_query($link, $query_row_count);
                            if($result)
                                $row_count = mysqli_num_rows($result);
                            else
                                echo "*** ERROR *** : target table return error </br>". mysqli_connect_error();

                            # encrypt user password
                            $encrypted_passwd = hash('sha512', $passwd);
                                
                            # insert user info into database
                            $insert_msg = "INSERT INTO user(UID, account, password, name, identity, latitude, longitude, phone, amount) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?)";
                                
                            # sql injection protection
                            $mysqli = new mysqli($host, $dbuser, $dbpasswd, $db_name);
                            $stmt = $mysqli->prepare($insert_msg);
                            $stmt->bind_param("issssddsi", $UID, $account, $password, $name, $identity, $latitude, $longitude, $phone, $amount);
                            $UID = $row_count;
                            $account = $account_name;
                            $password = $encrypted_passwd;
                            $name = $user_name;
                            $identity = 'customer';
                            $latitude = $insert_latitude;
                            $longitude = $insert_longitude;
                            $phone = $phone;
                            $amount = 0;
                            $stmt->execute();

                            mysqli_close($link);
                            echo "<script>alert('Sign up success');</script>";
                            echo "<script>location.replace('../entrance.php');</script>";
                            //echo "<script>window.location.href = 'http://localhost/index.html'</script>";

                        }
                    }
                }
                 
            }

        ?>
    </body>
</htnl>