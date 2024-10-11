<?php
session_start();

// Cek apakah pengguna sudah login
if (!isset($_SESSION['customer_id'])) {
    // Jika belum login, arahkan ke halaman login
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
    <h1>Selamat datang, <?php echo $_SESSION['customer_id']; ?></h1> <!-- Jika Anda ingin menampilkan nama pengguna, Anda harus mengambilnya dari database -->
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
