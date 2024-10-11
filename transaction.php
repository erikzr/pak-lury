<?php
include 'config.php'; // Pastikan Anda sudah membuat file config.php untuk koneksi database
session_start(); // Memulai sesi

// Periksa apakah pengguna sudah login
if (!isset($_SESSION['customer_id'])) {
    header("Location: login.php"); // Arahkan ke halaman login jika belum login
    exit();
}

// Mendapatkan ID customer dari sesi
$customer_id = $_SESSION['customer_id'];

// Inisialisasi variabel untuk pesan
$message = "";

// Proses form jika ada pengiriman data
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $from_account_number = $_POST['from_account']; // Nomor akun asal
    $to_account_number = $_POST['to_account']; // Nomor akun tujuan
    $amount = $_POST['amount']; // Jumlah

    // Validasi akun asal
    $stmt = $conn->prepare("SELECT id, available_balance FROM m_portfolio_account WHERE account_number = ? AND m_customer_id = ?");
    $stmt->bind_param("si", $from_account_number, $customer_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $message = "Akun asal tidak ditemukan!";
    } else {
        // Ambil akun asal
        $from_account = $result->fetch_assoc();

        // Validasi akun tujuan
        $stmt = $conn->prepare("SELECT id, account_name FROM m_portfolio_account WHERE account_number = ?");
        $stmt->bind_param("s", $to_account_number);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            $message = "Akun tujuan tidak ditemukan!";
        } else {
            // Ambil akun tujuan
            $to_account = $result->fetch_assoc();

            // Cek apakah saldo cukup
            if ($from_account['available_balance'] < $amount) {
                $message = "Saldo tidak cukup untuk melakukan transaksi!";
            } else {
                // Proses transaksi
                $conn->begin_transaction();

                try {
                    // Kurangi saldo dari akun asal
                    $new_from_balance = $from_account['available_balance'] - $amount;
                    $stmt = $conn->prepare("UPDATE m_portfolio_account SET available_balance = ? WHERE id = ?");
                    $stmt->bind_param("di", $new_from_balance, $from_account['id']);
                    $stmt->execute();

                    // Tambah saldo ke akun tujuan
                    $stmt = $conn->prepare("SELECT available_balance FROM m_portfolio_account WHERE id = ?");
                    $stmt->bind_param("i", $to_account['id']);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $to_account_data = $result->fetch_assoc();
                    $new_to_balance = $to_account_data['available_balance'] + $amount;

                    $stmt = $conn->prepare("UPDATE m_portfolio_account SET available_balance = ? WHERE id = ?");
                    $stmt->bind_param("di", $new_to_balance, $to_account['id']);
                    $stmt->execute();

                    // Simpan data transaksi ke tabel t_transaction
                    $stmt = $conn->prepare("INSERT INTO t_transaction (m_customer_id, transaction_amount, status, transaction_date, from_account_number, to_account_number) VALUES (?, ?, ?, NOW(), ?, ?)");
                    $status = 'success'; // Status transaksi
                    $stmt->bind_param("isdss", $customer_id, $amount, $status, $from_account_number, $to_account_number);
                    $stmt->execute();

                    // Commit transaksi
                    $conn->commit();
                    $message = "Transaksi berhasil!";
                } catch (Exception $e) {
                    // Rollback jika ada error
                    $conn->rollback();
                    $message = "Terjadi kesalahan dalam proses transaksi: " . $e->getMessage();
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaksi</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h2 class="text-center">Transaksi</h2>

        <!-- Tampilkan pesan -->
        <?php if ($message): ?>
            <div class="alert alert-info"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="from_account">Dari Akun (Nomor Akun)</label>
                <input type="text" name="from_account" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="to_account">Ke Akun (Nomor Akun)</label>
                <input type="text" name="to_account" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="amount">Jumlah</label>
                <input type="number" name="amount" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Kirim</button>
        </form>
        <div class="text-center mt-3">
            <a href="dashboard.php" class="btn btn-secondary">Kembali ke Dashboard</a>
        </div>
    </div>
</body>
</html>
