<?php
session_start();
include 'db.php';

if(!isset($_SESSION['user_id'])){
    header("Location: login.html");
    exit();
}

$user_id = $_SESSION['user_id'];
$stock_id = $_POST['stock_id'];

/* prevent duplicate */
$check = mysqli_query($conn,"
SELECT * FROM watchlist 
WHERE user_id=$user_id AND stock_id=$stock_id
");

if(mysqli_num_rows($check)==0){

mysqli_query($conn,"
INSERT INTO watchlist(user_id, stock_id) 
VALUES($user_id, $stock_id)
");

}

header("Location: stocks.php");
?>