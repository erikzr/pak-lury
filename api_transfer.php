<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', '/path/to/error.log'); // Sesuaikan path-nya

header("Access-Control-Allow-Origin: *"); // Ganti * dengan domain yang diizinkan jika perlu
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit; // Hentikan eksekusi untuk preflight request
}

header('Content-Type: application/json');

include '../config.php';

if ($conn->connect_error) {
    error_log("Koneksi database gagal: " . $conn->connect_error);
    echo json_encode(['status' => 'error', 'message' => 'Gagal terhubung ke database']);
    exit;
}

function validateInput($input) {
    return htmlspecialchars(trim($input));
}

function logTransaction($conn, $customer_id, $amount, $status, $from_account, $to_account, $transaction_type, $error_message = '') {
    $stmt = $conn->prepare("INSERT INTO t_transaction (m_customer_id, transaction_amount, status, transaction_date, from_account_number, to_account_number, transaction_type, error_message) VALUES (?, ?, ?, NOW(), ?, ?, ?, ?)");
    $stmt->bind_param("idssssss", $customer_id, $amount, $status, $from_account, $to_account, $transaction_type, $error_message);
    $stmt->execute();
}

$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    error_log("JSON Error: " . json_last_error_msg());
    echo json_encode(['status' => 'error', 'message' => 'Invalid JSON data']);
    exit;
}

if (!isset($data['customer_id']) || !is_numeric($data['customer_id']) ||
    !isset($data['from_account']) || !isset($data['to_account']) || !isset($data['amount'])) {
    echo json_encode(['status' => 'error', 'message' => 'Data transfer tidak lengkap']);
    exit;
}

$from_account = validateInput($data['from_account']);
$to_account = validateInput($data['to_account']);
$amount = floatval($data['amount']);
$customer_id = intval($data['customer_id']);

if ($amount <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Jumlah transfer tidak valid']);
    exit;
}

$conn->begin_transaction();

try {
    error_log("Memulai transaksi transfer. Dari: $from_account, Ke: $to_account, Jumlah: $amount");

    // Periksa dan kurangi saldo akun pengirim
    $stmt = $conn->prepare("SELECT available_balance FROM m_portfolio_account WHERE account_number = ? AND m_customer_id = ? FOR UPDATE");
    $stmt->bind_param("si", $from_account, $customer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $available_balance = $row['available_balance'];
        error_log("Saldo tersedia: $available_balance, Jumlah transfer: $amount");
        if ($available_balance < $amount) {
            throw new Exception("Saldo tidak mencukupi");
        }
        
        $new_balance = $available_balance - $amount;
        $update_stmt = $conn->prepare("UPDATE m_portfolio_account SET available_balance = ? WHERE account_number = ?");
        $update_stmt->bind_param("ds", $new_balance, $from_account);
        $update_stmt->execute();
        if ($update_stmt->affected_rows == 0) {
            error_log("Gagal memperbarui saldo pengirim");
            throw new Exception("Gagal memperbarui saldo pengirim");
        }
        error_log("Saldo pengirim berhasil diperbarui. Saldo baru: $new_balance");
    } else {
        throw new Exception("Akun pengirim tidak ditemukan");
    }

    // Periksa akun penerima
    $stmt = $conn->prepare("SELECT account_number FROM m_portfolio_account WHERE account_number = ?");
    $stmt->bind_param("s", $to_account);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows == 0) {
        throw new Exception("Akun penerima tidak ditemukan");
    }

    // Tambah saldo akun penerima
    $stmt = $conn->prepare("UPDATE m_portfolio_account SET available_balance = available_balance + ? WHERE account_number = ?");
    $stmt->bind_param("ds", $amount, $to_account);
    $stmt->execute();
    if ($stmt->affected_rows == 0) {
        throw new Exception("Gagal memperbarui saldo penerima");
    }
    error_log("Saldo penerima berhasil diperbarui");

    // Log transaksi sukses
    logTransaction($conn, $customer_id, $amount, 'success', $from_account, $to_account, 'TRANSFER');

    $conn->commit();
    error_log("Transaksi berhasil di-commit");
    echo json_encode(['status' => 'success', 'message' => 'Transfer berhasil']);
} catch (Exception $e) {
    $conn->rollback();
    error_log("Error during transfer: " . $e->getMessage());
    logTransaction($conn, $customer_id, $amount, 'failed', $from_account, $to_account, 'TRANSFER', $e->getMessage());
    
    $errorMessage = $e->getMessage();
    if (strpos($errorMessage, "Saldo tidak mencukupi") !== false) {
        echo json_encode(['status' => 'error', 'message' => 'Saldo tidak mencukupi']);
    } elseif (strpos($errorMessage, "tidak ditemukan") !== false) {
        echo json_encode(['status' => 'error', 'message' => 'Akun tidak ditemukan']);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Terjadi kesalahan saat melakukan transfer',
            'detail' => $errorMessage
        ]);
    }
} finally {
    $conn->close();
}
?>