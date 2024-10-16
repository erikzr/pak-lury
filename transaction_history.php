<?php
include 'config.php';
session_start();

if (!isset($_SESSION['customer_id'])) {
    header("Location: login.php");
    exit();
}

$customer_id = $_SESSION['customer_id'];

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 10;
$start = ($page > 1) ? ($page * $perPage) - $perPage : 0;

// Filter
$dateFrom = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$dateTo = isset($_GET['date_to']) ? $_GET['date_to'] : '';
$transactionType = isset($_GET['transaction_type']) ? $_GET['transaction_type'] : '';

// Base WHERE clause
$whereClause = "WHERE (t.from_account_number IN (SELECT account_number FROM m_portfolio_account WHERE m_customer_id = ?)
                OR t.to_account_number IN (SELECT account_number FROM m_portfolio_account WHERE m_customer_id = ?)
                OR t.transaction_type = 'TOPUP')";
$params = [$customer_id, $customer_id];
$types = 'ii';

// Add date filter
if ($dateFrom && $dateTo) {
    $whereClause .= " AND t.transaction_date BETWEEN ? AND ?";
    $params[] = $dateFrom . ' 00:00:00';
    $params[] = $dateTo . ' 23:59:59';
    $types .= 'ss';
}

// Add transaction type filter
if ($transactionType) {
    switch($transactionType) {
        case 'Topup':
            $whereClause .= " AND t.transaction_type = 'TOPUP'";
            break;
        case 'Masuk':
            $whereClause .= " AND t.to_account_number IN (SELECT account_number FROM m_portfolio_account WHERE m_customer_id = ?) AND t.transaction_type != 'TOPUP'";
            $params[] = $customer_id;
            $types .= 'i';
            break;
        case 'Keluar':
            $whereClause .= " AND t.from_account_number IN (SELECT account_number FROM m_portfolio_account WHERE m_customer_id = ?) AND t.transaction_type != 'TOPUP'";
            $params[] = $customer_id;
            $types .= 'i';
            break;
    }
}

// Count total rows
$countQuery = "SELECT COUNT(*) as total FROM t_transaction t $whereClause";
$stmt = $conn->prepare($countQuery);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$totalRows = $stmt->get_result()->fetch_assoc()['total'];
$totalPages = ceil($totalRows / $perPage);

// Main query
$query = "
    SELECT 
        t.transaction_date, 
        t.transaction_amount,
        t.from_account_number,
        t.to_account_number,
        t.transaction_type AS original_type,
        COALESCE(sender.customer_name, 'System') AS sender_name,
        COALESCE(receiver.customer_name, 'System') AS receiver_name,
        CASE 
            WHEN t.transaction_type = 'TOPUP' THEN 'Topup'
            WHEN t.from_account_number IN (SELECT account_number FROM m_portfolio_account WHERE m_customer_id = ?) THEN 'Keluar'
            WHEN t.to_account_number IN (SELECT account_number FROM m_portfolio_account WHERE m_customer_id = ?) THEN 'Masuk'
            ELSE 'Lainnya'
        END AS transaction_type
    FROM 
        t_transaction t
    LEFT JOIN 
        m_portfolio_account c_sender ON t.from_account_number = c_sender.account_number
    LEFT JOIN 
        m_portfolio_account c_receiver ON t.to_account_number = c_receiver.account_number
    LEFT JOIN
        m_customer sender ON c_sender.m_customer_id = sender.id
    LEFT JOIN
        m_customer receiver ON c_receiver.m_customer_id = receiver.id
    $whereClause
    ORDER BY 
        t.transaction_date DESC
    LIMIT ?, ?
";

array_unshift($params, $customer_id, $customer_id);
$params[] = $start;
$params[] = $perPage;
$types = 'ii' . $types . 'ii';

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$transactions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

function formatCurrency($amount) {
    return 'Rp ' . number_format($amount, 2, ',', '.');
}

