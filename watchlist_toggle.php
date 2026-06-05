<?php
session_start();
include 'db.php';

if(!isset($_SESSION['user_id'])){
    echo json_encode(["status"=>"error"]);
    exit();
}

$user_id = $_SESSION['user_id'];
$stock_id = intval($_POST['stock_id']);


// 🔍 CHECK IF EXISTS
$check = mysqli_query($conn,"
SELECT watchlist_id FROM watchlist 
WHERE user_id=$user_id AND stock_id=$stock_id
");

if(mysqli_num_rows($check) > 0){

    // ❌ REMOVE FROM WATCHLIST
    mysqli_query($conn,"
    DELETE FROM watchlist 
    WHERE user_id=$user_id AND stock_id=$stock_id
    ");

    echo json_encode(["status"=>"removed"]);

}else{

    // ✅ ADD TO WATCHLIST WITH DATE
    mysqli_query($conn,"
    INSERT INTO watchlist(user_id, stock_id, added_date)
    VALUES($user_id, $stock_id, NOW())
    ");

    echo json_encode(["status"=>"added"]);
}
?>