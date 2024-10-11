<?php
session_start();
include 'config.php'; // Sertakan file config.php untuk koneksi database

if (!isset($_SESSION['customer_id'])) {
    header('Location: login.php'); // Redirect ke halaman login
    exit();
}

// Ambil saldo saat ini
$query = "SELECT clear_balance FROM m_portfolio_account WHERE m_customer_id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, 'i', $_SESSION['customer_id']); // Menggunakan ID pengguna
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Periksa apakah hasil query ada
if ($result && mysqli_num_rows($result) > 0) {
    $current_balance = mysqli_fetch_assoc($result)['clear_balance'];
} else {
    $current_balance = 0; // Set default saldo jika akun tidak ditemukan
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username']; // Menggunakan username
    $amount = $_POST['amount'];

    if (!empty($username) && !empty($amount) && $amount > 0) {
        // Cek apakah username valid dan mendapatkan account_id
        $query = "SELECT a.id FROM m_portfolio_account a 
                  JOIN m_customer c ON a.account_number = c.registration_account_number 
                  WHERE LOWER(c.customer_username) = LOWER(?)";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, 's', $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($result && mysqli_num_rows($result) > 0) {
            $account = mysqli_fetch_assoc($result);
            $account_id = $account['id'];

            // Generate kode transaksi
            $transaction_code = uniqid('TRX-', true);

            // Masukkan ke tabel pending_topup_requests
            $query = "INSERT INTO pending_topup_requests (account_id, transaction_code, amount) VALUES (?, ?, ?)";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, 'isd', $account_id, $transaction_code, $amount);
            mysqli_stmt_execute($stmt);

            $success_message = "Permintaan top-up berhasil! Kode Transaksi: " . $transaction_code;
        } else {
            // Debug output
            echo "<p style='color:red;'>Debug: Username yang dicari: " . htmlspecialchars($username) . "</p>";
            $num_rows = mysqli_num_rows($result);
            echo "<p style='color:red;'>Debug: Jumlah hasil ditemukan: " . $num_rows . "</p>";

            $error_message = "Akun dengan username tersebut tidak ditemukan!";
        }
    } else {
        $error_message = "Semua field harus diisi dan jumlah harus lebih dari 0!";
    }
}
?>

<html>
<head>
    <title>Request Top-Up</title>
</head>
<body>
    <h1>Request Top-Up</h1>
    <?php if (isset($error_message)) echo "<p style='color:red;'>$error_message</p>"; ?>
    <?php if (isset($success_message)) echo "<p style='color:green;'>$success_message</p>"; ?>

    <p>Saldo saat ini: <strong><?php echo htmlspecialchars($current_balance); ?></strong></p>

    <form method="POST" action="">
        <label for="username">Username:</label><br>
        <input type="text" id="username" name="username" required><br><br>

        <label for="amount">Jumlah:</label><br>
        <input type="number" id="amount" name="amount" required><br><br>

        <input type="submit" value="Request Top-Up">
    </form>

    <p><a href="index.php">Kembali ke Menu Utama</a></p>
</body>
</html>
