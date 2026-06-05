<?php
session_start();
include 'db.php';

$email = $_POST['email'];
$password = $_POST['password'];

$result = mysqli_query($conn, "SELECT * FROM users WHERE email='$email' AND password='$password'");

if(mysqli_num_rows($result) > 0){
    
    $user = mysqli_fetch_assoc($result);
    
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['name'] = $user['name'];

    header("Location: dashboard.php"); 

} else {
    echo "Invalid Email or Password ❌";
}
?>