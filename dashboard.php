<?php
include 'config.php'; // Pastikan Anda sudah membuat file config.php untuk koneksi database
session_start(); // Memulai sesi

// Periksa apakah pengguna sudah login
if (!isset($_SESSION['customer_id'])) {
    header("Location: login.php"); // Arahkan ke halaman login jika belum login
    exit();
}

// Ambil ID customer dari sesi
$customer_id = $_SESSION['customer_id'];

// Ambil data customer dari database
$stmt = $conn->prepare("SELECT customer_name FROM m_customer WHERE id = ?");
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $customer = $result->fetch_assoc();
} else {
    // Jika tidak ditemukan, redirect ke login
    header("Location: login.php");
    exit();
}

// Ambil akun customer (misalnya, dari m_portfolio_account)
$stmt = $conn->prepare("SELECT account_number, m_customer_id, available_balance FROM m_portfolio_account WHERE m_customer_id = ?");
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$result = $stmt->get_result();

$accounts = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1 class="text-center">Dashboard</h1>
        <p class="text-center">Selamat datang, <?= htmlspecialchars($customer['customer_name']); ?></p>
        <h2 class="mt-4">Informasi Akun</h2>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>No Akun</th>
                    <th>Nama Akun</th>
                    <th>Saldo</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($accounts as $account): ?>
                    <tr>
                        <td><?= htmlspecialchars($account['account_number']); ?></td>
                        <td><?= htmlspecialchars($customer['customer_name']); ?></td> <!-- Menggunakan customer_name -->
                        <td><?= number_format($account['available_balance'], 2); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <div class="text-center">
            <a href="transaction.php" class="btn btn-success">Transaksi</a>
            <a href="transaction_history.php" class="btn btn-info">Riwayat Transaksi</a>
            <a href="logout.php" class="btn btn-danger">Logout</a>
        </div>
    </div>
</body>
</html>
