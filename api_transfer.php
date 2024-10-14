<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', '/path/to/error.log');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit;
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

// Validasi input
if (!isset($data['customer_id'], $data['amount'], $data['from_account'], $data['to_account'], $data['transaction_type'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required fields']);
    exit;
}

$customer_id = validateInput($data['customer_id']);
$amount = floatval($data['amount']);
$from_account = validateInput($data['from_account']);
$to_account = validateInput($data['to_account']);
$transaction_type = validateInput($data['transaction_type']);

// Validasi jumlah transaksi
if ($amount <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid transaction amount']);
    exit;
}

// Cek saldo rekening pengirim
$stmt = $conn->prepare("SELECT balance FROM m_account WHERE account_number = ? AND m_customer_id = ?");
$stmt->bind_param("si", $from_account, $customer_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid sender account']);
    logTransaction($conn, $customer_id, $amount, 'failed', $from_account, $to_account, $transaction_type, 'Invalid sender account');
    exit;
}

$row = $result->fetch_assoc();
$sender_balance = $row['balance'];

if ($sender_balance < $amount) {
    echo json_encode(['status' => 'error', 'message' => 'Insufficient balance']);
    logTransaction($conn, $customer_id, $amount, 'failed', $from_account, $to_account, $transaction_type, 'Insufficient balance');
    exit;
}

// Mulai transaksi
$conn->begin_transaction();

try {
    // Kurangi saldo pengirim
    $stmt = $conn->prepare("UPDATE m_account SET balance = balance - ? WHERE account_number = ?");
    $stmt->bind_param("ds", $amount, $from_account);
    $stmt->execute();

    // Tambah saldo penerima
    $stmt = $conn->prepare("UPDATE m_account SET balance = balance + ? WHERE account_number = ?");
    $stmt->bind_param("ds", $amount, $to_account);
    $stmt->execute();

    // Commit transaksi
    $conn->commit();

    // Log transaksi berhasil
    logTransaction($conn, $customer_id, $amount, 'success', $from_account, $to_account, $transaction_type);

    echo json_encode(['status' => 'success', 'message' => 'Transaction completed successfully']);
} catch (Exception $e) {
    // Rollback jika terjadi error
    $conn->rollback();
    error_log("Transaction Error: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Transaction failed']);
    logTransaction($conn, $customer_id, $amount, 'failed', $from_account, $to_account, $transaction_type, $e->getMessage());
}

$conn->close();
?>