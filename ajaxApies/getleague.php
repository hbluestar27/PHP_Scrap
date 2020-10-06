<?php
    include('./connect.php');
    $conn = getConnect();

    $country_id = $_POST['country_id'];
    /////////////////////////////////////
    $sql = "select * from league where country_id = ".$country_id.";";
    $result = multi_query($conn, $sql)->rows;
    /////////////////////////////////////

    mysqli_close($conn);
    // echo $sql;
    echo json_encode($result);
?>