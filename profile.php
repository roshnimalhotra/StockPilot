<?php
session_start();
include 'db.php';

if(!isset($_SESSION['user_id'])){
    header("Location: login.html");
    exit();
}

$user_id = $_SESSION['user_id'];

/* USER DATA */
$user = mysqli_fetch_assoc(mysqli_query($conn,"
SELECT * FROM users WHERE user_id=$user_id
"));

/* PORTFOLIO DATA */
$query = mysqli_query($conn,"
SELECT p.*, s.company_name, s.current_price
FROM portfolio p
JOIN stocks s ON p.stock_id = s.stock_id
WHERE p.user_id=$user_id
");

$totalInvestment = 0;
$currentValue = 0;
$totalQty = 0;
$totalStocks = 0;
$data = [];

while($row = mysqli_fetch_assoc($query)){
    $invest = $row['quantity'] * $row['average_price'];
    $value = $row['quantity'] * $row['current_price'];

    $totalInvestment += $invest;
    $currentValue += $value;
    $totalQty += $row['quantity'];
    $totalStocks++;

    $data[] = [
        "name"=>$row['company_name'],
        "value"=>$value
    ];
}

$profit = $currentValue - $totalInvestment;
$netWorth = $user['balance'] + $currentValue;

/* TRANSACTIONS */
$transactions = mysqli_query($conn,"
SELECT t.*, s.company_name, p.average_price
FROM transactions t
JOIN stocks s ON t.stock_id = s.stock_id
LEFT JOIN portfolio p 
ON t.stock_id = p.stock_id AND t.user_id = p.user_id
WHERE t.user_id = $user_id
ORDER BY t.transaction_date DESC
LIMIT 5
");
?>

<!DOCTYPE html>
<html>
<head>
<title>Profile</title>

<link rel="stylesheet" href="style.css">
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

</head>

<body>

<?php $current = basename($_SERVER['PHP_SELF']); ?>

<!-- NAVBAR -->
<nav>
<h2>💹 StockApp</h2>

<div class="nav-links">
<a href="dashboard.php">Overview</a>
<a href="portfolio.php">Portfolio</a>
<a href="stocks.php">Market</a>
<a href="transactions.php">Transactions</a>
<a href="watchlist.php">Watchlist</a>
<a href="profile.php" class="active">Profile</a>
</div>

<button class="btn" onclick="toggleTheme()">🌙</button>
</nav>

<div class="container">

<!-- 👤 PROFILE HEADER -->
<div class="card" style="display:flex;align-items:center;gap:20px;">

<div class="profile-avatar">
<?php echo strtoupper(substr($user['name'],0,1)); ?>
</div>

<div>
<h2><?php echo $user['name']; ?></h2>
<p style="color:#9ca3af;"><?php echo $user['email']; ?></p>

<span class="badge-buy">✔ Verified</span>
<span class="badge-sell">KYC Pending</span>
</div>

</div>

<br>

<!-- 💰 SUMMARY CARDS -->
<div class="grid">

<div class="card">
<h4>Wallet Balance</h4>
<h2 class="counter" data-value="<?php echo $user['balance']; ?>">₹0</h2>
</div>

<div class="card">
<h4>Total Investment</h4>
<h2>₹<?php echo number_format($totalInvestment); ?></h2>
</div>

<div class="card">
<h4>Market Value</h4>
<h2>₹<?php echo number_format($currentValue); ?></h2>
</div>

<div class="card <?php echo $profit>=0?'pulse-green':'pulse-red'; ?>">
<h4>Profit / Loss</h4>
<h2 class="<?php echo $profit>=0?'green':'red'; ?>">
₹<?php echo number_format($profit); ?>
</h2>
</div>

</div>

<!-- ⚡ ACTION BUTTONS (FIXED) -->
<div class="action-bar" style="margin:20px 0;">

<a href="add_money.php" class="btn btn-gradient glow-green">
➕ Add Money
</a>

<a href="withdraw.php" class="btn btn-gradient glow-red">
➖ Withdraw
</a>

<a href="edit_profile.php" class="btn btn-gradient">
✏ Edit Profile
</a>

<a href="logout.php" class="btn btn-gradient danger glow-orange">
🚪 Logout
</a>

</div>

<!-- 📊 CHART + ACCOUNT -->
<div class="grid">

<div class="card">
<h3>Investment Distribution</h3>

<?php if(empty($data)){ ?>
<p style="color:#9ca3af;">No stocks invested yet</p>
<?php } else { ?>
<div id="pieChart"></div>
<?php } ?>

</div>

<div class="card">
<h3>Account Insights</h3>

<p>Total Stocks: <b><?php echo $totalStocks; ?></b></p>
<p>Total Shares: <b><?php echo $totalQty; ?></b></p>
<p>Net Worth: <b>₹<?php echo number_format($netWorth); ?></b></p>

</div>

</div>

<br>

<!-- 📋 TRANSACTIONS -->
<div class="card">

<h3>Recent Transactions</h3>

<table class="premium-table">

<tr>
<th>Stock</th>
<th>Type</th>
<th>Qty</th>
<th>Price</th>
<th>Total</th>
<th>Brokerage</th>
<th>Date</th>
<th>P/L</th>
</tr>

<?php while($t = mysqli_fetch_assoc($transactions)){ ?>

<?php
$plText = "--";
$plClass = "";
$arrow = "";

// ✅ FIX: use $t (NOT $row)
if(strtolower($t['type']) == 'sell' && $t['average_price'] != null){

    $pl = ($t['price'] - $t['average_price']) * $t['quantity'];

    if($pl > 0){
        $plText = "₹".number_format($pl);
        $plClass = "profit";
        $arrow = "↑";
    }elseif($pl < 0){
        $plText = "₹".number_format(abs($pl));
        $plClass = "loss";
        $arrow = "↓";
    }else{
        $plText = "No Gain";
    }
}
?>

<tr class="table-row">

<td><?php echo $t['company_name']; ?></td>

<td>
<span class="<?php echo $t['type']=='BUY'?'badge-buy':'badge-sell pulse-red'; ?>">
<?php echo $t['type']=='BUY'?'🟢 BUY':'🔴 SELL'; ?>
</span>
</td>

<td><?php echo $t['quantity']; ?></td>

<td>₹<?php echo $t['price']; ?></td>

<td>₹<?php echo $t['price'] * $t['quantity']; ?></td>
<td style="color:#8b5cf6; font-weight:600;">
₹<?php echo number_format($t['brokerage'] ?? 0); ?>
</td>
<td><?php echo date("d M Y", strtotime($t['transaction_date'])); ?></td>

<!-- CORRECT P/L COLUMN -->
<td class="<?php echo $plClass; ?>">
    <?php echo $plText; ?>
    <span class="arrow"><?php echo $arrow; ?></span>
</td>

</tr>

<?php } ?>
</table>

</div>

</div>

<script>

/* 🌙 THEME */
function toggleTheme(){
document.body.classList.toggle("light");
}

/* 💰 COUNTER ANIMATION */
document.querySelectorAll(".counter").forEach(el=>{
let target = parseFloat(el.dataset.value);
let count = 0;

let interval = setInterval(()=>{
count += target/50;
if(count >= target){
count = target;
clearInterval(interval);
}
el.innerText = "₹" + Math.floor(count).toLocaleString();
},20);
});

/* 📊 PIE CHART */
<?php if(!empty($data)){ ?>
new ApexCharts(document.querySelector("#pieChart"),{

series: <?php echo json_encode(array_column($data,'value')); ?>,
labels: <?php echo json_encode(array_column($data,'name')); ?>,

chart:{
type:'donut',
height:300,
animations:{
enabled:true,
easing:'easeinout',
speed:800
}
},

colors:[
"#3b82f6","#22c55e","#facc15","#ef4444","#a855f7"
],

tooltip:{ theme:'dark' }

}).render();
<?php } ?>

</script>

</body>
</html>