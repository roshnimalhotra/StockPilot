<?php
session_start();
include 'db.php';

if(!isset($_SESSION['user_id'])){
    header("Location: login.html");
    exit();
}

$user_id = $_SESSION['user_id'];

$user = mysqli_fetch_assoc(mysqli_query($conn,"
SELECT * FROM users WHERE user_id=$user_id
"));

if(isset($_POST['name'])){
    $name = $_POST['name'];
    $email = $_POST['email'];

    mysqli_query($conn,"
    UPDATE users 
    SET name='$name', email='$email' 
    WHERE user_id=$user_id
    ");

    header("Location: profile.php?success=Profile Updated");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Edit Profile</title>
<link rel="stylesheet" href="style.css">
</head>

<body>

<div class="container">

<div class="card gradient">
<h2>✏ Edit Profile</h2>

<form method="POST">

<input type="text" name="name" value="<?php echo $user['name']; ?>" required class="qty" style="width:100%; margin:10px 0;">

<input type="email" name="email" value="<?php echo $user['email']; ?>" required class="qty" style="width:100%; margin:10px 0;">

<button class="btn-gradient">Update</button>

</form>

<br>
<button class="btn" onclick="location.href='profile.php'">⬅ Back</button>

</div>

</div>

</body>
</html>