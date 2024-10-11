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

// Ambil riwayat transaksi dari database
$stmt = $conn->prepare("
    SELECT 
        t.transaction_date, 
        t.transaction_amount, 
        c_sender.account_name AS sender_name, 
        c_receiver.account_name AS receiver_name 
    FROM 
        t_transaction t
    JOIN 
        m_portfolio_account c_sender ON t.from_account_number = c_sender.account_number
    JOIN 
        m_portfolio_account c_receiver ON t.to_account_number = c_receiver.account_number
    WHERE 
        c_sender.m_customer_id = ? OR c_receiver.m_customer_id = ?
");
$stmt->bind_param("ii", $customer_id, $customer_id);
$stmt->execute();
$result = $stmt->get_result();

$transactions = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Transaksi</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1 class="text-center">Riwayat Transaksi</h1>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Nama Pengirim</th>
                    <th>Nama Penerima</th>
                    <th>Nominal</th>
                    <th>Jenis Transaksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($transactions as $transaction): ?>
                    <tr>
                        <td><?= htmlspecialchars($transaction['transaction_date']); ?></td>
                        <td><?= htmlspecialchars($transaction['sender_name']); ?></td>
                        <td><?= htmlspecialchars($transaction['receiver_name']); ?></td>
                        <td><?= number_format($transaction['transaction_amount'], 2); ?></td>
                        <td>
                            <?php
                            // Tentukan jenis transaksi
                            if ($transaction['sender_name'] == $customer_id) {
                                echo "Uang Keluar"; // Jika pengirim adalah pengguna
                            } else {
                                echo "Uang Masuk"; // Jika penerima adalah pengguna
                            }
                            ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <div class="text-center">
            <a href="dashboard.php" class="btn btn-secondary">Kembali ke Dashboard</a>
        </div>
    </div>
</body>
</html>
