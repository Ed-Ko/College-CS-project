<?php
    session_start();

    echo "Account : " . $_SESSION["cur_user"] . "<br>";  # name
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