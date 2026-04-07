/*
====================================================
UPDATE FITUR LANJUTAN: TRANSAKSI + LAPORAN + UI
====================================================
*/

-- Tambah tabel transaksi
CREATE TABLE transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    total DECIMAL(10,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Detail transaksi
CREATE TABLE transaction_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_id INT,
    product_id INT,
    qty INT,
    price DECIMAL(10,2)
);

/*
====================================================
UI DASHBOARD (Bootstrap upgrade)
====================================================
*/
<?php
session_start();
if(!isset($_SESSION['user'])) header("Location: auth/login.php");
?>
<!DOCTYPE html>
<html>
<head>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container mt-4">
<h3>Halo, <?= $_SESSION['user']['name'] ?></h3>
<a href="products/index.php" class="btn btn-primary">Produk</a>
<a href="transactions.php" class="btn btn-success">Kasir</a>
<a href="report.php" class="btn btn-warning">Laporan</a>
<a href="auth/logout.php" class="btn btn-danger">Logout</a>
</body>
</html>

/*
====================================================
TRANSAKSI (transactions.php)
====================================================
*/
<?php
session_start();
require 'config/database.php';

$products = $conn->query("SELECT * FROM products")->fetchAll();

if(isset($_POST['checkout'])){
    $conn->beginTransaction();

    $total = 0;
    foreach($_POST['qty'] as $id => $qty){
        if($qty > 0){
            $stmt = $conn->prepare("SELECT * FROM products WHERE id=?");
            $stmt->execute([$id]);
            $p = $stmt->fetch();
            $total += $p['price'] * $qty;
        }
    }

    $stmt = $conn->prepare("INSERT INTO transactions (user_id,total) VALUES (?,?)");
    $stmt->execute([$_SESSION['user']['id'],$total]);
    $trx_id = $conn->lastInsertId();

    foreach($_POST['qty'] as $id => $qty){
        if($qty > 0){
            $stmt = $conn->prepare("SELECT * FROM products WHERE id=?");
            $stmt->execute([$id]);
            $p = $stmt->fetch();

            $stmt = $conn->prepare("INSERT INTO transaction_details VALUES(NULL,?,?,?,?)");
            $stmt->execute([$trx_id,$id,$qty,$p['price']]);

            // update stok
            $stmt = $conn->prepare("UPDATE products SET stock = stock - ? WHERE id=?");
            $stmt->execute([$qty,$id]);
        }
    }

    $conn->commit();
    echo "Transaksi berhasil!";
}
?>

<form method="POST" class="container">
<h3>Kasir</h3>
<?php foreach($products as $p): ?>
<div class="row mb-2">
<div class="col"><?= $p['name'] ?> (<?= $p['stock'] ?>)</div>
<div class="col">
<input type="number" name="qty[<?= $p['id'] ?>]" value="0" class="form-control">
</div>
</div>
<?php endforeach; ?>
<button name="checkout" class="btn btn-success">Checkout</button>
</form>

/*
====================================================
LAPORAN (report.php)
====================================================
*/
<?php
require 'config/database.php';

$data = $conn->query("SELECT * FROM transactions ORDER BY created_at DESC")->fetchAll();
?>

<h3>Laporan Penjualan</h3>
<table class="table table-bordered">
<tr><th>ID</th><th>Total</th><th>Tanggal</th></tr>
<?php foreach($data as $d): ?>
<tr>
<td><?= $d['id'] ?></td>
<td><?= $d['total'] ?></td>
<td><?= $d['created_at'] ?></td>
</tr>
<?php endforeach; ?>
</table>
