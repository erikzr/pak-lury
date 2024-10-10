<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Halaman Utama</title>
</head>
<body>
    <h1>Selamat datang, <?php echo $_SESSION['user']; ?></h1>
    <nav>
        <ul>
            <li><a href="customer_profile.php">Profil</a></li>
            <li><a href="transfer.php">Transaksi</a></li>
            <li><a href="transaction_history.php">Riwayat Transaksi</a></li>
            <li><a href="topup.php">Top-up</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </nav>
</body>
</html>
