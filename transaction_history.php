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
$types = 'ii'; // for customer_id in WHERE clause

// Add date filter
if ($dateFrom && $dateTo) {
    $whereClause .= " AND t.transaction_date BETWEEN ? AND ?";
    $params[] = $dateFrom . ' 00:00:00';
    $params[] = $dateTo . ' 23:59:59';
    $types .= 'ss'; // for dateFrom and dateTo
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

// Count total rows for pagination
$countQuery = "
    SELECT COUNT(*) as total 
    FROM t_transaction t
    $whereClause
";

$stmt = $conn->prepare($countQuery);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$totalRows = $stmt->get_result()->fetch_assoc()['total'];
$totalPages = ceil($totalRows / $perPage);

// Main query for transactions
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

// Add customer_id for CASE statement and pagination parameters
array_unshift($params, $customer_id, $customer_id);
$params[] = $start;
$params[] = $perPage;
$types = 'ii' . $types . 'ii';

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$transactions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

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

        <!-- Filter Form -->
        <div class="bg-white p-4 border-b">
            <button id="filterToggle" class="w-full bg-blue-500 text-white py-2 px-4 rounded-lg flex justify-between items-center">
                <span>Filter Transaksi</span>
                <i class="fas fa-chevron-down"></i>
            </button>
            <form id="filterForm" method="GET" class="hidden mt-4 space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="date_from" class="block text-sm font-medium text-gray-700">Dari Tanggal</label>
                        <input type="date" id="date_from" name="date_from" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50" value="<?= htmlspecialchars($dateFrom) ?>">
                    </div>
                    <div>
                        <label for="date_to" class="block text-sm font-medium text-gray-700">Sampai Tanggal</label>
                        <input type="date" id="date_to" name="date_to" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50" value="<?= htmlspecialchars($dateTo) ?>">
                    </div>
                </div>
                <div>
                    <label for="transaction_type" class="block text-sm font-medium text-gray-700">Jenis Transaksi</label>
                    <select id="transaction_type" name="transaction_type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                        <option value="">Semua Jenis</option>
                        <option value="Topup" <?= $transactionType == 'Topup' ? 'selected' : '' ?>>Topup</option>
                        <option value="Masuk" <?= $transactionType == 'Masuk' ? 'selected' : '' ?>>Masuk</option>
                        <option value="Keluar" <?= $transactionType == 'Keluar' ? 'selected' : '' ?>>Keluar</option>
                    </select>
                </div>
                <div class="flex justify-between">
                    <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                        Terapkan Filter
                    </button>
                    <a href="?" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded">
                        Reset
                    </a>
                </div>
            </form>
        </div>

        <!-- Transactions List -->
        <div class="flex-grow p-4 overflow-y-auto">
            <?php if (empty($transactions)): ?>
                <div class="bg-blue-100 border-l-4 border-blue-500 text-blue-700 p-4" role="alert">
                    <p class="font-bold">Informasi</p>
                    <p>Tidak ada transaksi yang ditemukan.</p>
                </div>
            <?php else: ?>
                <?php foreach ($transactions as $transaction): ?>
                    <div class="bg-white rounded-lg shadow p-4 mb-4 flex justify-between items-center">
                        <div>
                            <p class="text-sm text-gray-600"><?= htmlspecialchars(date('d/m/Y H:i', strtotime($transaction['transaction_date']))); ?></p>
                            <p class="font-semibold">
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
                                <?= $transaction['transaction_type'] == 'Keluar' ? '-' : '+' ?><?= formatCurrency($transaction['transaction_amount']); ?>
                            </p>
                            <span class="inline-block px-2 py-1 text-xs font-semibold rounded-full <?= getTransactionBadgeClass($transaction['transaction_type']) ?>">
                                <?= htmlspecialchars($transaction['transaction_type']); ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Pagination -->
        <div class="p-4 bg-gray-50">
            <nav class="flex justify-center">
                <ul class="flex space-x-2">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li>
                            <a class="px-3 py-2 <?= $i == $page ? 'bg-blue-500 text-white' : 'bg-white text-blue-500' ?> rounded-lg shadow" 
                               href="?page=<?= $i ?>&date_from=<?= htmlspecialchars($dateFrom) ?>&date_to=<?= htmlspecialchars($dateTo) ?>&transaction_type=<?= htmlspecialchars($transactionType) ?>">
                                <?= $i ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
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
            var filterForm = document.getElementById('filterForm');
            filterForm.classList.toggle('hidden');
            this.querySelector('i').classList.toggle('fa-chevron-down');
            this.querySelector('i').classList.toggle('fa-chevron-up');
        });
    </script>
</body>
</html>

<?php
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

function formatCurrency($amount) {
    return 'Rp ' . number_format($amount, 2, ',', '.');
}
?>