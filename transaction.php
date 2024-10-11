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
    // Menghapus 'Rp ' jika ada
    $amount = str_replace('Rp ', '', $amount);
    // Menghapus semua titik pemisah ribuan
    $amount = str_replace('.', '', $amount);
    // Mengganti koma desimal dengan titik (jika ada)
    $amount = str_replace(',', '.', $amount);
    // Mengkonversi ke float
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
        $from_account_number = validateInput($_POST['from_account']);
        $to_account_number = validateInput($_POST['to_account']);
        $amount = currencyToNumber($_POST['amount']);

        if ($amount <= 0) {
            $message = "Jumlah transfer harus lebih dari 0!";
            $message_type = "danger";
        } else {
            // Validasi akun asal
            $stmt = $conn->prepare("SELECT id, available_balance FROM m_portfolio_account WHERE account_number = ? AND m_customer_id = ? FOR UPDATE");
            $stmt->bind_param("si", $from_account_number, $customer_id);
            $stmt->execute();
            $from_account = $stmt->get_result()->fetch_assoc();

            // Validasi akun tujuan
            $stmt = $conn->prepare("SELECT id, m_customer_id FROM m_portfolio_account WHERE account_number = ? FOR UPDATE");
            $stmt->bind_param("s", $to_account_number);
            $stmt->execute();
            $to_account = $stmt->get_result()->fetch_assoc();

            if (!$from_account) {
                $message = "Akun asal tidak ditemukan!";
                $message_type = "danger";
            } elseif (!$to_account) {
                $message = "Akun tujuan tidak ditemukan!";
                $message_type = "danger";
            } elseif ($from_account_number === $to_account_number) {
                $message = "Tidak dapat transfer ke akun yang sama!";
                $message_type = "danger";
            } elseif ($from_account['available_balance'] - $amount < $minimum_balance) {
                $message = "Saldo minimal yang harus tersisa adalah Rp " . number_format($minimum_balance, 0, ',', '.') . "!";
                $message_type = "danger";
            } else {
                $conn->begin_transaction();
                
                try {
                    // Kurangi saldo akun asal
                    $new_balance_from = $from_account['available_balance'] - $amount;
                    $stmt = $conn->prepare("UPDATE m_portfolio_account SET available_balance = ? WHERE id = ?");
                    $stmt->bind_param("di", $new_balance_from, $from_account['id']);
                    $stmt->execute();

                    // Tambah saldo akun tujuan
                    $stmt = $conn->prepare("UPDATE m_portfolio_account SET available_balance = available_balance + ? WHERE id = ?");
                    $stmt->bind_param("di", $amount, $to_account['id']);
                    $stmt->execute();

                    // Simpan data transaksi
                    $stmt = $conn->prepare("INSERT INTO t_transaction (m_customer_id, transaction_amount, status, transaction_date, from_account_number, to_account_number, transaction_type) VALUES (?, ?, ?, NOW(), ?, ?, 'TRANSFER')");
                    $status = 'success';
                    $stmt->bind_param("idsss", $customer_id, $amount, $status, $from_account_number, $to_account_number);
                    $stmt->execute();

                    $conn->commit();
                    $message = "Transfer berhasil! Saldo telah dipotong dari akun Anda.";
                    $message_type = "success";
                    
                    // Refresh daftar akun setelah transaksi
                    $stmt = $conn->prepare("SELECT account_number, account_type, available_balance FROM m_portfolio_account WHERE m_customer_id = ?");
                    $stmt->bind_param("i", $customer_id);
                    $stmt->execute();
                    $accounts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                } catch (Exception $e) {
                    $conn->rollback();
                    $message = "Terjadi kesalahan dalam proses transfer: " . $e->getMessage();
                    $message_type = "danger";
                }
            }
        }
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
        $account_number = validateInput($_POST['empty_account']);
        
        // Validasi akun
        $stmt = $conn->prepare("SELECT id, available_balance FROM m_portfolio_account WHERE account_number = ? AND m_customer_id = ? FOR UPDATE");
        $stmt->bind_param("si", $account_number, $customer_id);
        $stmt->execute();
        $account = $stmt->get_result()->fetch_assoc();

        if (!$account) {
            $message = "Akun tidak ditemukan!";
            $message_type = "danger";
        } else {
            $conn->begin_transaction();

            try {
                $amount = $account['available_balance'];
                
                // Update saldo akun menjadi 0
                $stmt = $conn->prepare("UPDATE m_portfolio_account SET available_balance = 0 WHERE id = ?");
                $stmt->bind_param("i", $account['id']);
                $stmt->execute();

                // Simpan data transaksi
                $stmt = $conn->prepare("INSERT INTO t_transaction (m_customer_id, transaction_amount, status, transaction_date, from_account_number, to_account_number, transaction_type) VALUES (?, ?, ?, NOW(), ?, ?, 'WITHDRAWAL')");
                $status = 'success';
                $system_account = 'SYSTEM';
                $stmt->bind_param("idsss", $customer_id, $amount, $status, $account_number, $system_account);
                $stmt->execute();

                $conn->commit();
                $message = "Penarikan berhasil! Saldo akun telah dikosongkan.";
                $message_type = "success";
                
                // Refresh daftar akun setelah transaksi
                $stmt = $conn->prepare("SELECT account_number, account_type, available_balance FROM m_portfolio_account WHERE m_customer_id = ?");
                $stmt->bind_param("i", $customer_id);
                $stmt->execute();
                $accounts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            } catch (Exception $e) {
                $conn->rollback();
                $message = "Terjadi kesalahan dalam proses penarikan: " . $e->getMessage();
                $message_type = "danger";
            }
        }
    }
}

