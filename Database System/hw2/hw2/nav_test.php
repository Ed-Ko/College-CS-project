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
</head>

<body>
 
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
    </ul>

    <div class="tab-content">

      <div id="home" class="tab-pane fade in active">
        <h3>Profile</h3>
        <div class="row">
          <div class="col-xs-12">
            <?php
              session_start();

              echo "Account : " . $_SESSION["account"] . "<br>";  # name
              echo "Role : " . $_SESSION["identity"] . "<br>";  # role(user, manager)
              echo "Phone : " . $_SESSION["phone"] . "<br>";  # phone
              echo "Latitude : " .  $_SESSION["latitude"] . "<br>";  # location
              echo "Longtitude : " .  $_SESSION["longtitude"] . "<br>";  # location
              
              echo <<< EOT
              <br>
                <button type="button " style="margin-left: 5px;" class=" btn btn-info " data-toggle="modal"
                data-target="#location">edit location</button>
                <!--  -->
                <div class="modal fade" id="location"  data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdropLabel" aria-hidden="true">
                  <div class="modal-dialog  modal-sm">
                    <div class="modal-content">
                      <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                        <h4 class="modal-title">edit location</h4>
                      </div>
                      <div class="modal-body">
                        <label class="control-label " for="latitude">latitude</label>
                        <input type="text" class="form-control" id="latitude" placeholder="enter latitude">
                          <br>
                          <label class="control-label " for="longitude">longitude</label>
                        <input type="text" class="form-control" id="longitude" placeholder="enter longitude">
                      </div>
                      <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Edit</button>
                      </div>
                    </div>
                  </div>
                </div>
              <br>
              <br>
              EOT;

              echo "Wallet balance : " . $_SESSION["amount"] . "<br>";  # amount

              echo <<< EOT
                <!--  -->
                <!-- Modal -->
                <br>
                <button type="button " style="margin-left: 5px;" class=" btn btn-info " data-toggle="modal"
                  data-target="#myModal">Add value</button>
                <div class="modal fade" id="myModal"  data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdropLabel" aria-hidden="true">
                  <div class="modal-dialog  modal-sm">
                    <div class="modal-content">
                      <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                        <h4 class="modal-title">Add value</h4>
                      </div>
                      <div class="modal-body">
                        <input type="text" class="form-control" id="value" placeholder="enter add value">
                      </div>
                      <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Add</button>
                      </div>
                    </div>
                  </div>
                </div>

              EOT;

            ?>
            
          </div>

        </div>

        <!-- 
                
             -->
        <h3>Search</h3>
        <div class=" row  col-xs-8">
          <form class="form-horizontal" action="./php/search_shop.php" method="post">
            <div class="form-group">
              <label class="control-label col-sm-1" for="Shop">Shop</label>
              <div class="col-sm-5">
                <input type="text" class="form-control" placeholder="Enter Shop name" name="shop_name">
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

                <input type="text" class="form-control" name="lower_price">

              </div>
              <label class="control-label col-sm-1" for="~">~</label>
              <div class="col-sm-2">

                <input type="text" class="form-control" name="higher_price">

              </div>
              <label class="control-label col-sm-1" for="Meal">Meal</label>
              <div class="col-sm-5">
                <input type="text" list="Meals" class="form-control" id="Meal" placeholder="Enter Meal" name="meal_name">
                <datalist id="Meals">
                  <option value="Hamburger">
                  <option value="coffee">
                </datalist>
              </div>
            </div>

            <div class="form-group">
              <label class="control-label col-sm-1" for="category"> category</label>
            
              
                <div class="col-sm-5">
                  <input type="text" list="categorys" class="form-control" id="category" placeholder="Enter shop category" name="shop_category">
                  <datalist id="categorys">
                    <option value="fast food">
               
                  </datalist>
                </div>

                <button type="submit" style="margin-left: 18px;"class="btn btn-primary">Search</button>
              
            </div>
          </form>
        </div>
        <div class="row">
          <div class="  col-xs-8">
            <?php
              
              if(!empty($_SESSION["search_SID"])){
                echo '<table class="table" style=" margin-top: 15px;">';
                echo '<thead>';
                echo '<tr>
                      <th scope="col">#</th>
                  
                      <th scope="col">shop name</th>
                      <th scope="col">shop category</th>
                      <th scope="col">Distance</th>
                  
                      </tr>
                      </thead>
                      <tbody>';

                $host = "localhost";
                $dbuser = "root";
                $dbpasswd = "";
                $db_name = "hw2";

                $link = new mysqli($host, $dbuser, $dbpasswd, $db_name);
                $search_SID = array();
                $search_SID = $_SESSION["search_SID"];
                $shop_list_id = 0;
                $user_latitude = $_SESSION["latitude"];
                $user_longtitude = $_SESSION["longtitude"];


                
                for ($index = 0; $index < count($search_SID); $index++){
                  $tmp_shop_id = $search_SID[$index];
                  $shop_sql="SELECT * FROM shop WHERE SID='$search_SID[$index]' " ;
                  $shop_result=$link->query($shop_sql);
                  
                  if ($shop_result->num_rows > 0) {

                    while($row = $shop_result->fetch_assoc()){

                      $distance_number = pow( ($row['latitude'] - $user_latitude),2) + pow( ($row['longtitude'] - $user_longtitude),2);
                      
                      if($distance_number < 800)
                        $distance_case = "near";
                      else if($distance_number >= 800 && $distance_number < 20000)
                        $distance_case = "medium";
                      else
                        $distance_case = "far";
                      
                      $shop_list_id = $shop_list_id + 1;
                      echo '<tr> ';
                      echo '<th scope="row">'.$shop_list_id.'</th>';
                      echo '<td>'.$row['name'].'</td>';
                      echo '<td>'.$row['order_type'].'</td>';
                      echo '<td>'.$distance_case.' </td>';
                      echo '
                            <td>
                              <button type="button" class="btn btn-info" data-toggle="modal" data-target="#shop' . $shop_list_id .'" ">
                                Open menu
                              </button>
                            </td>
                            </tr>
                      ';

                    }
                  
                  }
                }

                echo '
                  </tbody>
                  </table>
                ';

                $shop_list_id = 0;
                for ($index = 0; $index < count($search_SID); $index++){

                  $tmp_shop_id = $search_SID[$index];
                  $shop_sql="SELECT * FROM shop WHERE SID='$search_SID[$index]' " ;
                  $shop_result=$link->query($shop_sql);

                  if ($shop_result->num_rows > 0) {

                    while($row = $shop_result->fetch_assoc()){
                      $shop_list_id = $shop_list_id + 1;
                      echo '
                        <!-- Modal -->
                        <div class="modal fade" id="shop' . $shop_list_id .'"  data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdropLabel" aria-hidden="true">
                          <div class="modal-dialog">
                      
                          <!-- Modal content-->
                          <div class="modal-content">
                            <div class="modal-header">
                              <button type="button" class="close" data-dismiss="modal">&times;</button>
                              <h4 class="modal-title">menu</h4>
                            </div>

                            <form action="./php/list_commodity.php" method="post">
                            <div class="modal-body">
                            <!--  -->
                      
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
                                  <tbody>
                      ';

                      $menu_sql="SELECT * FROM commodity WHERE SID='$tmp_shop_id'";
                      $menu_result = $link->query($menu_sql);
                      $menu_list_id = 0;
                      if ($menu_result->num_rows > 0) {
                          while($menu_row = $menu_result->fetch_assoc()){
                              $menu_list_id = $menu_list_id + 1;
                              echo '<tr>';
                              echo '<th scope="row">'.$menu_list_id.'</th>';
                              echo '<td>'.'<img width="100" height="100" src="data:'.$menu_row['picture_type'].';base64,' . $menu_row['picture'] . ' " />';
                              echo '<td>'.$menu_row['name'].'</td>';
                              echo '<td>'.$menu_row['price'].'</td>';
                              echo '<td>'.$menu_row['quantity'].' </td>';
                              echo '<td><input type="checkbox" id=" '.$menu_list_id.' " value=" '.$menu_row['name'].' "></td> ';
                              echo '</tr>';
                          }
                      }
                      
                      echo '
                                  </tbody>
                                </table>
                              </div>
                            </div>
                            </div>

                            <!--  -->
                            <div class="modal-footer">
                              <button type="button" class="btn btn-default">Order</button>
                            </div>
                            </form>
                          </div>
                          
                        </div>
                      </div>
                      ';

                    }
                  }

                }

              }
              else{
                echo '<table class="table" style=" margin-top: 15px;">';
                echo '<thead>';
                echo '<tr>
                      <th scope="col">#</th>
                  
                      <th scope="col">shop name</th>
                      <th scope="col">shop category</th>
                      <th scope="col">Distance</th>
                  
                      </tr>
                      </thead>
                      </table>
                '; 
              }
              
            ?>
            
          </div>
        </div>
      </div>


      <div id="menu1" class="tab-pane fade">

        <h3> Start a business </h3>

        <?php

          if($_SESSION["identity"] == "manager"){
            echo "Shop name : " . $_SESSION["shop_name"] . "<br>";
            echo "Shop category : " . $_SESSION["shop_category"] . "<br>";
            echo "latitude : " . $_SESSION["shop_latitude"] . "<br>";
            echo "longtitude : " . $_SESSION["shop_longtitude"] . "<br>";
          }
          else{
            echo <<< EOT
            <form action="./php/register.php" method = "post">
            <div class="form-group ">
                <div class="row">
                    <div class="col-xs-2">
                    <label for="ex5">shop name</label>
                    <input class="form-control" id="ex5" placeholder="macdonald" type="text" name = "shop_name">
                    </div>
                    <div class="col-xs-2">
                    <label for="ex5">shop category</label>
                    <input class="form-control" id="ex5" placeholder="fast food" type="text" name = "shop_category">
                    </div>
                    <div class="col-xs-2">
                    <label for="ex6">latitude</label>
                    <input class="form-control" id="ex6" placeholder="121.00028167648875" type="text" name = "latitude">
                    </div>
                    <div class="col-xs-2">
                    <label for="ex8">longitude</label>
                    <input class="form-control" id="ex8" placeholder="24.78472733371133" type="text" name = "longtitude">
                    </div>
                </div>
            </div>
        

            <div class=" row" style=" margin-top: 25px;">
              <div class=" col-xs-3">
                <button type="submit" class="btn btn-primary"  >register</button>
              </div>
            </div>
            <hr>
            </form>
            EOT;
          }
        
        ?>

        

        <h3>ADD</h3>

        <form action="./php/add_meal.php" method = "post" enctype="multipart/form-data">
          <div class="form-group ">
            <div class="row">
              <div class="col-xs-6">
                <label for="ex3">meal name</label>
                <input class="form-control" id="ex3" type="text" name = "meal_name">
              </div>
            </div>
            <div class="row" style=" margin-top: 15px;">
              <div class="col-xs-3">
                <label for="ex7">price</label>
                <input class="form-control" id="ex7" type="text" name = "price">
              </div>
              <div class="col-xs-3">
                <label for="ex4">quantity</label>
                <input class="form-control" id="ex4" type="text" name = "quantity">
              </div>
            </div>

            <div class="row" style=" margin-top: 25px;">

              <div class=" col-xs-3">
                <label for="ex12">上傳圖片</label>
                <input id="myFile" type="file" name="myFile" multiple class="file-loading">

              </div>
              <div class=" col-xs-3">

                <button style=" margin-top: 15px;" type="submit" class="btn btn-primary">Add</button>
              </div>
            </div>
          </div>
        </form>

        
        <div class="row">
          <div class="  col-xs-8">
            <table class="table" style=" margin-top: 15px;">
              <thead>
                <tr>
                  <th scope="col">#</th>
                  <th scope="col">Picture</th>
                  <th scope="col">meal name</th>
              
                  <th scope="col">price</th>
                  <th scope="col">Quantity</th>
                  <th scope="col">Edit</th>
                  <th scope="col">Delete</th>
                </tr>
              </thead>
              <tbody>

                <?php

                  $host = "localhost";
                  $dbuser = "root";
                  $dbpasswd = "";
                  $db_name = "hw2";

                  $link = mysqli_connect($host, $dbuser, $dbpasswd, $db_name);


                  if($_SESSION["identity"] == "manager"){
                    
                    # get the meal list of current shop
                    $cur_shop_id = $_SESSION["shop_id"];
                    
                    $query_commodity = "SELECT * FROM commodity WHERE SID='$cur_shop_id'";
                    
                    $query_meal_result = mysqli_query($link, $query_commodity);                    
                    
                    if(mysqli_num_rows($query_meal_result) > 0){
                      $i = 1;
                      
                      while($row_now = mysqli_fetch_assoc($query_meal_result)){
                        $commodity_id = $row_now["PID"];
                        $commodity_name = $row_now["name"];
                        $commodity_price = $row_now["price"];
                        $commodity_quantity = $row_now["quantity"];
                        $commodity_picture = $row_now["picture"];
                        $commodity_picture_type = $row_now["picture_type"];

                        
                        echo '
                        <tr>
                        

                        <th scope="row">' . $commodity_id . '</th>

                        <td><img src="data:' . $commodity_picture_type . ';base64,' . $commodity_picture . '" with="50" heigh="10" alt="' . $commodity_name . '"/></td>

                        <td>' . $commodity_name . '</td>
                        <td>' . $commodity_price . '</td>
                        <td>' . $commodity_quantity . '</td>

                        

                        <td><button type="button" class="btn btn-info" data-toggle="modal" data-target="#' . $commodity_id . '">Edit</button></td>
                        
                        
                        
                        <div class="modal fade" id="' . $commodity_id . '" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdropLabel" aria-hidden="true">
                          
                          <div class="modal-dialog" role="document">
                          
                            <div class="modal-content">
                              <form action="./php/edit_meal.php" method="post">
                              <div class="modal-header">
                                  <h5 class="modal-title" id="staticBackdropLabel">Edit</h5>
                                  <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                  <span aria-hidden="true">&times;</span>
                                  </button>
                              </div>
        
                              <input class="form-control" type="hidden" name="commodity_id" id="commodity_id" value="' . $row_now["PID"] . '">

                              <div class="modal-body">
                                <div class="form-group">
                                  <div class="col-xs-6">
                                      <label for="ex71">price</label>
                                      <input class="form-control" name="edit_price" type="text">
                                  </div>
                                

                                  <div class="col-xs-6">
                                      <label for="ex41">quantity</label>
                                      <input class="form-control" name="edit_quantity" type="text">
                                  </div>
                                </div>
                              </div>

                              <div class="modal-footer">
                                  <button type="submit" class="btn btn-secondary" id="submit_edit">Edit</button>
                              </div>
                              </form>
                              
                            </div>
                          </div>
                        </div>
                        
                        ';

                        echo <<< EOT

                        <form action="./php/delete_meal.php" method="post">
                          <input class="form-control" type="hidden" name="commodity_id" id="commodity_id" value="$commodity_id">
                          <td><button type="submit" class="btn btn-danger">Delete</button></td>
                        </form>
                        </tr>

                        EOT;
                      }
                    }
                    
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