function getTransactionBadgeClass($type) {
    switch ($type) {
        case 'Topup':
            return 'bg-green-100 text-green-800';
        case 'Masuk':
            return 'bg-blue-100 text-blue-800';
        case 'Keluar':
            return 'bg-red-100 text-red-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Transaksi SwiftPay</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" />
    <style>
        @media (min-width: 640px) {
            .dashboard-container {
                max-width: 640px;
                margin: 0 auto;
            }
        }
        .transactions-container {
            height: calc(100vh - 280px); /* Adjust this value based on your header and footer heights */
            overflow-y: auto;
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="dashboard-container bg-white shadow-lg min-h-screen flex flex-col">
        <!-- Header -->
        <div class="bg-blue-500 text-white p-4 flex justify-between items-center">
            <div>
                <h1 class="text-xl font-bold">Riwayat Transaksi</h1>
                <p class="text-sm">Pantau aktivitas keuangan Anda</p>
            </div>
            <div class="space-x-4">
                <a href="notifications.php" class="text-white"><i class="fas fa-bell text-xl"></i></a>
                <a href="settings.php" class="text-white"><i class="fas fa-cog text-xl"></i></a>
            </div>
        </div>

        <!-- Filter Section (Fixed) -->
        <div class="bg-white p-4 border-b sticky top-0 z-10">
            <button id="filterToggle" class="w-full bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded-lg flex justify-between items-center transition duration-200">
                <span>Filter Transaksi</span>
                <i class="fas fa-chevron-down"></i>
            </button>
            <form id="filterForm" method="GET" class="hidden mt-4 space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Dari Tanggal</label>
                        <input type="date" name="date_from" class="w-full p-2 border border-gray-300 rounded-lg" value="<?= htmlspecialchars($dateFrom) ?>">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Sampai Tanggal</label>
                        <input type="date" name="date_to" class="w-full p-2 border border-gray-300 rounded-lg" value="<?= htmlspecialchars($dateTo) ?>">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Jenis Transaksi</label>
                    <select name="transaction_type" class="w-full p-2 border border-gray-300 rounded-lg">
                        <option value="">Semua Jenis</option>
                        <option value="Topup" <?= $transactionType == 'Topup' ? 'selected' : '' ?>>Topup</option>
                        <option value="Masuk" <?= $transactionType == 'Masuk' ? 'selected' : '' ?>>Masuk</option>
                        <option value="Keluar" <?= $transactionType == 'Keluar' ? 'selected' : '' ?>>Keluar</option>
                    </select>
                </div>
                <div class="flex space-x-2">
                    <button type="submit" class="flex-1 bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded-lg transition duration-200">
                        Terapkan Filter
                    </button>
                    <a href="?" class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-2 px-4 rounded-lg text-center transition duration-200">
                        Reset
                    </a>
                </div>
            </form>
        </div>

        <!-- Transactions List (Scrollable) -->
        <div class="flex-grow p-4 transactions-container">
            <?php if (empty($transactions)): ?>
                <div class="bg-blue-100 border-l-4 border-blue-500 text-blue-700 p-4 rounded">
                    <p class="font-bold">Informasi</p>
                    <p>Tidak ada transaksi yang ditemukan.</p>
                </div>
            <?php else: ?>
                <div class="space-y-4">
                    <?php foreach ($transactions as $transaction): ?>
                        <div class="bg-white rounded-lg shadow p-4">
                            <div class="flex justify-between items-start">
                                <div>
                                    <p class="text-sm text-gray-500">
                                        <?= htmlspecialchars(date('d/m/Y H:i', strtotime($transaction['transaction_date']))); ?>
                                    </p>
                                    <p class="font-semibold mt-1">
                                        <?php
                                        switch($transaction['transaction_type']) {
                                            case 'Topup':
                                                echo "Topup Saldo";
                                                break;
                                            case 'Masuk':
                                                echo "Dari: " . htmlspecialchars($transaction['sender_name']);
                                                break;
                                            case 'Keluar':
                                                echo "Ke: " . htmlspecialchars($transaction['receiver_name']);
                                                break;
                                            default:
                                                echo "Transaksi Lainnya";
                                        }
                                        ?>
                                    </p>
                                </div>
                                <div class="text-right">
                                    <p class="text-lg font-bold <?= $transaction['transaction_type'] == 'Keluar' ? 'text-red-500' : 'text-green-500' ?>">
                                        <?= $transaction['transaction_type'] == 'Keluar' ? '-' : '+' ?>
                                        <?= formatCurrency($transaction['transaction_amount']); ?>
                                    </p>
                                    <span class="inline-block px-2 py-1 text-xs font-semibold rounded-full mt-1 <?= getTransactionBadgeClass($transaction['transaction_type']) ?>">
                                        <?= htmlspecialchars($transaction['transaction_type']); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <div class="bg-white p-4 border-t">
                <div class="flex justify-center space-x-2">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <a href="?page=<?= $i ?>&date_from=<?= htmlspecialchars($dateFrom) ?>&date_to=<?= htmlspecialchars($dateTo) ?>&transaction_type=<?= htmlspecialchars($transactionType) ?>" 
                           class="px-3 py-2 rounded <?= $i == $page ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Footer Navigation -->
        <div class="bg-white border-t flex justify-around py-2 sticky bottom-0 left-0 right-0">
            <a href="dashboard.php" class="flex flex-col items-center no-underline text-gray-500 hover:text-blue-500">
                <i class="fas fa-home text-xl"></i>
                <p class="text-xs mt-1">Beranda</p>
            </a>
            <a href="transfer.php" class="flex flex-col items-center no-underline text-gray-500 hover:text-blue-500">
                <i class="fas fa-paper-plane text-xl"></i>
                <p class="text-xs mt-1">Kirim</p>
            </a>
            <a href="transaction_history.php" class="flex flex-col items-center no-underline text-blue-500">
                <i class="fas fa-history text-xl"></i>
                <p class="text-xs mt-1">Riwayat</p>
            </a>
            <a href="logout.php" class="flex flex-col items-center no-underline text-gray-500 hover:text-blue-500">
                <i class="fas fa-sign-out-alt text-xl"></i>
                <p class="text-xs mt-1">Logout</p>
            </a>
        </div>
    </div>

    <script>
        document.getElementById('filterToggle').addEventListener('click', function() {
            const filterForm = document.getElementById('filterForm');
            const icon = this.querySelector('i');
            
            filterForm.classList.toggle('hidden');
            icon.classList.toggle('fa-chevron-down');
            icon.classList.toggle('fa-chevron-up');
        });

        const dateFromInput = document.querySelector('input[name="date_from"]');
        const dateToInput = document.querySelector('input[name="date_to"]');

        function validateDateRange() {
            const dateFrom = new Date(dateFromInput.value);
            const dateTo = new Date(dateToInput.value);

            if (dateFrom > dateTo) {
                alert('Tanggal awal tidak boleh lebih besar dari tanggal akhir');
                dateToInput.value = dateFromInput.value;
            }
        }

        dateFromInput.addEventListener('change', validateDateRange);
        dateToInput.addEventListener('change', validateDateRange);
    </script>
</body>
</html>