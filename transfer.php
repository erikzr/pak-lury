<?php
include 'config.php';
session_start();

if (!isset($_SESSION['customer_id'])) {
    header("Location: login.php");
    exit();
}

$customer_id = $_SESSION['customer_id'];
$message = "";
$message_type = "info";
$minimum_balance = 25000;

function validateInput($input) {
    return htmlspecialchars(trim($input));
}

function currencyToNumber($amount) {
    $amount = str_replace('Rp ', '', $amount);
    $amount = str_replace('.', '', $amount);
    $amount = str_replace(',', '.', $amount);
    return (float) $amount;
}

function formatCurrency($amount) {
    return 'Rp ' . number_format($amount, 0, ',', '.');
}

function getServers() {
    return [
        'local' => ['name' => 'Server Lokal', 'url' => 'http://localhost/pak-lury/api_transfer.php'],
        'server2' => ['name' => 'Server 2', 'url' => 'http://192.168.9.190/pak-lury/api_transfer.php'],
    ];
}

function callTransferAPI($url, $data) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $result = curl_exec($ch);
    
    if ($result === FALSE) {
        $error = curl_error($ch);
        $errno = curl_errno($ch);
        curl_close($ch);
        error_log("Panggilan API gagal: $error (Kode error: $errno)");
        return ['status' => 'error', 'message' => "Gagal terhubung ke server: $error"];
    }
    
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code != 200) {
        error_log("API mengembalikan kode status bukan 200: $http_code");
        return ['status' => 'error', 'message' => "Server merespon dengan kode status tidak valid: $http_code"];
    }
    
    $response = json_decode($result, true);
    
    if (!is_array($response) || !isset($response['status'])) {
        error_log("Respon API tidak valid: " . print_r($result, true));
        return ['status' => 'error', 'message' => 'Respon dari API tidak valid'];
    }
    
    return $response;
}

function transferAntarServer($from_account, $to_account, $amount, $source_server, $destination_server) {
    global $conn, $customer_id;
    
    error_log("Mencoba transfer antar server: Dari $from_account ke $to_account, Jumlah: $amount, Tujuan: $destination_server");
    
    $conn->begin_transaction();

    try {
        if ($destination_server == 'local') {
            // Proses transfer lokal
            $stmt = $conn->prepare("UPDATE m_portfolio_account SET available_balance = available_balance - ? WHERE account_number = ?");
            $stmt->bind_param("ds", $amount, $from_account);
            $stmt->execute();

            $stmt = $conn->prepare("UPDATE m_portfolio_account SET available_balance = available_balance + ? WHERE account_number = ?");
            $stmt->bind_param("ds", $amount, $to_account);
            $stmt->execute();

            $stmt = $conn->prepare("INSERT INTO t_transaction (m_customer_id, transaction_type, transaction_amount, from_account_number, to_account_number, transaction_date, status, description) VALUES (?, 'TF', ?, ?, ?, NOW(), 'SUCCESS', 'Transfer lokal')");
            $stmt->bind_param("idss", $customer_id, $amount, $from_account, $to_account);
            $stmt->execute();

            $conn->commit();
            error_log("Transfer lokal berhasil");
            return ['status' => 'success', 'message' => 'Transfer lokal berhasil'];
        } else {
            $transfer_data = [
                'from_account' => $from_account,
                'to_account' => $to_account,
                'amount' => $amount,
                'source_server' => $source_server
            ];

            $servers = getServers();
            if (!isset($servers[$destination_server]) || !isset($servers[$destination_server]['url'])) {
                throw new Exception('Server tujuan tidak valid atau URL tidak ditemukan');
            }
            $api_url = $servers[$destination_server]['url'];
            $response = callTransferAPI($api_url, $transfer_data);
            if ($response['status'] == 'success') {
                $stmt = $conn->prepare("UPDATE m_portfolio_account SET available_balance = available_balance - ? WHERE account_number = ?");
                $stmt->bind_param("ds", $amount, $from_account);
                $stmt->execute();

                $stmt = $conn->prepare("INSERT INTO t_transaction (m_customer_id, transaction_type, transaction_amount, from_account_number, to_account_number, transaction_date, status, description) VALUES (?, 'TF', ?, ?, ?, NOW(), 'SUCCESS', 'Transfer antar server')");
                $stmt->bind_param("idss", $customer_id, $amount, $from_account, $to_account);
                $stmt->execute();

                $conn->commit();
                error_log("Transfer antar server berhasil");
                return ['status' => 'success', 'message' => 'Transfer antar server berhasil'];
            } else {
                throw new Exception('Gagal melakukan transfer: ' . $response['message']);
            }
        }
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Transfer gagal: " . $e->getMessage());
        return ['status' => 'error', 'message' => $e->getMessage()];
    }
}

