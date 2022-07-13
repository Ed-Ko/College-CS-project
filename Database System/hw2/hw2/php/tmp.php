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
            echo '<td>'.$row['shop_name'].'</td>';
            echo '<td>'.$row['shop_category'].'</td>';
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

                <form action="./php/list_commodity.php" method="post"> #######################################################################################
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
                    echo '<td>'.'<img width="100" height="100" src="data:'.$menu_row['imgType'].';base64,' . $menu_row['img'] . ' " />';
                    echo '<td>'.$menu_row['meal_name'].'</td>';
                    echo '<td>'.$menu_row['price'].'</td>';
                    echo '<td>'.$menu_row['quantity'].' </td>';
                    echo '<td><input type="checkbox" id=" '.$menu_list_id.' " value=" '.$menu_row['meal_name'].' "></td> ';
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