<?php
session_start();
include 'db.php';

ini_set('display_errors', 1);
error_reporting(E_ALL);

if(!isset($_SESSION['user_id'])){
    header("Location: login.html");
    exit();
}

$user_id = $_SESSION['user_id'];
$stock_id = $_GET['stock_id'] ?? 1;

/* PORTFOLIO VALUE */
$res1 = mysqli_query($conn,"
SELECT SUM(quantity*average_price) AS total 
FROM portfolio 
WHERE user_id=$user_id
");
$total = mysqli_fetch_assoc($res1)['total'] ?? 0;

/* STOCK COUNT */
$res2 = mysqli_query($conn,"
SELECT COUNT(*) AS count 
FROM portfolio 
WHERE user_id=$user_id
");
$count = mysqli_fetch_assoc($res2)['count'] ?? 0;

/* TODAY P/L */
$todayPL = 0;
$stocks = mysqli_query($conn,"
SELECT p.*, s.current_price 
FROM portfolio p 
JOIN stocks s ON p.stock_id = s.stock_id 
WHERE p.user_id=$user_id
");

while($row = mysqli_fetch_assoc($stocks)){
    $id = $row['stock_id'];
    $qty = $row['quantity'];

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

    if(count($prices)==2){
        $todayPL += ($prices[0] - $prices[1]) * $qty;
    }
}

/* STOCK NAME */
$nameQuery = mysqli_query($conn,"SELECT company_name FROM stocks WHERE stock_id=$stock_id");
$stockName = mysqli_fetch_assoc($nameQuery)['company_name'] ?? "Stock";

/* CANDLE DATA */
$candleQuery = mysqli_query($conn,"
SELECT * FROM stock_price_history 
WHERE stock_id = $stock_id 
ORDER BY price_date DESC
LIMIT 7
");

$candleData = [];
$closePrices = [];

while($row = mysqli_fetch_assoc($candleQuery)){
    $entry = [
        "x" => strtotime($row['price_date']) * 1000,
        "y" => [
            (float)$row['open_price'],
            (float)$row['high_price'],
            (float)$row['low_price'],
            (float)$row['close_price']
        ]
    ];
    $candleData[] = $entry;
    $closePrices[] = (float)$row['close_price'];
}

$candleData = array_reverse($candleData);
$closePrices = array_reverse($closePrices);
?>

<!DOCTYPE html>
<html>
<head>
<title>Dashboard</title>

<link rel="stylesheet" href="style.css">
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

</head>

<body class="dark">

<?php $current = basename($_SERVER['PHP_SELF']); ?>

<nav>
<h2>💹 StockApp</h2>

<div class="nav-links">
<a href="dashboard.php" class="<?= ($current=='dashboard.php')?'active':'' ?>">Overview</a>
<a href="portfolio.php">Portfolio</a>
<a href="stocks.php">Market</a>
<a href="transactions.php">Transactions</a>
<a href="profile.php">Profile</a>
<a href="watchlist.php" class="<?= ($current=='watchlist.php')?'active':'' ?>">Watchlist</a>
</div>

<button class="btn" onclick="toggleTheme()">🌙</button>
</nav>

<div class="container">

<!-- CARDS -->
<div class="grid">

<div class="card">
<h4>Portfolio Value</h4>
<h2>₹<?php echo number_format($total); ?></h2>
<div id="sparkline"></div>
</div>

<div class="card">
<h4>Today’s Gain/Loss</h4>
<h2 class="<?php echo $todayPL>=0?'green':'red'; ?>">
₹<?php echo round($todayPL); ?>
</h2>
</div>

<div class="card">
<h4>Total Stocks</h4>
<h2><?php echo $count; ?></h2>
</div>

<div class="card">
<h4>Status</h4>
<h2 class="green">Active</h2>
</div>

</div>

<!-- STOCK SELECT -->
<div class="card">
<form method="GET">
<select name="stock_id" onchange="this.form.submit()">
<?php
$stocksList = mysqli_query($conn,"SELECT * FROM stocks");
while($s = mysqli_fetch_assoc($stocksList)){
$selected = ($stock_id == $s['stock_id']) ? "selected" : "";
echo "<option value='{$s['stock_id']}' $selected>{$s['company_name']}</option>";
}
?>
</select>
</form>
</div>

<!-- TIMEFRAME -->
<div class="card">
<button onclick="changeRange(1)">1D</button>
<button onclick="changeRange(7)">1W</button>
<button onclick="changeRange(30)">1M</button>
</div>

<!-- CANDLE CHART -->
<div class="card">
<h3><?php echo $stockName; ?></h3>
<div id="candleChart"></div>
</div>

</div>

<script>

/* THEME */
function toggleTheme(){
document.body.classList.toggle("light");
document.body.classList.toggle("dark");
}

/* CANDLE */
var chart = new ApexCharts(document.querySelector("#candleChart"), {
series: [{ data: <?php echo json_encode($candleData); ?> }],
chart: {
type: 'candlestick',
height: 350
},
theme:{
mode: document.body.classList.contains("light") ? "light" : "dark"
},
xaxis:{
type:'datetime',
labels:{style:{colors:"#94a3b8"}}
},
yaxis:{
labels:{style:{colors:"#e2e8f0"}}
},
grid:{borderColor:"#334155"}
});

chart.render();

/* TIMEFRAME WORKING */
function changeRange(days){
fetch("get_chart.php?stock_id=<?php echo $stock_id; ?>&days="+days)
.then(res=>res.json())
.then(data=>{
chart.updateSeries([{data:data}]);
});
}

/* SPARKLINE */
new ApexCharts(document.querySelector("#sparkline"),{
series:[{
data: <?php echo json_encode($closePrices); ?>
}],
chart:{
type:'area',
height:90,
sparkline:{enabled:true}

},

stroke:{
curve:'smooth',
width:3
},

colors:["#00ffa3"],

fill:{
type:'gradient',
gradient:{
shade:'dark',
shadeIntensity:1,
opacityFrom:0.7,
opacityTo:0,
stops:[0,100]
}
},

markers:{
size:4,
colors:["#00ffa3"],
strokeColors:"#020617",
strokeWidth:2
},

tooltip:{
theme: document.body.classList.contains("light") ? "light" : "dark"
}
}).render();

</script>

</body>
</html>