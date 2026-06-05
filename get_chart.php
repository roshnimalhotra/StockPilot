<?php
include 'db.php';

$id = $_GET['stock_id'];
$days = $_GET['days'];

$q = mysqli_query($conn,"
SELECT * FROM stock_price_history
WHERE stock_id=$id
ORDER BY price_date DESC
LIMIT $days
");

$data=[];

while($row=mysqli_fetch_assoc($q)){
$data[]=[
"x"=>strtotime($row['price_date'])*1000,
"y"=>[
(float)$row['open_price'],
(float)$row['high_price'],
(float)$row['low_price'],
(float)$row['close_price'],
]
];
}

echo json_encode(array_reverse($data));