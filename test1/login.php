<?php
session_start();
include 'config.php'; // Koneksi ke database

$error_message = ""; // Inisialisasi variabel untuk menyimpan pesan kesalahan

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $pin = $_POST['pin'];

    // Query untuk cek apakah username ada
    $stmt = $conn->prepare("SELECT id, customer_pin FROM m_customer WHERE customer_username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // Memeriksa PIN
        if ($user['customer_pin'] === $pin) {
            // Jika login berhasil, simpan ID pengguna ke dalam session
            $_SESSION['customer_id'] = $user['id'];
            header("Location: index.php"); // Arahkan ke halaman index setelah login
            exit();
        } else {
            $error_message = "PIN salah.";
        }
    } else {
        $error_message = "Username tidak ditemukan.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
</head>
<body>
    <h2>Login</h2>
    <?php if ($error_message): ?>
        <p style="color: red;"><?php echo $error_message; ?></p>
    <?php endif; ?>
    <form method="POST" action="">
        Username: <input type="text" name="username" required><br>
        PIN: <input type="password" name="pin" required><br>
        <input type="submit" value="Login">
    </form>
</body>
</html>
