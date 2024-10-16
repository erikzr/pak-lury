<?php
include 'config.php';
session_start();

if (!isset($_SESSION['customer_id'])) {
    header("Location: login.php");
    exit();
}

$customer_id = $_SESSION['customer_id'];

$stmt = $conn->prepare("SELECT customer_name, customer_phone, customer_email FROM m_customer WHERE id = ?");
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $customer = $result->fetch_assoc();
} else {
    header("Location: login.php");
    exit();
}

$stmt = $conn->prepare("SELECT account_number FROM m_portfolio_account WHERE m_customer_id = ? LIMIT 1");
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$result = $stmt->get_result();
$account = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Profil SwiftPay</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" />
    <style>
        @media (min-width: 640px) {
            .profile-container {
                max-width: 640px;
                margin: 0 auto;
            }
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="profile-container bg-white shadow-lg min-h-screen flex flex-col">
        <!-- Header -->
        <div class="bg-blue-500 text-white p-4 flex justify-between items-center">
            <div>
                <p class="font-bold text-lg">Profil</p>
                <p class="text-sm">Informasi akun Anda</p>
            </div>
            <div>
                <a href="dashboard.php" class="text-white"><i class="fas fa-arrow-left text-xl"></i></a>
            </div>
        </div>

        <!-- Profile Section -->
        <div class="bg-white p-6 border-b flex items-center">
            <div class="w-20 h-20 bg-gray-300 rounded-full flex items-center justify-center mr-4">
                <i class="fas fa-user text-4xl text-gray-600"></i>
            </div>
            <div>
                <p class="text-xl font-bold"><?= htmlspecialchars($customer['customer_name']); ?></p>
                <p class="text-sm text-gray-600">No. Akun: <?= htmlspecialchars($account['account_number']); ?></p>
            </div>
        </div>

        <!-- Profile Information -->
        <div class="flex-grow p-4">
            <div class="bg-white rounded-lg shadow-md p-4 mb-4">
                <p class="font-bold mb-2 text-gray-700">Informasi Kontak</p>
                <hr>
                <div class="mb-2">
                    <p class="text-sm text-gray-600">No. Handphone</p>
                    <p class="text-lg"><?= htmlspecialchars($customer['customer_phone']); ?></p>
                </div>
                <hr>
                <div>
                    <p class="text-sm text-gray-600">Alamat Email</p>
                    <p class="text-lg"><?= htmlspecialchars($customer['customer_email']); ?></p>
                </div>
                <br>
                <p class="font-bold mb-2 text-gray-700">Informasi Aplikasi</p>
                <hr>
                <div class="mb-2">
                    <p class="text-sm text-gray-600">Perangkat Terhubung</p>
                    <p class="text-lg">iPhone 12, MacBook Pro</p>
                </div>
                <hr>
                <div>
                    <p class="text-sm text-gray-600">Versi Aplikasi</p>
                    <p class="text-lg">v1.5.2</p>
                </div>
            </div>
        </div>

        <!-- Footer Navigation -->
        <div class="bg-white border-t flex justify-around py-2 fixed bottom-0 left-0 right-0 md:relative">
            <a href="dashboard.php" class="flex flex-col items-center no-underline text-gray-500 hover:text-blue-500">
                <i class="fas fa-home text-xl"></i>
                <p class="text-xs mt-1">Beranda</p>
            </a>
            <a href="transfer.php" class="flex flex-col items-center no-underline text-gray-500 hover:text-blue-500">
                <i class="fas fa-paper-plane text-xl"></i>
                <p class="text-xs mt-1">Kirim</p>
            </a>
            <a href="transaction_history.php" class="flex flex-col items-center no-underline text-gray-500 hover:text-blue-500">
                <i class="fas fa-history text-xl"></i>
                <p class="text-xs mt-1">Riwayat</p>
            </a>
            <a href="logout.php" class="flex flex-col items-center no-underline text-gray-500 hover:text-blue-500">
                <i class="fas fa-sign-out-alt text-xl"></i>
                <p class="text-xs mt-1">Logout</p>
            </a>
        </div>
    </div>
</body>
</html>