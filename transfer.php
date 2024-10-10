<?php
session_start();
include 'config.php'; // Pastikan ini berisi koneksi ke database MySQL

// Proses transfer
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $from_account = $_POST['from_account']; // ID akun pengirim
    $to_account = $_POST['to_account']; // ID akun penerima
    $amount = $_POST['amount']; // Jumlah transfer

    // Mulai transaksi
    mysqli_begin_transaction($conn);

    try {
        // Insert data transaksi
        $query = "INSERT INTO t_transaction (m_customer_id, transaction_type, transaction_amount) VALUES (?, 'transfer', ?)";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, 'id', $from_account, $amount);
        mysqli_stmt_execute($stmt);

        // Kurangi saldo dari akun pengirim
        $query = "UPDATE m_portfolio_account SET clear_balance = clear_balance - ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, 'di', $amount, $from_account);
        mysqli_stmt_execute($stmt);

        // Tambah saldo ke akun penerima
        $query = "UPDATE m_portfolio_account SET clear_balance = clear_balance + ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, 'di', $amount, $to_account);
        mysqli_stmt_execute($stmt);

        // Commit transaksi
        mysqli_commit($conn);
        echo "Transfer berhasil!";
    } catch (Exception $e) {
        // Rollback jika ada kesalahan
        mysqli_rollback($conn);
        echo "Transfer gagal: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Transfer Antar Akun</title>
</head>
<body>
    <h1>Transfer Antar Akun</h1>
    <form method="POST" action="">
        Dari Akun: <input type="text" name="from_account" required><br>
        Ke Akun: <input type="text" name="to_account" required><br>
        Jumlah: <input type="number" name="amount" required><br>
        <input type="submit" value="Kirim">
    </form>
</body>
</html>
