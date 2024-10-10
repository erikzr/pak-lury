<?php
session_start();
include 'config.php'; // Sertakan file config.php untuk koneksi database

if (!isset($_SESSION['customer_id'])) {
    header('Location: login.php'); // Redirect ke halaman login
    exit();
}

// Tampilkan riwayat transaksi pada halaman
$customer_id = $_SESSION['customer_id'];
$query = "SELECT * FROM ebanking.t_transaction WHERE m_customer_id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, 'i', $customer_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$transactions = mysqli_fetch_all($result, MYSQLI_ASSOC);
?>

<html>
<head>
    <title>Riwayat Transaksi</title>
</head>
<body>
    <h1>Riwayat Transaksi</h1>
    <ul>
        <?php foreach ($transactions as $transaction) { ?>
            <li>
                <?php echo $transaction['transaction_date']; ?> - <?php echo $transaction['transaction_type']; ?> - <?php echo $transaction['transaction_amount']; ?>
            </li>
        <?php } ?>
    </ul>

    <p><a href="index.php">Kembali ke Menu Utama</a></p>
</body>
</html>
