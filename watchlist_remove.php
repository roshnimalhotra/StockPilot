<?php
session_start();
include 'db.php';

$user_id = $_SESSION['user_id'];
$stock_id = $_POST['stock_id'];

mysqli_query($conn,"DELETE FROM watchlist WHERE user_id=$user_id AND stock_id=$stock_id");

header("Location: watchlist.php");
?>