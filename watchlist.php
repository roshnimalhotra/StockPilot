<?php
session_start();
include 'db.php';

if(!isset($_SESSION['user_id'])){
    header("Location: login.html");
    exit();
}

$user_id = $_SESSION['user_id'];

$query = mysqli_query($conn,"
SELECT w.*, s.company_name, s.current_price
FROM watchlist w
JOIN stocks s ON w.stock_id = s.stock_id
WHERE w.user_id=$user_id
");
?>

<!DOCTYPE html>
<html>
<head>
<title>Watchlist</title>
<link rel="stylesheet" href="style.css">
</head>

<body>

<?php $current = basename($_SERVER['PHP_SELF']); ?>

<nav>
<h2>💹 StockApp</h2>

<div class="nav-links">
<a href="dashboard.php">Overview</a>
<a href="portfolio.php">Portfolio</a>
<a href="stocks.php">Market</a>
<a href="transactions.php">Transactions</a>
<a href="profile.php">Profile</a>
<a href="watchlist.php" class="active">Watchlist</a>
</div>

<button class="btn" onclick="toggleDark()">🌙</button>
</nav>

<div class="container">

<h2>⭐ My Watchlist</h2>

<?php if(mysqli_num_rows($query)==0){ ?>
<p>No stocks in watchlist yet 😢</p>
<?php } else { ?>

<table class="premium-table">

<tr>
<th>Stock</th>
<th>Price</th>
<th>Action</th>
</tr>

<?php while($row = mysqli_fetch_assoc($query)){ ?>

<tr>

<td><?php echo $row['company_name']; ?></td>

<td>₹<?php echo $row['current_price']; ?></td>

<td>
<form action="watchlist_remove.php" method="POST">
<input type="hidden" name="stock_id" value="<?php echo $row['stock_id']; ?>">
<button class="btn">Remove</button>
</form>
</td>

</tr>

<?php } ?>

</table>

<?php } ?>

</div>

<script>
function toggleDark(){
document.body.classList.toggle("light");
}
</script>

</body>
</html>