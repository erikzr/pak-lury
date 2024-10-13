<?php
include 'config.php'; // Pastikan Anda sudah membuat file config.php untuk koneksi database

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $customer_name = $_POST['customer_name'];
    $customer_username = $_POST['customer_username'];
    $customer_pin = $_POST['customer_pin'];
    $customer_phone = $_POST['customer_phone'];
    $customer_email = $_POST['customer_email'];
    
    // Pastikan PIN terenkripsi sebelum disimpan
    $hashed_pin = password_hash($customer_pin, PASSWORD_DEFAULT);

    // Query untuk menyimpan data registrasi ke dalam database
    $stmt = $conn->prepare("INSERT INTO m_customer (customer_name, customer_username, customer_pin, customer_phone, customer_email, created, createdby) VALUES (?, ?, ?, ?, ?, NOW(), 1)");
    $stmt->bind_param("sssss", $customer_name, $customer_username, $hashed_pin, $customer_phone, $customer_email);

    if ($stmt->execute()) {
        // Berikan saldo awal sebesar 100.000
        $last_id = $stmt->insert_id; // Mendapatkan ID dari customer yang baru ditambahkan
        $stmt_balance = $conn->prepare("INSERT INTO m_portfolio_account (m_customer_id, account_number, available_balance, clear_balance, created, createdby) VALUES (?, ?, ?, ?, NOW(), 1)");
        $account_number = "ACC" . str_pad($last_id, 5, "0", STR_PAD_LEFT); // Membuat nomor akun
        $initial_balance = 100000;

        // Ubah tipe parameter pada bind_param
        $stmt_balance->bind_param("isdd", $last_id, $account_number, $initial_balance, $initial_balance);

        // Eksekusi query untuk menyimpan saldo
        if ($stmt_balance->execute()) {
            echo "<p class='text-success text-center'>Registrasi berhasil! Anda dapat login sekarang.</p>";
        } else {
            echo "<p class='text-danger text-center'>Gagal menyimpan saldo: " . $stmt_balance->error . "</p>";
        }
    } else {
        echo "<p class='text-danger text-center'>Terjadi kesalahan: " . $stmt->error . "</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h2 class="text-center">Registrasi</h2>
        <form method="POST" action="">
            <div class="form-group">
                <label for="customer_name">Nama Lengkap</label>
                <input type="text" name="customer_name" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="customer_username">Username</label>
                <input type="text" name="customer_username" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="customer_pin">PIN</label>
                <input type="password" name="customer_pin" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="customer_phone">Telepon</label>
                <input type="text" name="customer_phone" class="form-control">
            </div>
            <div class="form-group">
                <label for="customer_email">Email</label>
                <input type="email" name="customer_email" class="form-control">
            </div>
            <button type="submit" class="btn btn-primary btn-block">Daftar</button>
        </form>
        <p class="text-center mt-3">Sudah punya akun? <a href="login.php">Login di sini</a></p>
    </div>
</body>
</html>
