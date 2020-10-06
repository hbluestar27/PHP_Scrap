<?php
    include('./connect.php');
    $conn = getConnect();
    $results = array();

    $match_id = $_POST['match_id'];
    /////////////////////////////////////
    $sql = "select * from matches where id = '".$match_id."';";
    $result = multi_query($conn, $sql)->rows;

    $results[] = $result;

    $sql = "select * from tone_ten_matches where match_id = '".$match_id."';";
    $result = multi_query($conn, $sql)->rows;
    $results[] = $result;

    $sql = "select * from ttwo_ten_matches where match_id = '".$match_id."';";
    $result = multi_query($conn, $sql)->rows;
    $results[] = $result;

    $sql = "select * from home_matches where match_id = '".$match_id."';";
    $result = multi_query($conn, $sql)->rows;
    $results[] = $result;

    $sql = "select * from away_matches where match_id = '".$match_id."';";
    $result = multi_query($conn, $sql)->rows;
    $results[] = $result;

    $sql = "select * from one_two where match_id = '".$match_id."';";
    $result = multi_query($conn, $sql)->rows;
    $results[] = $result;

    $sql = "select * from ou where match_id = '".$match_id."';";
    $result = multi_query($conn, $sql)->rows;
    $results[] = $result;

    /////////////////////////////////////

    mysqli_close($conn);
    // echo $sql;
    echo json_encode($results);
?>