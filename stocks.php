<?php
session_start();
include 'db.php';

if(!isset($_SESSION['user_id'])){
    header("Location: login.html");
    exit();
}

$username = $_SESSION['username'] ?? "User";
$initials = strtoupper(substr($username,0,1));

$result = mysqli_query($conn, "SELECT * FROM stocks");

$watchRes = mysqli_query($conn,"
SELECT stock_id FROM watchlist 
WHERE user_id=".$_SESSION['user_id']
);

$watchlist = [];
while($w = mysqli_fetch_assoc($watchRes)){
    $watchlist[] = $w['stock_id'];
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Market Dashboard</title>
<link rel="stylesheet" href="style.css">
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

<style>
/* HEADER CLUSTER */
.header-right{
display:flex;
align-items:center;
gap:15px;
}

/* SEARCH */
.search-box{
padding:6px 10px;
border-radius:8px;
border:1px solid rgba(255,255,255,0.1);
background:rgba(255,255,255,0.05);
color:white;
}

/* NOTIFICATION */
.bell{
position:relative;
font-size:20px;
cursor:pointer;
}
.badge{
position:absolute;
top:-6px;
right:-6px;
background:red;
color:white;
font-size:10px;
padding:2px 5px;
border-radius:50%;
}

/* AVATAR */
.avatar{
width:38px;
height:38px;
border-radius:50%;
background:linear-gradient(135deg,#7c3aed,#8b5cf6);
display:flex;
align-items:center;
justify-content:center;
cursor:pointer;
font-weight:bold;
}

/* DROPDOWN */
.dropdown{
position:absolute;
top:60px;
right:20px;
background:rgba(255,255,255,0.05);
backdrop-filter:blur(10px);
padding:10px;
border-radius:12px;
display:none;
flex-direction:column;
min-width:160px;
}

.dropdown a{
padding:8px;
color:white;
text-decoration:none;
}

.dropdown a:hover{
background:rgba(124,58,237,0.2);
}
</style>

</head>

<body>

<!-- NAVBAR -->
<nav>
<h2>💹 StockApp</h2>

<div class="nav-links">
<a href="dashboard.php">Overview</a>
<a href="portfolio.php">Portfolio</a>
<a href="stocks.php" class="active">Market</a>
<a href="transactions.php">Transactions</a>
<a href="watchlist.php">Watchlist</a>
</div>

<!-- RIGHT CLUSTER -->
<div class="header-right">

<input type="text" id="search" class="search-box" placeholder="Search stocks...">



<button class="btn" onclick="exportCSV()">Export</button>

<div class="avatar" id="avatar"><?php echo $initials; ?></div>

</div>

<button class="btn" onclick="toggleDark()">🌙</button>
</nav>

<!-- DROPDOWN -->
<div class="dropdown" id="dropdown">
<a href="profile.php">My Profile</a>
<a href="portfolio.php">Portfolio</a>
<a href="logout.php">Logout</a>
</div>

<div class="container">

<h2 class="page-title">📈 Market Dashboard</h2>

<div class="glass-card">

<table class="premium-table" id="stockTable">

<tr>
<th>Company</th>
<th>Symbol</th>
<th>Sector</th>
<th>Price</th>
<th>Market Cap</th>
<th>Trend</th>
<th>Chart</th>
<th>Action</th>
</tr>

<?php
while($row = mysqli_fetch_assoc($result)){

$change = rand(-5,5);
$color = $change >= 0 ? "green" : "red";
$id = $row['stock_id'];

$isInWatchlist = in_array($id, $watchlist);

$history = mysqli_query($conn,"
SELECT close_price 
FROM stock_price_history 
WHERE stock_id=$id 
ORDER BY price_date DESC 
LIMIT 7
");

$data = [];
while($h = mysqli_fetch_assoc($history)){
$data[] = (float)$h['close_price'];
}
$data = array_reverse($data);
?>

<tr class="table-row">

<td style="display:flex;align-items:center;gap:8px;">
<?php echo $row['company_name']; ?>
<button class="watch-btn <?= $isInWatchlist ? 'active' : '' ?>" data-id="<?php echo $id; ?>">⭐</button>
</td>

<td><?php echo $row['symbol']; ?></td>
<td><?php echo $row['sector']; ?></td>

<td class="price <?php echo $color; ?>">
<span class="price-value">₹<?php echo $row['current_price']; ?></span>
</td>

<td>₹<?php echo number_format($row['market_cap']); ?> Cr</td>

<td class="trend-text">
<span class="<?php echo $change >= 0 ? 'arrow-up':'arrow-down'; ?>">
<?php echo $change >= 0 ? '↑':'↓'; ?>
</span>
</td>

<td>
<div id="chart_<?php echo $id; ?>"></div>
</td>

<td>
<form action="buy.php" method="POST" class="buy-form">

<input type="hidden" name="stock_id" value="<?php echo $id; ?>">
<input type="hidden" name="price" value="<?php echo $row['current_price']; ?>">
<input type="hidden" name="risk_mode" class="risk-mode" value="manual">

<input type="number" name="quantity" placeholder="Qty" required class="qty">

<div class="risk-box">
<span class="risk-label">Auto Risk Mode</span>
<label class="switch">
<input type="checkbox" class="risk-toggle">
<span class="slider"></span>
</label>
</div>

<input type="number" step="0.01" name="stop_loss" class="sl-input" placeholder="Stop Loss">
<input type="number" step="0.01" name="resistance" class="res-input" placeholder="Resistance">

<div class="est-cost"></div>
<!-- BROKERAGE PANEL -->
<div class="broker-box">
    <div><small>Trade Value</small><br><span class="trade-val">₹0</span></div>
    <div><small>Brokerage (0.5%)</small><br><span class="brokerage-val">₹0</span></div>
    <div><small>Total</small><br><span class="total-val">₹0</span></div>
</div>

<div class="rr-box">
<div><small>Risk</small><br><span class="risk-amount red">₹0</span></div>
<div><small>Target</small><br><span class="target-amount green">₹0</span></div>
</div>

<button class="buy-btn">Buy</button>

</form>
</td>

</tr>

<script>
(function(){
var data = <?php echo json_encode($data); ?>;
if(data.length === 0) return;

let chartEl = document.querySelector("#chart_<?php echo $id; ?>");

let chart = new ApexCharts(chartEl,{
series:[{ data:data }],
chart:{type:'area',height:70,sparkline:{enabled:true}},
stroke:{curve:'smooth',width:2},
colors:["#22c55e"],
fill:{type:'gradient'},
tooltip:{theme:"dark"}
});

chart.render();
chartEl.apexchart = chart;
})();
</script>

<?php } ?>

</table>
</div>
</div>

<script>
function toggleDark(){
document.body.classList.toggle("light");
}

/* SEARCH FILTER */
document.getElementById("search").addEventListener("input", function(){
let val = this.value.toLowerCase();
document.querySelectorAll(".table-row").forEach(row=>{
row.style.display = row.innerText.toLowerCase().includes(val) ? "" : "none";
});
});

/* DROPDOWN */
let avatar = document.getElementById("avatar");
let dropdown = document.getElementById("dropdown");

avatar.onclick = ()=>{
dropdown.style.display = dropdown.style.display === "flex" ? "none" : "flex";
};

document.addEventListener("click", (e)=>{
if(!avatar.contains(e.target)) dropdown.style.display="none";
});

/* EXPORT CSV */
function exportCSV(){
let rows = document.querySelectorAll("#stockTable tr");
let csv = [];

rows.forEach(row=>{
let cols = row.querySelectorAll("td,th");
let data = [];
cols.forEach(col=> data.push(col.innerText));
csv.push(data.join(","));
});

let blob = new Blob([csv.join("\n")], {type:"text/csv"});
let a = document.createElement("a");
a.href = URL.createObjectURL(blob);
a.download = "stocks.csv";
a.click();
}
</script>
<script>
document.addEventListener("DOMContentLoaded", function(){

/* =========================
ESTIMATE + RR + AUTO RISK 
========================= */

document.querySelectorAll(".buy-form").forEach(form=>{

let qty = form.querySelector(".qty");
let price = parseFloat(form.querySelector("input[name='price']").value);

let est = form.querySelector(".est-cost");
let tradeEl = form.querySelector(".trade-val");
let brokerEl = form.querySelector(".brokerage-val");
let totalEl = form.querySelector(".total-val");

let toggle = form.querySelector(".risk-toggle");
let sl = form.querySelector(".sl-input");
let res = form.querySelector(".res-input");
let mode = form.querySelector(".risk-mode");

let riskEl = form.querySelector(".risk-amount");
let targetEl = form.querySelector(".target-amount");

/* 🔥 MAIN CALC FUNCTION */
function calc(){

let q = parseFloat(qty.value) || 0;
let s = parseFloat(sl.value) || 0;
let r = parseFloat(res.value) || 0;

/* =========================
TRADE + BROKERAGE
========================= */

if(q > 0){

let trade = q * price;
let brokerage = trade * 0.005;
let total = trade + brokerage;

/* ESTIMATE */
est.innerText = "Est: ₹" + Math.round(trade).toLocaleString();

/* BROKER UI */
if(tradeEl) tradeEl.innerText = "₹" + Math.round(trade).toLocaleString();
if(brokerEl) brokerEl.innerText = "₹" + Math.round(brokerage).toLocaleString();
if(totalEl) totalEl.innerText = "₹" + Math.round(total).toLocaleString();

}else{

est.innerText = "";

if(tradeEl) tradeEl.innerText = "₹0";
if(brokerEl) brokerEl.innerText = "₹0";
if(totalEl) totalEl.innerText = "₹0";
}

/* =========================
RISK / TARGET
========================= */

if(q <= 0 || s <= 0 || r <= 0){
riskEl.innerText = "₹0";
targetEl.innerText = "₹0";
return;
}

let risk = Math.max(0, (price - s) * q);
let target = Math.max(0, (r - price) * q);

riskEl.innerText = "₹" + Math.round(risk).toLocaleString();
targetEl.innerText = "₹" + Math.round(target).toLocaleString();
}

/* =========================
AUTO MODE
========================= */

toggle.addEventListener("change", ()=>{

if(toggle.checked){

sl.value = (price * 0.95).toFixed(2);
res.value = (price * 1.10).toFixed(2);

sl.readOnly = true;
res.readOnly = true;

mode.value = "auto";

}else{

sl.readOnly = false;
res.readOnly = false;

sl.value = "";
res.value = "";

mode.value = "manual";
}

calc();
});

/* =========================
LIVE INPUT EVENTS
========================= */

qty.addEventListener("input", calc);
sl.addEventListener("input", calc);
res.addEventListener("input", calc);

/* INITIAL RUN */
calc();
});
});
</script>
<script>
document.addEventListener("DOMContentLoaded", function(){

/* LIVE PRICE + CHART UPDATE */
setInterval(()=>{

document.querySelectorAll(".table-row").forEach(row=>{

let priceEl = row.querySelector(".price-value");
let chartEl = row.querySelector("[id^='chart_']");

if(!priceEl) return;

let oldPrice = parseFloat(priceEl.innerText.replace("₹",""));

/* random movement */
let change = (Math.random()*4 - 2).toFixed(2);
let newPrice = (oldPrice + parseFloat(change)).toFixed(2);

/* UPDATE PRICE */
priceEl.innerText = "₹" + newPrice;

/* FLASH EFFECT */
let td = priceEl.closest(".price");
td.classList.remove("flash-up","flash-down");

if(change >= 0){
td.classList.add("flash-up");
}else{
td.classList.add("flash-down");
}

/* UPDATE CHART */
if(chartEl && chartEl.apexchart){

let chart = chartEl.apexchart;

/* get old data safely */
let data = chart.w.config.series[0].data;

/* push new price */
data.push(parseFloat(newPrice));

/* keep last 7 points */
if(data.length > 7) data.shift();

/* update chart */
chart.updateSeries([{ data: data }]);
}

});

},5000); // every 5 seconds

});
</script>
<script>
document.querySelectorAll(".watch-btn").forEach(btn=>{

btn.addEventListener("click", function(){

let stockId = this.dataset.id;
let button = this;

/* AJAX CALL */
fetch("watchlist_toggle.php", {
    method: "POST",
    headers: {
        "Content-Type": "application/x-www-form-urlencoded"
    },
    body: "stock_id=" + stockId
})
.then(res => res.text())
.then(data => {

    if(data === "added"){
        button.classList.add("active");
    }else if(data === "removed"){
        button.classList.remove("active");
    }

});
});
});
</script>