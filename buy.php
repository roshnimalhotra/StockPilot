<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include 'db.php';

if(!isset($_SESSION['user_id'])){
    header("Location: login.html");
    exit();
}

$user_id = $_SESSION['user_id'];

/* =========================
INPUTS
========================= */
$stock_id   = isset($_POST['stock_id']) ? (int)$_POST['stock_id'] : 0;
$qty        = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 0;
$stop_loss  = isset($_POST['stop_loss']) ? (float)$_POST['stop_loss'] : 0;
$resistance = isset($_POST['resistance']) ? (float)$_POST['resistance'] : 0;
$risk_mode  = $_POST['risk_mode'] ?? 'manual';

/* =========================
VALIDATION
========================= */
if($stock_id <= 0 || $qty <= 0){
    die("Invalid input ⚠️");
}

/* =========================
GET STOCK PRICE
========================= */
$stockQuery = mysqli_query($conn,"
SELECT current_price FROM stocks WHERE stock_id=$stock_id
");

if(!$stockQuery || mysqli_num_rows($stockQuery) == 0){
    die("Stock not found");
}

$stock = mysqli_fetch_assoc($stockQuery);
$price = (float)$stock['current_price'];

/* =========================
BROKERAGE CALCULATION
========================= */
$trade_value = $price * $qty;
$brokerage = $trade_value * 0.005;   // 0.5%
$total = $trade_value + $brokerage;

/* =========================
CHECK USER BALANCE
========================= */
$userQuery = mysqli_query($conn,"
SELECT balance FROM users WHERE user_id=$user_id
");

$user = mysqli_fetch_assoc($userQuery);

if($user['balance'] < $total){
    die("Insufficient balance 💸 (Including Brokerage)");
}

/* =========================
AUTO RISK MODE
========================= */
if($risk_mode === 'auto'){
    $stop_loss  = $price * 0.95;
    $resistance = $price * 1.10;
}

/* FINAL SAFETY */
if($stop_loss <= 0)  $stop_loss = 0;
if($resistance <= 0) $resistance = 0;

/* =========================
CHECK EXISTING PORTFOLIO
========================= */
$existing = mysqli_query($conn,"
SELECT * FROM portfolio 
WHERE user_id=$user_id AND stock_id=$stock_id
");

if(mysqli_num_rows($existing) > 0){

    $row = mysqli_fetch_assoc($existing);

    $oldQty = (int)$row['quantity'];
    $oldAvg = (float)$row['average_price'];

    $newQty = $oldQty + $qty;

    /* =========================
    CORRECT INVESTMENT LOGIC
    ========================= */

    // OLD investment already includes brokerage
    $oldInvestment = $oldQty * $oldAvg;

    // NEW investment (with brokerage)
    $newInvestment = $trade_value + $brokerage;

    // TOTAL investment
    $totalInvestment = $oldInvestment + $newInvestment;

    // NEW average price
    $newAvg = $totalInvestment / $newQty;

    /* KEEP OLD VALUES IF EMPTY */
    if($stop_loss == 0)  $stop_loss = (float)$row['stop_loss'];
    if($resistance == 0) $resistance = (float)$row['resistance'];
    if(!$risk_mode)      $risk_mode = $row['risk_mode'];

    mysqli_query($conn,"
    UPDATE portfolio 
    SET 
        quantity = $newQty,
        average_price = $newAvg,
        stop_loss = $stop_loss,
        resistance = $resistance,
        risk_mode = '$risk_mode'
    WHERE user_id=$user_id AND stock_id=$stock_id
    ");

}else{

    /* =========================
    NEW STOCK ENTRY
    ========================= */

    // avg price must include brokerage per share
    $avgPrice = ($trade_value + $brokerage) / $qty;

    mysqli_query($conn,"
    INSERT INTO portfolio(
        user_id, stock_id, quantity, average_price, stop_loss, resistance, risk_mode
    )
    VALUES(
        $user_id, $stock_id, $qty, $avgPrice, $stop_loss, $resistance, '$risk_mode'
    )
    ");
}

/* =========================
DEDUCT BALANCE
========================= */
mysqli_query($conn,"
UPDATE users 
SET balance = balance - $total 
WHERE user_id=$user_id
");

/* =========================
TRANSACTION LOG
========================= */
mysqli_query($conn,"
INSERT INTO transactions(
    user_id, stock_id, type, quantity, price, brokerage, status, transaction_date
)
VALUES(
    $user_id, $stock_id, 'BUY', $qty, $price, $brokerage, 'completed', NOW()
)
");


header("Location: transactions.php?success=1");
exit();
?>