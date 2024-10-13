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
$minimum_balance = 25000; // Minimum balance in Rupiah

// Fungsi-fungsi helper
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

// Ambil daftar server dari database atau file konfigurasi
function getServers() {
    // Implementasi sesuai dengan struktur data Anda
    return [
        'local' => ['name' => 'Server Lokal', 'url' => 'http://localhost/api_transfer.php'],
        'server2' => ['name' => 'Server 2', 'url' => 'http://server2.example.com/api_transfer.php'],
        // Tambahkan server lain sesuai kebutuhan
    ];
}

$servers = getServers();

// Ambil daftar akun customer untuk dropdown
$stmt = $conn->prepare("SELECT account_number, account_type, available_balance FROM m_portfolio_account WHERE m_customer_id = ?");
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$accounts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Proses form jika ada pengiriman data
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $from_account_number = validateInput($_POST['from_account']);
    $to_account_number = validateInput($_POST['to_account']);
    $amount = currencyToNumber($_POST['amount']);
    $server_key = validateInput($_POST['server']);

    if ($amount <= 0) {
        $message = "Jumlah transfer harus lebih dari 0!";
        $message_type = "danger";
    } else {
        // Validasi saldo
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
                // Proses transfer
                $transfer_data = [
                    'from_account' => $from_account_number,
                    'to_account' => $to_account_number,
                    'amount' => $amount,
                    'customer_id' => $customer_id
                ];

                $server_url = $servers[$server_key]['url'];
                $response = callTransferAPI($server_url, $transfer_data);

                if ($response['status'] == 'success') {
                    $message = "Transfer berhasil! " . $response['message'];
                    $message_type = "success";
                    
                    // Update saldo lokal jika transfer ke server lokal
                    if ($server_key == 'local') {
                        $new_balance = $available_balance - $amount;
                        $update_stmt = $conn->prepare("UPDATE m_portfolio_account SET available_balance = ? WHERE account_number = ?");
                        $update_stmt->bind_param("ds", $new_balance, $from_account_number);
                        $update_stmt->execute();
                    }
                    
                    // Refresh daftar akun
                    $stmt->execute();
                    $accounts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                } else {
                    $message = "Gagal melakukan transfer: " . $response['message'];
                    $message_type = "danger";
                }
            }
        } else {
            $message = "Akun tidak ditemukan";
            $message_type = "danger";
        }
    }
}

function callTransferAPI($url, $data) {
    $options = [
        'http' => [
            'header'  => "Content-type: application/json\r\n",
            'method'  => 'POST',
            'content' => json_encode($data)
        ]
    ];
    $context  = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    if ($result === FALSE) {
        return ['status' => 'error', 'message' => 'Gagal terhubung ke server'];
    }
    return json_decode($result, true);
}

// Ambil saldo total
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
    <title>Kirim ke Rekening Bank</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" />
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md mx-auto bg-white rounded-xl shadow-md overflow-hidden">
        <!-- Header -->
        <div class="bg-blue-600 text-white p-4">
            <div class="flex items-center mb-4">
                <a href="dashboard.php" class="text-white"><i class="fas fa-arrow-left text-xl"></i></a>
                <h1 class="text-center flex-grow text-xl font-semibold">Kirim ke Rekening Bank</h1>
            </div>
            <div class="text-center">
                <p class="text-sm opacity-80">Total Saldo</p>
                <p class="text-3xl font-bold"><?php echo formatCurrency($total_balance); ?></p>
                <p class="text-xs opacity-70">Terakhir diperbarui <?php echo date('d/m/Y H:i'); ?></p>
            </div>
        </div>

        <div class="p-6">
            <?php if ($message): ?>
                <div class="mb-4 p-4 rounded-lg <?php echo $message_type == 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <!-- Form -->
            <form method="POST" action="">
                <!-- Dari Akun -->
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="from_account">Dari Akun</label>
                    <select name="from_account" id="from_account" class="w-full px-3 py-2 text-gray-700 border rounded-lg focus:outline-none focus:border-blue-500" required>
                        <?php foreach ($accounts as $account): ?>
                            <option value="<?= htmlspecialchars($account['account_number']) ?>">
                                <?= htmlspecialchars($account['account_number']) ?> - 
                                <?= htmlspecialchars($account['account_type']) ?> 
                                (Saldo: <?= formatCurrency($account['available_balance']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Server Tujuan -->
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="server">Server Tujuan</label>
                    <select name="server" id="server" class="w-full px-3 py-2 text-gray-700 border rounded-lg focus:outline-none focus:border-blue-500" required>
                        <?php foreach ($servers as $key => $server): ?>
                            <option value="<?= htmlspecialchars($key) ?>"><?= htmlspecialchars($server['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Nomor Rekening -->
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="to_account">Nomor Rekening</label>
                    <input class="w-full px-3 py-2 text-gray-700 border rounded-lg focus:outline-none focus:border-blue-500" type="text" name="to_account" id="to_account" placeholder="Nomor Rekening" required />
                </div>

                <!-- Jumlah Transfer -->
                <div class="mb-6">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="amount">Jumlah Transfer</label>
                    <div class="relative">
                        <span class="absolute left-3 top-2 text-gray-600">Rp</span>
                        <input type="text" name="amount" id="amount" class="w-full pl-8 pr-3 py-2 text-gray-700 border rounded-lg focus:outline-none focus:border-blue-500" placeholder="0" required>
                    </div>
                </div>

                <!-- Continue Button -->
                <button type="submit" class="w-full bg-blue-600 text-white font-bold py-2 px-4 rounded-lg hover:bg-blue-700 focus:outline-none focus:shadow-outline">
                    Lanjutkan
                </button>
            </form>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/cleave.js/1.6.0/cleave.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            new Cleave('#amount', {
                numeral: true,
                numeralThousandsGroupStyle: 'thousand',
                numeralDecimalMark: ',',
                delimiter: '.'
            });
        });
    </script>
</body>
</html>