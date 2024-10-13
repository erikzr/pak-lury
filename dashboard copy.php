<?php
include 'config.php';
session_start();

// Set timezone to Indonesia (Jakarta)
date_default_timezone_set('Asia/Jakarta');

if (!isset($_SESSION['customer_id'])) {
    header("Location: login.php");
    exit();
}

$customer_id = $_SESSION['customer_id'];

$stmt = $conn->prepare("SELECT customer_name FROM m_customer WHERE id = ?");
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $customer = $result->fetch_assoc();
} else {
    header("Location: login.php");
    exit();
}

$stmt = $conn->prepare("SELECT account_number, m_customer_id, available_balance FROM m_portfolio_account WHERE m_customer_id = ?");
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$result = $stmt->get_result();

$accounts = $result->fetch_all(MYSQLI_ASSOC);

// Hitung total saldo
$total_balance = 0;
foreach ($accounts as $account) {
    $total_balance += $account['available_balance'];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Dashboard SwiftPay</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" />
    <style>
        @media (min-width: 640px) {
            .dashboard-container {
                max-width: 640px;
                margin: 0 auto;
            }
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="dashboard-container bg-white shadow-lg min-h-screen flex flex-col">
        <!-- Header -->
        <div class="bg-blue-500 text-white p-4 flex justify-between items-center">
            <div>
                <p class="font-bold text-lg">Hai, <?= htmlspecialchars($customer['customer_name']); ?></p>
                <p class="text-sm">Selamat datang, mari mulai melakukan pembayaran</p>
            </div>
            <div class="space-x-4">
                <a href="notifications.php" class="text-white"><i class="fas fa-bell text-xl"></i></a>
                <a href="settings.php" class="text-white"><i class="fas fa-cog text-xl"></i></a>
            </div>
        </div>

        <!-- Balance Section -->
        <div class="bg-white p-4 border-b">
            <p class="text-sm text-gray-600">Total Saldo</p>
            <p class="text-2xl font-bold">Rp<?= number_format($total_balance, 2, ',', '.'); ?></p>
            <p class="text-xs text-gray-500">Terakhir diperbarui <?= date('d M Y H:i'); ?></p>
        </div>

        <!-- Quick Actions -->
        <div class="flex justify-around p-6 bg-gray-50">
            <a href="transaction.php" class="flex flex-col items-center no-underline text-black">
                <div class="w-16 h-16 bg-white rounded-full flex items-center justify-center shadow-md mb-2 hover:bg-gray-100">
                    <i class="fas fa-plus text-xl"></i>
                </div>
                <p class="text-sm">Top Up Saldo</p>
            </a>
            <a href="profile.php" class="flex flex-col items-center no-underline text-black">
                <div class="w-16 h-16 bg-white rounded-full flex items-center justify-center shadow-md mb-2 hover:bg-gray-100">
                    <i class="fas fa-user text-xl"></i>
                </div>
                <p class="text-sm">Profil</p>
            </a>
            <a href="transfer.php" class="flex flex-col items-center no-underline text-black">
                <div class="w-16 h-16 bg-white rounded-full flex items-center justify-center shadow-md mb-2 hover:bg-gray-100">
                    <i class="fas fa-paper-plane text-xl"></i>
                </div>
                <p class="text-sm">Kirim Uang</p>
            </a>
        </div>

        <!-- Accounts List -->
        <div class="flex-grow p-4">
            <p class="font-bold mb-2 text-gray-700">DAFTAR AKUN</p>
            <?php foreach ($accounts as $account): ?>
            <div class="bg-white rounded-lg shadow-md p-4 mb-4">
                <p class="text-sm text-gray-600">No. Akun: <?= htmlspecialchars($account['account_number']); ?></p>
                <p class="text-lg font-semibold">Rp<?= number_format($account['available_balance'], 2, ',', '.'); ?></p>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Footer Navigation -->
        <div class="bg-white border-t flex justify-around py-2 fixed bottom-0 left-0 right-0 md:relative">
            <a href="index.php" class="flex flex-col items-center no-underline text-blue-500">
                <i class="fas fa-home text-xl"></i>
                <p class="text-xs mt-1">Beranda</p>
            </a>
            <a href="transfer.php" class="flex flex-col items-center no-underline text-gray-500 hover:text-blue-500">
                <i class="fas fa-paper-plane text-xl"></i>
                <p class="text-xs mt-1">Kirim</p>
            </a>
            <a href="cards.php" class="flex flex-col items-center no-underline text-gray-500 hover:text-blue-500">
                <i class="fas fa-credit-card text-xl"></i>
                <p class="text-xs mt-1">Kartu</p>
            </a>
            <a href="logout.php" class="flex flex-col items-center no-underline text-gray-500 hover:text-blue-500">
                <i class="fas fa-sign-out-alt text-xl"></i>
                <p class="text-xs mt-1">Logout</p>
            </a>
        </div>
    </div>
</body>
</html>