// Fungsi untuk memformat angka ke format mata uang
function formatCurrency($amount) {
    return 'Rp ' . number_format($amount, 0, ',', '.');
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaksi</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cleave.js/1.6.0/cleave.min.js"></script>
    <style>
        .form-control-lg {
            font-size: 1.25rem;
        }
        .currency-input {
            position: relative;
        }
        .currency-input:before {
            position: absolute;
            top: 0;
            left: 10px;
            content: "Rp";
            display: block;
            height: 100%;
            font-size: 1.25rem;
            line-height: 45px;
        }
        .currency-input input {
            padding-left: 35px;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <h2 class="text-center mb-4">Transaksi</h2>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <ul class="nav nav-tabs" id="transactionTabs" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" id="transfer-tab" data-toggle="tab" href="#transfer" role="tab">Transfer</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="topup-tab" data-toggle="tab" href="#topup" role="tab">Top Up</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="empty-tab" data-toggle="tab" href="#empty" role="tab">Kosongkan Saldo</a>
            </li>
        </ul>

        <div class="tab-content mt-3" id="transactionTabsContent">
            <!-- Form Transfer -->
            <div class="tab-pane fade show active" id="transfer" role="tabpanel">
                <form method="POST" action="">
                    <input type="hidden" name="transaction_type" value="transfer">
                    <div class="form-group">
                        <label for="from_account">Dari Akun</label>
                        <select name="from_account" id="from_account" class="form-control form-control-lg" required>
                            <?php foreach ($accounts as $account): ?>
                                <option value="<?= htmlspecialchars($account['account_number']) ?>">
                                    <?= htmlspecialchars($account['account_number']) ?> - 
                                    <?= htmlspecialchars($account['account_type']) ?> 
                                    (Saldo: <?= formatCurrency($account['available_balance']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="to_account">Ke Akun (Nomor Akun)</label>
                        <input type="text" name="to_account" id="to_account" class="form-control form-control-lg" required>
                    </div>
                    <div class="form-group">
                        <label for="amount">Jumlah</label>
                        <div class="currency-input">
                            <input type="text" name="amount" id="amount" class="form-control form-control-lg" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary btn-lg btn-block">Kirim Transfer</button>
                </form>
            </div>

            <!-- Form Top Up -->
            <div class="tab-pane fade" id="topup" role="tabpanel">
                <form method="POST" action="">
                    <input type="hidden" name="transaction_type" value="topup">
                    <div class="form-group">
                        <label for="topup_account">Pilih Akun</label>
                        <select name="topup_account" id="topup_account" class="form-control form-control-lg" required>
                            <?php foreach ($accounts as $account): ?>
                                <option value="<?= htmlspecialchars($account['account_number']) ?>">
                                    <?= htmlspecialchars($account['account_number']) ?> - 
                                    <?= htmlspecialchars($account['account_type']) ?> 
                                    (Saldo: <?= formatCurrency($account['available_balance']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="topup_amount">Jumlah Top Up</label>
                        <div class="currency-input">
                            <input type="text" name="topup_amount" id="topup_amount" class="form-control form-control-lg" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-success btn-lg btn-block">Proses Top Up</button>
                </form>
            </div>

            <!-- Form Kosongkan Saldo -->
            <div class="tab-pane fade" id="empty" role="tabpanel">
                <form method="POST" action="" onsubmit="return confirmEmpty()">
                    <input type="hidden" name="transaction_type" value="empty">
                    <div class="form-group">
                        <label for="empty_account">Pilih Akun untuk Dikosongkan</label>
                        <select name="empty_account" id="empty_account" class="form-control form-control-lg" required>
                            <?php foreach ($accounts as $account): ?>
                                <option value="<?= htmlspecialchars($account['account_number']) ?>">
                                    <?= htmlspecialchars($account['account_number']) ?> - 
                                    <?= htmlspecialchars($account['account_type']) ?> 
                                    (Saldo: <?= formatCurrency($account['available_balance']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-danger btn-lg btn-block">Kosongkan Saldo</button>
                </form>
            </div>
        </div>

        <div class="text-center mt-4">
            <a href="dashboard.php" class="btn btn-secondary btn-lg">Kembali ke Dashboard</a>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var currencyInputs = document.querySelectorAll('#amount, #topup_amount');
            currencyInputs.forEach(function(input) {
                new Cleave(input, {
                    numeral: true,
                    numeralThousandsGroupStyle: 'thousand',
                    numeralDecimalMark: ',',
                    delimiter: '.',
                    prefix: ' ',
                    rawValueTrimPrefix: true
                });
            });
        });

        function confirmEmpty() {
            return confirm("Apakah Anda yakin ingin mengosongkan saldo akun ini? Tindakan ini tidak dapat dibatalkan.");
        }
    </script>
</body>
</html>
                                    