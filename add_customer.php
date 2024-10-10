<?php
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $username = $_POST['username'];
    $pin = $_POST['pin'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];

    // Generate nomor akun
    $account_number = uniqid('ACC'); // Contoh generate nomor akun

    // Masukkan data ke database
    $stmt = $conn->prepare("INSERT INTO m_customer (customer_name, customer_username, customer_pin, customer_phone, customer_email, registration_account_number) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $name, $username, $pin, $phone, $email, $account_number);
    if ($stmt->execute()) {
        echo "Pendaftaran berhasil! Nomor akun: $account_number";
    } else {
        echo "Error: " . $stmt->error;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Daftar</title>
</head>
<body>
    <h2>Daftar Pengguna Baru</h2>
    <form method="POST" action="">
        Nama: <input type="text" name="name" required><br>
        Username: <input type="text" name="username" required><br>
        PIN: <input type="password" name="pin" required><br>
        Telepon: <input type="text" name="phone" required><br>
        Email: <input type="email" name="email" required><br>
        <input type="submit" value="Daftar">
    </form>
</body>
</html>
