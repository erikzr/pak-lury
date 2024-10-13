<?php
include 'config.php';
header('Content-Type: application/json');

// Fungsi untuk memvalidasi input
function validateInput($input) {
    return htmlspecialchars(trim($input));
}

// Fungsi untuk log transaksi
function logTransaction($conn, $customer_id, $amount, $status, $from_account, $to_account, $transaction_type) {
    $stmt = $conn->prepare("INSERT INTO t_transaction (m_customer_id, transaction_amount, status, transaction_date, from_account_number, to_account_number, transaction_type) VALUES (?, ?, ?, NOW(), ?, ?, ?)");
    $stmt->bind_param("idssss", $customer_id, $amount, $status, $from_account, $to_account, $transaction_type);
    $stmt->execute();
}

// Terima data JSON
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if ($data === null) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid JSON data']);
    exit;
}

// Validasi data yang diterima
$from_account = validateInput($data['from_account']);
$to_account = validateInput($data['to_account']);
$amount = floatval($data['amount']);
$customer_id = intval($data['customer_id']);

if ($amount <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid amount']);
    exit;
}

// Mulai transaksi database
$conn->begin_transaction();

try {
    // Periksa dan kurangi saldo akun pengirim
    $stmt = $conn->prepare("SELECT available_balance FROM m_portfolio_account WHERE account_number = ? AND m_customer_id = ? FOR UPDATE");
    $stmt->bind_param("si", $from_account, $customer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $available_balance = $row['available_balance'];
        if ($available_balance < $amount) {
            throw new Exception("Insufficient balance");
        }
        
        $new_balance = $available_balance - $amount;
        $update_stmt = $conn->prepare("UPDATE m_portfolio_account SET available_balance = ? WHERE account_number = ?");
        $update_stmt->bind_param("ds", $new_balance, $from_account);
        $update_stmt->execute();
    } else {
        throw new Exception("Sender account not found");
    }

    // Tambah saldo akun penerima
    $stmt = $conn->prepare("UPDATE m_portfolio_account SET available_balance = available_balance + ? WHERE account_number = ?");
    $stmt->bind_param("ds", $amount, $to_account);
    $stmt->execute();
    
    if ($stmt->affected_rows == 0) {
        throw new Exception("Recipient account not found");
    }

    // Log transaksi
    logTransaction($conn, $customer_id, $amount, 'success', $from_account, $to_account, 'TRANSFER');

    // Commit transaksi
    $conn->commit();
    echo json_encode(['status' => 'success', 'message' => 'Transfer successful']);
} catch (Exception $e) {
    // Rollback jika terjadi error
    $conn->rollback();
    
    // Log transaksi gagal
    logTransaction($conn, $customer_id, $amount, 'failed', $from_account, $to_account, 'TRANSFER');
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
} finally {
    $conn->close();
}
?>