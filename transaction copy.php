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

// Fungsi untuk memvalidasi input
function validateInput($input) {
    return htmlspecialchars(trim($input));
}

// Fungsi untuk mengkonversi format mata uang ke angka
function currencyToNumber($amount) {
    $amount = str_replace('Rp ', '', $amount);
    $amount = str_replace('.', '', $amount);
    $amount = str_replace(',', '.', $amount);
    return (float) $amount;
}

// Ambil daftar akun customer untuk dropdown
$stmt = $conn->prepare("SELECT account_number, account_type, available_balance FROM m_portfolio_account WHERE m_customer_id = ?");
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$accounts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Proses form jika ada pengiriman data
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $transaction_type = validateInput($_POST['transaction_type']);
    
    if ($transaction_type == 'transfer') {
        // Logika transfer (tidak diubah)
    } elseif ($transaction_type == 'topup') {
        $to_account_number = validateInput($_POST['topup_account']);
        $amount = currencyToNumber($_POST['topup_amount']);

        if ($amount <= 0) {
            $message = "Jumlah topup harus lebih dari 0!";
            $message_type = "danger";
        } else {
            // Validasi akun
            $stmt = $conn->prepare("SELECT id FROM m_portfolio_account WHERE account_number = ? AND m_customer_id = ? FOR UPDATE");
            $stmt->bind_param("si", $to_account_number, $customer_id);
            $stmt->execute();
            $account = $stmt->get_result()->fetch_assoc();

            if (!$account) {
                $message = "Akun tidak ditemukan!";
                $message_type = "danger";
            } else {
                $conn->begin_transaction();

                try {
                    // Update saldo akun
                    $stmt = $conn->prepare("UPDATE m_portfolio_account SET available_balance = available_balance + ? WHERE id = ?");
                    $stmt->bind_param("di", $amount, $account['id']);
                    $stmt->execute();

                    // Simpan data transaksi
                    $stmt = $conn->prepare("INSERT INTO t_transaction (m_customer_id, transaction_amount, status, transaction_date, from_account_number, to_account_number, transaction_type) VALUES (?, ?, ?, NOW(), ?, ?, 'TOPUP')");
                    $status = 'success';
                    $system_account = 'SYSTEM';
                    $stmt->bind_param("idsss", $customer_id, $amount, $status, $system_account, $to_account_number);
                    $stmt->execute();

                    $conn->commit();
                    $message = "Topup berhasil! Saldo telah ditambahkan ke akun Anda.";
                    $message_type = "success";
                    
                    // Refresh daftar akun setelah transaksi
                    $stmt = $conn->prepare("SELECT account_number, account_type, available_balance FROM m_portfolio_account WHERE m_customer_id = ?");
                    $stmt->bind_param("i", $customer_id);
                    $stmt->execute();
                    $accounts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                } catch (Exception $e) {
                    $conn->rollback();
                    $message = "Terjadi kesalahan dalam proses topup: " . $e->getMessage();
                    $message_type = "danger";
                }
            }
        }
    } elseif ($transaction_type == 'empty') {
        // Logika kosongkan saldo (tidak diubah)
    }
}

// Fungsi untuk memformat angka ke format mata uang
function formatCurrency($amount) {
    return 'Rp ' . number_format($amount, 0, ',', '.');
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
    <title>Top Up Saldo</title>
    <link rel="stylesheet" href="topup.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" />
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 h-screen flex items-center justify-center">
    <div class="w-full max-w-md mx-auto bg-white p-6 rounded-lg shadow-md">
        <!-- Header -->
        <div class="flex items-center mb-4">
            <a href="dashboard.php" class="text-gray-600"><i class="fas fa-arrow-left text-xl"></i></a>
            <h1 class="text-center flex-grow text-xl font-semibold">Top Up Saldo</h1>
        </div>

        <!-- Balance Info -->
        <div class="bg-gray-800 text-white p-4 rounded-lg mb-6">
            <p class="text-sm">Saldo Tersedia</p>
            <p class="text-3xl font-bold"><?php echo formatCurrency($total_balance); ?></p>
            <p class="text-xs">Terakhir diperbarui <?php echo date('d/m/Y H:i'); ?></p>
        </div>

        <?php if ($message): ?>
            <div class="mb-4 p-4 rounded-lg <?php echo $message_type == 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- Form -->
        <form method="POST" action="">
            <input type="hidden" name="transaction_type" value="topup">
            
            <!-- Nomor Rekening -->
            <div class="mb-4">
                <label class="block text-gray-700 text-sm mb-2" for="topup_account">Nomor Rekening</label>
                <select name="topup_account" id="topup_account" class="w-full p-2 border rounded-md">
                    <?php foreach ($accounts as $account): ?>
                        <option value="<?= htmlspecialchars($account['account_number']) ?>">
                            <?= htmlspecialchars($account['account_number']) ?> - 
                            <?= htmlspecialchars($account['account_type']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Tipe Akun -->
            <div class="mb-4">
                <label class="block text-gray-700 text-sm mb-2" for="account_type">Tipe Akun</label>
                <div class="relative">
                    <select id="account_type" class="w-full p-2 border rounded-md appearance-none">
                        <option>Pilih Tipe Akun</option>
                        <?php foreach ($accounts as $account): ?>
                            <option><?= htmlspecialchars($account['account_type']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <i class="fas fa-chevron-down absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500"></i>
                </div>
            </div>

            <!-- Jumlah Top Up -->
            <div class="mb-6">
                <label class="block text-gray-700 text-sm mb-2" for="topup_amount">Jumlah Top Up</label>
                <div class="relative">
                    <span class="absolute left-3 top-2 text-gray-600">Rp</span>
                    <input type="text" name="topup_amount" id="topup_amount" class="w-full p-2 pl-8 border rounded-md" placeholder="0" required>
                </div>
            </div>

            <!-- Continue Button -->
            <button type="submit" class="w-full bg-blue-500 text-white p-2 rounded-md hover:bg-blue-600">Continue Top Up</button>
        </form>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/cleave.js/1.6.0/cleave.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            new Cleave('#topup_amount', {
                numeral: true,
                numeralThousandsGroupStyle: 'thousand',
                numeralDecimalMark: ',',
                delimiter: '.'
            });
        });
    </script>
</body>
</html>