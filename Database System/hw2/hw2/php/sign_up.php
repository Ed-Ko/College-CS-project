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
                $insert_latitude = (float)$_POST["latitude"];
                $insert_longtitude = (float)$_POST["longtitude"];

                $link = mysqli_connect($host, $dbuser, $dbpasswd, $db_name);
                if(!$link)
                    echo "*** ERROR *** : MySQL not connected </br>". mysqli_connect_error();

                # check if form is empty
                if($user_name == '' || $phone == '' || $account_name == '' || $passwd == '' || $re_passwd == '' || $insert_latitude == '' || $insert_longtitude == ''){
                    echo "<script>alert('Sign up failed : Some info in the form is empty');</script>";
                    echo "<script>window.location.href = 'https://localhost/hw2/sign-up.html'</script>";
                }

                # check if form format is correct  
                else{


                    $check = true;
                    $error_msg = "";
                    # check user name format
                    if(!preg_match("/^[A-Za-z]+ ?[A-Za-z]+$/", $user_name)){
                        $error_msg = $error_msg . "Sign up failed : user name format error\\n";
                        $check = false;
                    }

                    

                    # check password format
                    if(!preg_match("/^[A-Za-z0-9]*$/", $passwd)){
                        $error_msg = $error_msg . "Sign up failed : user password format error\\n";
                        $check = false;
                    }

                    # check if password and re-typed password are the same
                    if($passwd != $re_passwd){
                        $error_msg = $error_msg . "Sign up failed : Password mismatched\\n";
                        $check = false;
                    }
                        
                    
                    # check user account
                    if(!preg_match("/^[A-Za-z0-9]*$/", $account_name)){
                        $error_msg = $error_msg . "Sign up failed : user account name format error\\n";
                        $check = false;
                    }
                    
                    # check user phone 
                    if(!preg_match("/^\d{10}$/", $phone)){
                        $error_msg = $error_msg . "Sign up failed : user phone format error\\n";
                        $check = false;
                    }

                    if((float)$insert_latitude > 180 || (float)$insert_latitude < -180){
                        $error_msg = $error_msg . "Sign up failed : user position(latitude) format error\\n";
                        $check = false;
                    }
                    
                    if((float)$insert_longtitude > 90 || (float)$insert_longtitude < -90){
                        $error_msg = $error_msg . "Sign up failed : user position(longtitude) format error\\n";
                        $check = false;
                    }

                    if($check == false){
                        echo '<script type="text/javascript">alert("'.$error_msg.'")</script>';
                        echo "<script>window.location.href = 'https://localhost/hw2/sign-up.html'</script>";
                    }
                    else{
                        # check if user is already registered
                        $query_user_registered = "SELECT * FROM user WHERE name='$user_name'";
                        $query_result = mysqli_query($link, $query_user_registered);
                        if($query_result){
                            
                            # user name already registered
                            if(mysqli_num_rows($query_result) != 0){
                                echo "<script>alert('Sign up failed : User name already registered');</script>";
                                echo "<script>window.location.href = 'https://localhost/hw2/sign-up.html'</script>";
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
                                $insert_msg = "INSERT INTO user(UID, account, password, name, identity, latitude, longtitude, phone, amount) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?)";
                                
                                # sql injection protection
                                $mysqli = new mysqli($host, $dbuser, $dbpasswd, $db_name);
                                $stmt = $mysqli->prepare($insert_msg);
                                $stmt->bind_param("issssddsi", $UID, $account, $password, $name, $identity, $latitude, $longtitude, $phone, $amount);
                                $UID = $row_count;
                                $account = $account_name;
                                $password = $encrypted_passwd;
                                $name = $user_name;
                                $identity = 'customer';
                                $latitude = $insert_latitude;
                                $longtitude = $insert_longtitude;
                                $phone = $phone;
                                $amount = 0;
                                $stmt->execute();


                                mysqli_close($link);
                                echo "<script>alert('Sign up success');</script>";
                                echo "<script>window.location.href = 'https://localhost/hw2/index.html'</script>";

                            }


                        }

                    }
                    

                }



            }



        ?>
    </body>
</htnl>