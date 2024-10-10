<?php
session_start();
include 'config.php'; // Sertakan file config.php untuk koneksi database

if (!isset($_SESSION['customer_id'])) {
    header('Location: login.php'); // Redirect ke halaman login
    exit();
}

// Proses permintaan top-up
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $account_id = $_POST['account_id'];
    $amount = $_POST['amount'];

    if (!empty($account_id) && !empty($amount)) {
        // Masukkan transaksi top-up
        $query = "INSERT INTO ebanking.t_transaction (m_customer_id, transaction_type, transaction_amount) VALUES (?, 'topup', ?)";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, 'id', $_SESSION['customer_id'], $amount);
        mysqli_stmt_execute($stmt);

        // Update saldo
        $query = "UPDATE ebanking.m_portfolio_account SET clear_balance = clear_balance + ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, 'di', $amount, $account_id);
        mysqli_stmt_execute($stmt);

        $success_message = "Top Up berhasil!";
    } else {
        $error_message = "Semua field harus diisi!";
    }
}
?>

<html>
<head>
    <title>Top Up</title>
</head>
<body>
    <h1>Top Up</h1>
    <?php if (isset($error_message)) echo "<p style='color:red;'>$error_message</p>"; ?>
    <?php if (isset($success_message)) echo "<p style='color:green;'>$success_message</p>"; ?>
    
    <form method="POST" action="">
        <label for="account_id">Nomor Akun:</label><br>
        <input type="text" id="account_id" name="account_id" required><br><br>

        <label for="amount">Jumlah:</label><br>
        <input type="number" id="amount" name="amount" required><br><br>

        <input type="submit" value="Top Up">
    </form>

    <p><a href="index.php">Kembali ke Menu Utama</a></p>
</body>
</html>
