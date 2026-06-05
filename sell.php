<?php
session_start();
include 'db.php';

if(!isset($_SESSION['user_id'])){
    header("Location: login.html");
    exit();
}

$user_id = $_SESSION['user_id'];
$stock_id = intval($_POST['stock_id']);
$qty = intval($_POST['quantity']);

/* VALIDATION */
if($qty <= 0){
    echo "<script>
    alert('Invalid quantity ⚠️');
    window.location.href='portfolio.php';
    </script>";
    exit();
}

/* CHECK PORTFOLIO */
$q = mysqli_query($conn,"
SELECT * FROM portfolio 
WHERE user_id=$user_id AND stock_id=$stock_id
");

$row = mysqli_fetch_assoc($q);

/* NOT OWNED / INSUFFICIENT */
if(!$row || $row['quantity'] < $qty){
    echo "<script>
    alert('Not enough stock to sell 📉');
    window.location.href='portfolio.php';
    </script>";
    exit();
}

/* GET CURRENT PRICE */
$p = mysqli_query($conn,"
SELECT current_price FROM stocks 
WHERE stock_id=$stock_id
");

$priceData = mysqli_fetch_assoc($p);
$price = (float)$priceData['current_price'];

/* =========================
BROKERAGE CALCULATION
========================= */
$trade_value = $price * $qty;
$brokerage = $trade_value * 0.005;   // 0.5%
$net_credit = $trade_value - $brokerage;

/* =========================
ADD BALANCE (AFTER BROKERAGE)
========================= */
mysqli_query($conn,"
UPDATE users 
SET balance = balance + $net_credit 
WHERE user_id=$user_id
");

/* =========================
UPDATE PORTFOLIO
========================= */
$newQty = $row['quantity'] - $qty;

if($newQty == 0){
    mysqli_query($conn,"
    DELETE FROM portfolio 
    WHERE user_id=$user_id AND stock_id=$stock_id
    ");
}else{
    mysqli_query($conn,"
    UPDATE portfolio 
    SET quantity=$newQty 
    WHERE user_id=$user_id AND stock_id=$stock_id
    ");
}

/* =========================
RECORD TRANSACTION (UPDATED)
========================= */
mysqli_query($conn,"
INSERT INTO transactions(
    user_id, stock_id, type, quantity, price, brokerage, status, transaction_date
)
VALUES(
    $user_id,$stock_id,'sell',$qty,$price,$brokerage,'completed', NOW()
)
");

/* =========================
REDIRECT
========================= */
header("Location: transactions.php");
exit();
?>