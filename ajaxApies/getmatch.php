<?php
    include('./connect.php');
    $conn = getConnect();

    $league_name = $_POST['league_name'];
    /////////////////////////////////////
    $sql = "select * from matches where lieg_name = '".$league_name."' and match_time > NOW();";
    $result = multi_query($conn, $sql)->rows;
    /////////////////////////////////////

    mysqli_close($conn);
    // echo $sql;
    echo json_encode($result);
?>