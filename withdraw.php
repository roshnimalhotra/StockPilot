<?php
session_start();
include 'db.php';
if(!isset($_SESSION['user_id'])){
    header("Location: login.html");
    exit();}
$user_id = $_SESSION['user_id'];
$user = mysqli_fetch_assoc(mysqli_query($conn,"SELECT balance FROM users WHERE user_id=$user_id"));
if(isset($_POST['amount'])){
    $amount = floatval($_POST['amount']);
    if($amount > 0 && $amount <= $user['balance']){
        mysqli_query($conn,"UPDATE users SET balance = balance - $amount WHERE user_id = $user_id");
        header("Location: profile.php?success=Money Withdrawn");
        exit();
    }}?>
<!DOCTYPE html>
<html>
<head>
<title>Withdraw Money</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
<div class="card gradient">
<h2>➖ Withdraw Money</h2>
<p>Available Balance: ₹<?php echo $user['balance']; ?></p>
<form method="POST">
<input type="number" name="amount" placeholder="Enter Amount" required class="qty" style="width:100%; margin:15px 0;">
<button class="btn-gradient btn-danger">Withdraw</button>
</form>
<br>
<button class="btn" onclick="location.href='profile.php'">⬅ Back</button>
</div>
</div>
</body>
</html>