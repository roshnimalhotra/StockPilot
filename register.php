<?php
include 'db.php';

$name = $_POST['name'];
$email = $_POST['email'];
$password = $_POST['password'];

// check if email already exists
$check = mysqli_query($conn, "SELECT * FROM users WHERE email='$email'");

if(mysqli_num_rows($check) > 0){
    echo "Email already exists ❌";
} else {

    mysqli_query($conn, "INSERT INTO users(name,email,password) VALUES('$name','$email','$password')");

    echo "Registered Successfully 🎉";
}
?>