<?php
include 'config.php';
session_start();

if (!isset($_SESSION['customer_id'])) {
    header("Location: login.php");
    exit();
}

$customer_id = $_SESSION['customer_id'];

// Paginasi
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 10;
$start = ($page > 1) ? ($page * $perPage) - $perPage : 0;

// Filter
$dateFrom = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$dateTo = isset($_GET['date_to']) ? $_GET['date_to'] : '';
$transactionType = isset($_GET['transaction_type']) ? $_GET['transaction_type'] : '';

// Base WHERE clause
$whereClause = "WHERE (c_sender.m_customer_id = ? OR c_receiver.m_customer_id = ?)";
$params = [$customer_id, $customer_id];

if ($dateFrom && $dateTo) {
    $whereClause .= " AND t.transaction_date BETWEEN ? AND ?";
    $params[] = $dateFrom;
    $params[] = $dateTo;
}

// Add transaction type filter
if ($transactionType) {
    switch($transactionType) {
        case 'Masuk':
            $whereClause .= " AND c_receiver.m_customer_id = ? AND c_sender.m_customer_id != ?";
            $params[] = $customer_id;
            $params[] = $customer_id;
            break;
        case 'Keluar':
            $whereClause .= " AND c_sender.m_customer_id = ? AND c_receiver.m_customer_id != ?";
            $params[] = $customer_id;
            $params[] = $customer_id;
            break;
        case 'Topup':
            $whereClause .= " AND c_sender.m_customer_id = c_receiver.m_customer_id AND c_sender.m_customer_id = ?";
            $params[] = $customer_id;
            break;
    }
}

// Count total rows for pagination
$countQuery = "
    SELECT COUNT(*) as total 
    FROM t_transaction t
    JOIN m_portfolio_account c_sender ON t.from_account_number = c_sender.account_number
    JOIN m_portfolio_account c_receiver ON t.to_account_number = c_receiver.account_number
    $whereClause
";

$stmt = $conn->prepare($countQuery);
$types = str_repeat('i', count($params));
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
        sender.customer_name AS sender_name,
        receiver.customer_name AS receiver_name,
        CASE 
            WHEN c_sender.m_customer_id = c_receiver.m_customer_id THEN 'Topup'
            WHEN c_sender.m_customer_id = ? THEN 'Keluar'
            ELSE 'Masuk'
        END AS transaction_type
    FROM 
        t_transaction t
    JOIN 
        m_portfolio_account c_sender ON t.from_account_number = c_sender.account_number
    JOIN 
        m_portfolio_account c_receiver ON t.to_account_number = c_receiver.account_number
    JOIN
        m_customer sender ON c_sender.m_customer_id = sender.id
    JOIN
        m_customer receiver ON c_receiver.m_customer_id = receiver.id
    $whereClause
    ORDER BY 
        t.transaction_date DESC
    LIMIT ?, ?
";

// Add customer_id for CASE statement and pagination parameters
array_unshift($params, $customer_id);
$params[] = $start;
$params[] = $perPage;

$stmt = $conn->prepare($query);
$types = str_repeat('i', count($params));
$stmt->bind_param($types, ...$params);
$stmt->execute();
$transactions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Transaksi</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" />
    <style>
        @media (min-width: 640px) {
            .transaction-container {
                max-width: 640px;
                margin: 0 auto;
            }
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="transaction-container bg-white shadow-lg min-h-screen flex flex-col">
        <!-- Header -->
        <div class="bg-blue-500 text-white p-4">
            <h1 class="text-2xl font-bold">Riwayat Transaksi</h1>
        </div>

        <!-- Filter Form -->
        <form method="GET" class="p-4 bg-gray-50">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="date_from" class="block text-sm font-medium text-gray-700">Dari Tanggal</label>
                    <input type="date" id="date_from" name="date_from" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50" value="<?= $dateFrom ?>">
                </div>
                <div>
                    <label for="date_to" class="block text-sm font-medium text-gray-700">Sampai Tanggal</label>
                    <input type="date" id="date_to" name="date_to" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50" value="<?= $dateTo ?>">
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
                <div class="flex items-end">
                    <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                        Filter
                    </button>
                    <a href="?" class="ml-2 bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded">
                        Reset
                    </a>
                </div>
            </div>
        </form>

        <!-- Transactions List -->
        <div class="flex-grow p-4">
            <?php if (empty($transactions)): ?>
                <div class="bg-blue-100 border-l-4 border-blue-500 text-blue-700 p-4" role="alert">
                    <p class="font-bold">Informasi</p>
                    <p>Tidak ada transaksi yang ditemukan.</p>
                </div>
            <?php else: ?>
                <?php foreach ($transactions as $transaction): ?>
                    <div class="bg-white rounded-lg shadow-md p-4 mb-4">
                        <div class="flex justify-between items-center">
                            <div>
                                <p class="text-sm text-gray-600"><?= htmlspecialchars(date('d/m/Y H:i', strtotime($transaction['transaction_date']))); ?></p>
                                <p class="font-semibold">
                                    <?php
                                    switch($transaction['transaction_type']) {
                                        case 'Topup':
                                            echo "Topup Saldo";
                                            break;
                                        case 'Masuk':
                                            echo "Transfer dari " . htmlspecialchars($transaction['sender_name']);
                                            break;
                                        case 'Keluar':
                                            echo "Transfer ke " . htmlspecialchars($transaction['receiver_name']);
                                            break;
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
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Pagination -->
        <div class="p-4">
            <nav class="flex justify-center">
                <ul class="flex">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="mx-1">
                            <a class="px-3 py-2 <?= $i == $page ? 'bg-blue-500 text-white' : 'bg-white text-blue-500' ?> rounded-lg" 
                               href="?page=<?= $i ?>&date_from=<?= $dateFrom ?>&date_to=<?= $dateTo ?>&transaction_type=<?= $transactionType ?>">
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
</body>
</html>