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
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .transaction-badge {
            font-size: 0.8rem;
            padding: 0.4em 0.6em;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <h1 class="text-center">Riwayat Transaksi</h1>

        <form method="GET" class="mb-4">
            <div class="form-row">
                <div class="col">
                    <input type="date" name="date_from" class="form-control" value="<?= $dateFrom ?>" placeholder="Dari Tanggal">
                </div>
                <div class="col">
                    <input type="date" name="date_to" class="form-control" value="<?= $dateTo ?>" placeholder="Sampai Tanggal">
                </div>
                <div class="col">
                    <select name="transaction_type" class="form-control">
                        <option value="">Semua Jenis</option>
                        <option value="Topup" <?= $transactionType == 'Topup' ? 'selected' : '' ?>>Topup</option>
                        <option value="Masuk" <?= $transactionType == 'Masuk' ? 'selected' : '' ?>>Masuk</option>
                        <option value="Keluar" <?= $transactionType == 'Keluar' ? 'selected' : '' ?>>Keluar</option>
                    </select>
                </div>
                <div class="col">
                    <button type="submit" class="btn btn-primary">Filter</button>
                    <a href="?" class="btn btn-secondary">Reset</a>
                </div>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Jenis</th>
                        <th>Dari Akun</th>
                        <th>Ke Akun</th>
                        <th>Nominal</th>
                        <th>Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($transactions as $transaction): ?>
                        <tr>
                            <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime($transaction['transaction_date']))); ?></td>
                            <td>
                                <span class="badge transaction-badge <?= getTransactionBadgeClass($transaction['transaction_type']) ?>">
                                    <?= htmlspecialchars($transaction['transaction_type']); ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($transaction['from_account_number']); ?></td>
                            <td><?= htmlspecialchars($transaction['to_account_number']); ?></td>
                            <td><?= formatCurrency($transaction['transaction_amount']); ?></td>
                            <td>
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
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if (empty($transactions)): ?>
            <div class="alert alert-info text-center">
                Tidak ada transaksi yang ditemukan.
            </div>
        <?php endif; ?>

        <nav>
            <ul class="pagination justify-content-center">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $i ?>&date_from=<?= $dateFrom ?>&date_to=<?= $dateTo ?>&transaction_type=<?= $transactionType ?>">
                            <?= $i ?>
                        </a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>

        <div class="text-center mt-3">
            <a href="dashboard.php" class="btn btn-secondary">Kembali ke Dashboard</a>
        </div>
    </div>

    <?php
    function getTransactionBadgeClass($type) {
        switch ($type) {
            case 'Topup':
                return 'badge-success';
            case 'Masuk':
                return 'badge-primary';
            case 'Keluar':
                return 'badge-warning';
            default:
                return 'badge-secondary';
        }
    }

    function formatCurrency($amount) {
        return 'Rp ' . number_format($amount, 2, ',', '.');
    }
    ?>
</body>
</html>