<?php 
session_start();
include 'db.php'; 

$user_id = $_SESSION['user_id'] ?? 0;

// GAINERS
$gainers = mysqli_query($conn,"
SELECT * FROM stocks 
ORDER BY current_price DESC 
LIMIT 3
");

// LOSERS
$losers = mysqli_query($conn,"
SELECT * FROM stocks 
ORDER BY current_price ASC 
LIMIT 3
");

// PORTFOLIO PREVIEW
$portfolio = mysqli_query($conn,"
SELECT p.*, s.company_name, s.current_price 
FROM portfolio p
JOIN stocks s ON p.stock_id = s.stock_id
WHERE p.user_id = $user_id
LIMIT 3
");
?>
<!DOCTYPE html>
<html>
<head>
<title>Stock Market</title>
<link rel="stylesheet" href="style.css">

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;700&display=swap" rel="stylesheet">

<style>
.hero{
height:90vh;
display:flex;
flex-direction:column;
justify-content:center;
align-items:center;
text-align:center;
background: radial-gradient(circle at top,#0ea5e9,#020617);
}

.hero h1{
font-size:50px;
margin-bottom:15px;
background:linear-gradient(45deg,#00e5ff,#00ffa3);
-webkit-background-clip:text;
color:transparent;
}

.hero p{
opacity:0.8;
margin-bottom:30px;
}

.glow{
box-shadow:0 0 40px rgba(0,229,255,0.4);
}

.buttons{
display:flex;
gap:20px;
}

.section{
padding:50px 8%;
}

.grid{
display:grid;
grid-template-columns:repeat(auto-fit,minmax(250px,1fr));
gap:20px;
}

</style>
</head>

<body>

<!-- NAVBAR -->
<nav>
<h2>💹 StockApp</h2>

<div class="nav-links">
<a href="dashboard.php">Overview</a>
<a href="profile.php">Profile</a>
<a href="portfolio.php">Portfolio</a>
<a href="stocks.php">Market</a>
<a href="transactions.php">Transactions</a>
<a href="news.php">News</a>
<a href="community.php">Community</a>
</div>

<button class="btn" onclick="toggleDark()">🌙</button>
</nav>

<!-- HERO -->
<div class="hero">
<h1>Trade Smart. Grow Wealth.</h1>
<p>AI-powered stock management platform for modern investors.</p>

<div class="buttons">
<button class="btn glow" onclick="location.href='login.html'">Login</button>
<button class="btn" onclick="location.href='register.html'">Get Started</button>
</div>
</div>

<!-- 📊 MARKET SUMMARY -->
<div class="section">
<h2>📊 Market Summary</h2>

<div class="grid">

<div class="card">
<h3>🔥 Gainers</h3>
<?php while($g = mysqli_fetch_assoc($gainers)){ ?>
<p class="green">
<?php echo $g['company_name']; ?> ₹<?php echo $g['current_price']; ?>
</p>
<?php } ?>
</div>

<div class="card">
<h3>📉 Losers</h3>
<?php while($l = mysqli_fetch_assoc($losers)){ ?>
<p class="red">
<?php echo $l['company_name']; ?> ₹<?php echo $l['current_price']; ?>
</p>
<?php } ?>
</div>

<div class="card">
<h3>📊 Indices</h3>
<p>NIFTY: 22,500</p>
<p>SENSEX: 74,200</p>
</div>

</div>
</div>

<!-- PORTFOLIO PREVIEW -->
<div class="section">
<h2>💼 Your Portfolio</h2>

<div class="card">

<?php 
if($user_id && mysqli_num_rows($portfolio)>0){
while($p = mysqli_fetch_assoc($portfolio)){ 
$profit = ($p['current_price'] - $p['average_price']) * $p['quantity'];
$color = $profit >= 0 ? "green" : "red";
?>

<div style="display:flex;justify-content:space-between;margin:8px 0;">
<span><?php echo $p['company_name']; ?></span>
<span class="<?php echo $color; ?>">
₹<?php echo number_format($profit); ?>
</span>
</div>

<?php } } else { ?>
<p style="opacity:0.7;">Login to view your portfolio</p>
<?php } ?>

</div>
</div>

<!-- DARK MODE -->
<script>
function toggleDark(){
document.body.classList.toggle("light");
}
</script>

</body>
</html>