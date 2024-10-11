<?php
session_start();
include 'config.php';

// Cek apakah pengguna sudah login
if (!isset($_SESSION['customer_id'])) {
    header('Location: login.php'); // Redirect ke halaman login jika belum login
    exit();
}

// Ambil ID customer dari session
$customer_id = $_SESSION['customer_id'];

// Ambil data profil pengguna berdasarkan customer_id
$stmt = $conn->prepare("SELECT * FROM m_customer WHERE id = ?");
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$result = $stmt->get_result();
$customer = $result->fetch_assoc();

// Cek apakah data pengguna ditemukan
if (!$customer) {
    echo "Data profil tidak ditemukan.";
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Profil Pengguna</title>
</head>
<body>
    <h1>Profil Pengguna</h1>
    <p>Nama: <?php echo htmlspecialchars($customer['customer_name']); ?></p>
    <p>Nomor Akun: <?php echo htmlspecialchars($customer['registration_account_number']); ?></p>
    <p>Nomor Telepon: <?php echo htmlspecialchars($customer['customer_phone']); ?></p>
    <p>Email: <?php echo htmlspecialchars($customer['customer_email']); ?></p>
    <!-- Tampilkan informasi pelanggan lainnya -->
</body>
</html>
