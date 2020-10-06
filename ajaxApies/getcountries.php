<?php
    include('./connect.php');
    $conn = getConnect();
    /////////////////////////////////////
    $sql = "select * from country;";
    $result = multi_query($conn, $sql)->rows;
    /////////////////////////////////////

    mysqli_close($conn);
    echo json_encode($result);
?>