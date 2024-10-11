<?php
include 'config.php'; // Pastikan Anda sudah membuat file config.php untuk koneksi database

session_start(); // Memulai sesi

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $customer_username = $_POST['customer_username'];
    $customer_pin = $_POST['customer_pin'];

    // Query untuk mengambil data customer berdasarkan username
    $stmt = $conn->prepare("SELECT id, customer_name, customer_pin FROM m_customer WHERE customer_username = ?");
    $stmt->bind_param("s", $customer_username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();

        // Verifikasi PIN menggunakan password_verify
        if (password_verify($customer_pin, $row['customer_pin'])) {
            // PIN benar, simpan ID customer di sesi dan arahkan ke dashboard
            $_SESSION['customer_id'] = $row['id'];
            header("Location: dashboard.php"); // Ganti dengan halaman dashboard Anda
            exit();
        } else {
            $error = "PIN salah. Silakan coba lagi.";
        }
    } else {
        $error = "Username tidak ditemukan.";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h2 class="text-center">Login</h2>
        <form method="POST" action="">
            <div class="form-group">
                <label for="customer_username">Username</label>
                <input type="text" name="customer_username" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="customer_pin">PIN</label>
                <input type="password" name="customer_pin" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Login</button>
        </form>
        <?php if (isset($error)) echo "<p class='text-danger text-center'>$error</p>"; ?>
        <p class="text-center mt-3">Belum punya akun? <a href="register.php">Daftar di sini</a></p>
    </div>
</body>
</html>
