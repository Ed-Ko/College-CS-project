<html>
    <body>
        <?php

            session_start();

            $host = "localhost";
            $dbuser = "root";
            $dbpasswd = "";
            $db_name = "hw2";

            if($_SERVER["REQUEST_METHOD"] == "POST"){
                $meal_name = $_POST["meal_name"];
                $price = $_POST["price"];
                $quantity = $_POST["quantity"];

                if($_FILES["myFile"]["error"] > 0){
                    echo "fil error";
                }
                else{


                    # start processing file
                    $file = fopen($_FILES["myFile"]["tmp_name"], "rb");
                    $file_content = fread($file, filesize($_FILES["myFile"]["tmp_name"]));
                    fclose($file);

                    $fileContent = base64_encode($file_content);

                    $img_type = $_FILES["myFile"]["type"];
                    
                    # move picture to picture directory if picture is not exist in Picture directory
                    if(!file_exists("../Picture/".$_FILES["myFile"]["name"]))
                        move_uploaded_file($_FILES["myFile"]["tmp_name"], "../Picture/".$_FILES["myFile"]["name"]);
                }
                
                
                $link = mysqli_connect($host, $dbuser, $dbpasswd, $db_name);
                if(!$link)
                    echo "*** ERROR *** : MySQL not connected </br>". mysqli_connect_error();

                # get row count of commodity ot assign commodity id
                $row_count = 0;
                $cur_shop_id = $_SESSION["shop_id"];
                $query_row_count = "SELECT * FROM commodity";
                $result = mysqli_query($link, $query_row_count);
                if($result)
                    $row_count = mysqli_num_rows($result);
                else
                    echo "*** ERROR *** : target table return error </br>". mysqli_connect_error();


                # add meal to database
                $insert_meal = "INSERT INTO commodity(PID, SID, name, price, picture, picture_type, quantity) VALUES('$row_count','$cur_shop_id','$meal_name','$price','$fileContent', '$img_type', '$quantity')";
                if(!mysqli_query($link, $insert_meal)){
                    mysqli_close($link);
                    echo "** ERROR *** : add meal failed";
                    echo "<script>window.location.href = 'https://localhost/hw2/nav_test.php#menu1'</script>";
                }
                else{
                    mysqli_close($link);
                    echo "<script>alert('Meal added');</script>";
                    echo "<script>window.location.href = 'https://localhost/hw2/nav_test.php#menu1'</script>";
                }


            }
        ?>
    </body>
</html>