<?php
session_start(); // Memulai sesi

// Cek apakah pengguna sudah login
if (!isset($_SESSION['customer_id'])) {
    header("Location: login.php"); // Arahkan ke halaman login jika tidak terautentikasi
    exit();
}

// Koneksi ke database
include 'config.php';

function getTransactionHistory($customer_id) {
    global $conn; // Pastikan koneksi dapat diakses dalam fungsi
    $query = "SELECT * FROM t_transaction WHERE m_customer_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $customer_id); // Menggunakan integer untuk customer_id
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC); // Mengembalikan hasil sebagai array asosiatif
}

// Ambil riwayat transaksi dari database
$customer_id = $_SESSION['customer_id']; // Ambil customer_id dari sesi
$transactions = getTransactionHistory($customer_id);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Riwayat Transaksi</title>
</head>
<body>
    <h1>Riwayat Transaksi</h1>
    <ul>
        <?php if (empty($transactions)): ?>
            <li>Tidak ada riwayat transaksi.</li>
        <?php else: ?>
            <?php foreach ($transactions as $transaction): ?>
                <li>
                    <?php echo $transaction['transaction_date']; ?> - <?php echo $transaction['transaction_type']; ?> - <?php echo $transaction['transaction_amount']; ?>
                </li>
            <?php endforeach; ?>
        <?php endif; ?>
    </ul>
</body>
</html>
