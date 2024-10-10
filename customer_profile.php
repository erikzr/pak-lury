<?php
session_start();
include 'config.php';

$username = $_SESSION['user'];

// Ambil data profil pengguna
$stmt = $conn->prepare("SELECT * FROM m_customer WHERE customer_username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
$customer = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Profil Pengguna</title>
</head>
<body>
    <h1>Profil Pengguna</h1>
    <p>Nama: <?php echo $customer['customer_name']; ?></p>
    <p>Nomor Akun: <?php echo $customer['registration_account_number']; ?></p>
    <p>Nomor Telepon: <?php echo $customer['customer_phone']; ?></p>
    <p>Email: <?php echo $customer['customer_email']; ?></p>
    <!-- Tampilkan informasi pelanggan lainnya -->
</body>
</html>
