<?php
session_start();
include 'db.php';

if(!isset($_SESSION['user_id'])){
    header("Location: login.html");
    exit();
}

$user_id = $_SESSION['user_id'];

$query = mysqli_query($conn,"
SELECT p.*, s.company_name, s.current_price 
FROM portfolio p 
JOIN stocks s ON p.stock_id = s.stock_id
WHERE p.user_id=$user_id
");

$total = 0;
$totalInvestment = 0;
$totalBrokerage = 0;

/* GET TOTAL BROKERAGE FROM TRANSACTIONS */
$brokerQuery = mysqli_query($conn,"
SELECT SUM(brokerage) as total_brokerage 
FROM transactions 
WHERE user_id=$user_id
");

$brokerData = mysqli_fetch_assoc($brokerQuery);
$totalBrokerage = $brokerData['total_brokerage'] ?? 0;
$stocksData = [];

while($row = mysqli_fetch_assoc($query)){

$current = $row['current_price'];
$avg = $row['average_price'];
$qty = $row['quantity'];

/* 💰 VALUE */
$value = $qty * $current;


/* GET TOTAL BUY BROKERAGE FROM TRANSACTIONS */
$brokerQuery = mysqli_query($conn,"
SELECT SUM(brokerage) as total_brokerage 
FROM transactions 
WHERE user_id=$user_id 
AND stock_id=".$row['stock_id']." 
AND type='BUY'
");

$brokerData = mysqli_fetch_assoc($brokerQuery);
$brokerage = $brokerData['total_brokerage'] ?? 0;

/* INVESTMENT */
$investment = $qty * $avg;

/* 📊 PROFIT (adjusted with brokerage) */
$profit = $value - $investment;

/* TOTALS */
$total += $value;
$totalInvestment += $investment;
$totalBrokerage += $brokerage;

/* STORE */
$row['value'] = $value;
$row['profit'] = $profit;
$row['brokerage'] = $brokerage;
$row['investment'] = $investment;

$stocksData[] = $row;
}

/* 📉 TODAY P/L (UNCHANGED) */
$todayPL = 0;

foreach($stocksData as $stock){

$id = $stock['stock_id'];
$qty = $stock['quantity'];

$q = mysqli_query($conn,"
SELECT close_price 
FROM stock_price_history 
WHERE stock_id=$id 
ORDER BY price_date DESC 
LIMIT 2
");

$prices = [];
while($r = mysqli_fetch_assoc($q)){
$prices[] = $r['close_price'];
}

if(count($prices) == 2){
$todayPL += ($prices[0] - $prices[1]) * $qty;
}
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Portfolio</title>

<link rel="stylesheet" href="style.css">
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

<style>
.container{ padding:30px 8%; }

.grid{
display:grid;
grid-template-columns:1.2fr 1fr;
gap:20px;
}

.card{
background:rgba(255,255,255,0.05);
backdrop-filter:blur(10px);
padding:20px;
border-radius:16px;
}

/* TABLE */
.premium-table{
width:100%;
border-collapse:collapse;
}

.premium-table th,
.premium-table td{
padding:12px;
border-bottom:1px solid rgba(255,255,255,0.08);
}

.green{color:#00ffa3;font-weight:600;}
.red{color:#ff4d4d;font-weight:600;}

/* SELL BUTTON */
.sell-btn{
background:linear-gradient(45deg,#ff4d4d,#ff0000);
border:none;
padding:6px 12px;
border-radius:8px;
color:white;
cursor:pointer;
transition:0.3s;
}

.sell-btn:hover{
transform:scale(1.1);
box-shadow:0 0 10px rgba(255,77,77,0.7);
}

/* INPUT */
.qty{
width:60px;
padding:6px;
border-radius:6px;
border:none;
background:#020617;
color:white;
}

/* SL / RES */
.sl{ color:#ff4d4d; }
.res{ color:#00ffa3; }
</style>

</head>

<body class="dark">

<?php $current = basename($_SERVER['PHP_SELF']); ?>

<!-- NAVBAR -->
<nav>
<h2>💹 StockApp</h2>

<div class="nav-links">
<a href="dashboard.php" class="<?= ($current=='dashboard.php')?'active':'' ?>">Overview</a>
<a href="portfolio.php" class="<?= ($current=='portfolio.php')?'active':'' ?>">Portfolio</a>
<a href="stocks.php" class="<?= ($current=='stocks.php')?'active':'' ?>">Market</a>
<a href="transactions.php" class="<?= ($current=='transactions.php')?'active':'' ?>">Transactions</a>
<a href="profile.php">Profile</a>
<a href="watchlist.php" class="<?= ($current=='watchlist.php')?'active':'' ?>">Watchlist</a>
<a href="news.php">News</a>
<a href="community.php">Community</a>
</div>

<button class="btn" onclick="toggleTheme()">🌙</button>
</nav>

<div class="container">

<!-- 🔥 SUMMARY CARDS -->
<div class="grid">

<div class="card">
<h4>Total Portfolio Value</h4>
<h2>₹<?php echo number_format($total); ?></h2>
</div>

<div class="card">
<h4>Total Investment</h4>
<h2>₹<?php echo number_format($totalInvestment); ?></h2>
</div>



<div class="card">
<h4>Today P/L</h4>
<h2 class="<?php echo $todayPL>=0?'green':'red'; ?>">
₹<?php echo round($todayPL); ?>
</h2>
</div>
<div class="card">
<h4>Total Brokerage Paid</h4>
<h2 style="color:#8b5cf6;">
₹<?php echo number_format($totalBrokerage); ?>
</h2>
</div>
</div>

<br>

<div class="grid">

<!-- TABLE -->
<div class="card">
<h3>Holdings</h3>

<table class="premium-table">

<tr>
<th>Stock</th>
<th>Qty</th>
<th>Value</th>
<th>Investment</th>
<th>Brokerage</th>
<th>P/L</th>
<th>Alloc %</th>
<th>SL</th>
<th>Resistance</th>
<th>Mode</th>
<th>Action</th>
</tr>

<?php foreach($stocksData as $row){ 
$allocation = $total > 0 ? ($row['value']/$total)*100 : 0;

$sl = $row['stop_loss'] ?? 0;
$res = $row['resistance'] ?? 0;
$mode = $row['risk_mode'] ?? 'manual';
?>

<tr>

<td><?php echo $row['company_name']; ?></td>

<td><?php echo $row['quantity']; ?></td>

<td>₹<?php echo round($row['value']); ?></td>

<td>₹<?php echo round($row['investment']); ?></td>

<td>₹<?php echo round($row['brokerage']); ?></td>

<td class="<?php echo $row['profit']>=0?'green':'red'; ?>">
₹<?php echo round($row['profit']); ?>
</td>

<td><?php echo round($allocation,2); ?>%</td>

<td class="sl">
₹<?php echo $sl > 0 ? number_format($sl,2) : '--'; ?>
</td>

<td class="res">
₹<?php echo $res > 0 ? number_format($res,2) : '--'; ?>
</td>

<td><?php echo strtoupper($mode); ?></td>

<td>
<form action="sell.php" method="POST" style="display:flex; gap:6px;">
<input type="hidden" name="stock_id" value="<?php echo $row['stock_id']; ?>">
<input type="number" name="quantity" placeholder="Qty" required class="qty">
<button class="sell-btn">Sell</button>
</form>
</td>

</tr>

<?php } ?>

</table>
</div>

<!-- DONUT -->
<div class="card">
<h3>Allocation</h3>
<div id="donutChart"></div>
</div>

</div>

</div>

<script>
function toggleTheme(){
document.body.classList.toggle("light");
document.body.classList.toggle("dark");
}

/* DONUT */
new ApexCharts(document.querySelector("#donutChart"), {
series: <?php echo json_encode(array_column($stocksData,'value')); ?>,
labels: <?php echo json_encode(array_column($stocksData,'company_name')); ?>,
chart:{ type:'donut', height:300 },
tooltip:{ theme:'dark' }
}).render();
</script>

</body>
</html>