$servers = getServers();

$stmt = $conn->prepare("SELECT account_number, account_type, available_balance FROM m_portfolio_account WHERE m_customer_id = ?");
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$accounts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $from_account_number = validateInput($_POST['from_account']);
    $to_account_number = validateInput($_POST['to_account']);
    $amount = currencyToNumber($_POST['amount']);
    $server_key = validateInput($_POST['server']);

    if ($amount <= 0) {
        $message = "Jumlah transfer harus lebih dari 0!";
        $message_type = "danger";
    } else {
        $stmt = $conn->prepare("SELECT available_balance FROM m_portfolio_account WHERE account_number = ? AND m_customer_id = ?");
        $stmt->bind_param("si", $from_account_number, $customer_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $available_balance = $row['available_balance'];
            if ($available_balance - $amount < $minimum_balance) {
                $message = "Saldo tidak mencukupi. Saldo minimal yang harus tersisa adalah " . formatCurrency($minimum_balance);
                $message_type = "danger";
            } else {
                $result = transferAntarServer($from_account_number, $to_account_number, $amount, 'local', $server_key);

                if ($result['status'] == 'success') {
                    $message = "Transfer berhasil! " . $result['message'];
                    $message_type = "success";
                    
                    $stmt->execute();
                    $accounts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                } else {
                    $message = "Gagal melakukan transfer: " . $result['message'];
                    $message_type = "danger";
                }
            }
        } else {
            $message = "Akun tidak ditemukan";
            $message_type = "danger";
        }
    }
}

$stmt = $conn->prepare("SELECT SUM(available_balance) as total_balance FROM m_portfolio_account WHERE m_customer_id = ?");
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$result = $stmt->get_result();
$total_balance = $result->fetch_assoc()['total_balance'];
?>


<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Transfer SwiftPay</title>
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
                <p class="font-bold text-lg">Transfer Uang</p>
                <p class="text-sm">Kirim uang dengan mudah dan aman</p>
            </div>
            <div class="space-x-4">
                <a href="notifications.php" class="text-white"><i class="fas fa-bell text-xl"></i></a>
                <a href="settings.php" class="text-white"><i class="fas fa-cog text-xl"></i></a>
            </div>
        </div>

        <!-- Balance Section -->
        <div class="bg-white p-4 border-b">
            <p class="text-sm text-gray-600">Total Saldo</p>
            <p class="text-2xl font-bold"><?= formatCurrency($total_balance); ?></p>
            <p class="text-xs text-gray-500">Terakhir diperbarui <?= date('d M Y H:i'); ?></p>
        </div>

        <!-- Transfer Form -->
        <div class="flex-grow p-4">
            <?php if ($message): ?>
                <div class="p-2 mb-4 text-<?= $message_type === 'danger' ? 'red' : 'green'; ?>-600 bg-<?= $message_type === 'danger' ? 'red' : 'green'; ?>-100 border border-<?= $message_type === 'danger' ? 'red' : 'green'; ?>-400 rounded">
                    <?= $message; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" class="space-y-4">
                <div>
                    <label for="from_account" class="block text-sm font-medium text-gray-700">Dari Rekening</label>
                    <select id="from_account" name="from_account" required class="mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        <option value="" disabled selected>Pilih Rekening</option>
                        <?php foreach ($accounts as $account): ?>
                            <option value="<?= htmlspecialchars($account['account_number']); ?>">
                                <?= htmlspecialchars($account['account_number']); ?> - <?= formatCurrency($account['available_balance']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="to_account" class="block text-sm font-medium text-gray-700">Ke Rekening</label>
                    <input type="text" id="to_account" name="to_account" required class="mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" placeholder="Nomor Rekening Tujuan" />
                </div>

                <div>
                    <label for="amount" class="block text-sm font-medium text-gray-700">Jumlah Transfer</label>
                    <input type="text" id="amount" name="amount" required class="mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" placeholder="Jumlah" />
                </div>

                <div>
                    <label for="server" class="block text-sm font-medium text-gray-700">Pilih Server</label>
                    <select id="server" name="server" required class="mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        <?php foreach ($servers as $key => $server): ?>
                            <option value="<?= $key; ?>"><?= $server['name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit" class="w-full p-3 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    Kirim Uang
                </button>
            </form>
        </div>

        <!-- Footer Navigation -->
        <div class="bg-white border-t flex justify-around py-2 fixed bottom-0 left-0 right-0 md:relative">
            <a href="dashboard.php" class="flex flex-col items-center no-underline text-gray-500 hover:text-blue-500">
                <i class="fas fa-home text-xl"></i>
                <p class="text-xs mt-1">Beranda</p>
            </a>
            <a href="transfer.php" class="flex flex-col items-center no-underline text-blue-500">
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
