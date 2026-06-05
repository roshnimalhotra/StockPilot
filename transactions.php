<?php
session_start();
include 'db.php';

if(!isset($_SESSION['user_id'])){
    header("Location: login.html");
    exit();
}

$user_id = $_SESSION['user_id'];

/* FILTERS */
$filter = $_GET['filter'] ?? 'all';
$plFilter = $_GET['pl'] ?? 'all';
$search = $_GET['search'] ?? '';

/* DATE FILTER */
$dateCondition = "";
if($filter == 'weekly'){
    $dateCondition = "AND t.transaction_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
}elseif($filter == 'monthly'){
    $dateCondition = "AND MONTH(t.transaction_date) = MONTH(CURDATE())
                      AND YEAR(t.transaction_date) = YEAR(CURDATE())";
}

/* SEARCH FILTER */
$searchCondition = "";
if(!empty($search)){
    $search = mysqli_real_escape_string($conn, $search);
    $searchCondition = "AND s.company_name LIKE '%$search%'";
}

/* QUERY */
$query = "
SELECT t.*, s.company_name, s.current_price
FROM transactions t
JOIN stocks s ON t.stock_id = s.stock_id
WHERE t.user_id = $user_id
$dateCondition
$searchCondition
ORDER BY t.transaction_id DESC
";

$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html>
<head>
<title>Transactions</title>
<link rel="stylesheet" href="style.css">

<style>

/* FILTER BAR */
.filter-bar{
display:flex;
gap:10px;
margin-bottom:15px;
flex-wrap:wrap;
}

.filter-bar select,
.filter-bar input{
padding:8px;
border-radius:8px;
border:none;
background:#020617;
color:white;
}

/* BADGES */
.badge{
padding:6px 12px;
border-radius:20px;
font-size:12px;
font-weight:bold;
}

.buy{ background:#00ffa3; color:black; }
.sell{ background:#ff4d4d; color:white; }

/* STATUS */
.status{
padding:5px 10px;
border-radius:20px;
font-size:12px;
}

.done{ background:#00ffa3; color:black; }
.pending{ background:orange; color:black; }

/* BROKERAGE */
.brokerage-text{
color:#8b5cf6;
font-weight:600;
}

/* PROFIT */
.green{ color:#00ffa3; font-weight:600; }
.red{ color:#ff4d4d; font-weight:600; }

/* TABLE */
table{
width:100%;
border-collapse:collapse;
}

th, td{
padding:12px;
border-bottom:1px solid rgba(255,255,255,0.08);
}

tr{
transition:0.3s;
}

tr:hover{
transform:scale(1.01);
background:rgba(124,58,237,0.08);
}

</style>

</head>

<body>

<?php $current = basename($_SERVER['PHP_SELF']); ?>

<nav>
<h2>💹 StockApp</h2>

<div class="nav-links">
<a href="dashboard.php">Overview</a>
<a href="portfolio.php">Portfolio</a>
<a href="stocks.php">Market</a>
<a href="transactions.php" class="active">Transactions</a>
<a href="profile.php">Profile</a>
</div>

<button class="btn" onclick="toggleDark()">🌙</button>
</nav>

<div class="container">

<h3 style="margin-bottom:10px;">📊 Trading History</h3>

<!-- FILTER BAR -->
<form method="GET" class="filter-bar">

<select name="filter" onchange="this.form.submit()">
<option value="all">All Time</option>
<option value="weekly" <?= $filter=='weekly'?'selected':'' ?>>Weekly</option>
<option value="monthly" <?= $filter=='monthly'?'selected':'' ?>>Monthly</option>
</select>

<select name="pl" onchange="this.form.submit()">
<option value="all">All</option>
<option value="profit" <?= $plFilter=='profit'?'selected':'' ?>>Profit</option>
<option value="loss" <?= $plFilter=='loss'?'selected':'' ?>>Loss</option>
</select>

<input type="text" name="search" placeholder="Search stock..."
value="<?= htmlspecialchars($search) ?>">

<button class="btn">Apply</button>

</form>

<table>

<tr>
<th>Stock</th>
<th>Type</th>
<th>Qty</th>
<th>Price</th>
<th>Brokerage</th>
<th>Net P/L</th>
<th>Status</th>
<th>Date</th>
</tr>

<?php

if(mysqli_num_rows($result) > 0){

while($row = mysqli_fetch_assoc($result)){

$type = strtoupper($row['type']);
$typeClass = $type == 'BUY' ? 'buy' : 'sell';

$brokerage = $row['brokerage'] ?? 0;
$tradeValue = $row['price'] * $row['quantity'];

/* NET PROFIT CALC */
$netPL = 0;

if($type == 'SELL'){
    $netPL = $tradeValue - $brokerage;
}else{
    $netPL = -($tradeValue + $brokerage);
}

/* FILTER PROFIT/LOSS */
if($plFilter == 'profit' && $netPL <= 0) continue;
if($plFilter == 'loss' && $netPL >= 0) continue;

$plClass = $netPL >= 0 ? 'green' : 'red';
?>

<tr>

<td><b><?php echo $row['company_name']; ?></b></td>

<td>
<span class="badge <?php echo $typeClass; ?>">
<?php echo $type == 'BUY' ? '🟢 BUY' : '🔴 SELL'; ?>
</span>
</td>

<td><?php echo $row['quantity']; ?></td>

<td>₹<?php echo number_format($row['price']); ?></td>

<td class="brokerage-text">
₹<?php echo number_format($brokerage,2); ?>
</td>

<td class="<?php echo $plClass; ?>">
₹<?php echo number_format($netPL,2); ?>
</td>

<td>
<span class="status <?php echo strtolower($row['status'])=='completed'?'done':'pending'; ?>">
<?php echo ucfirst($row['status']); ?>
</span>
</td>

<td>
<?php echo date("d M Y, h:i A", strtotime($row['transaction_date'])); ?>
</td>

</tr>

<?php
}

}else{
?>

<tr>
<td colspan="8" style="text-align:center; padding:30px;">
😴 No transactions found
</td>
</tr>

<?php } ?>

</table>

</div>

<script>
function toggleDark(){
document.body.classList.toggle("light");
}
</script>

</body>
</html>