<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json');

include '../config.php';

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

if ($data === null) {
    echo json_encode(['status' => 'error', 'message' => 'Data JSON tidak valid']);
    exit;
}

if (!isset($data['customer_id']) || !is_numeric($data['customer_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'ID pelanggan tidak valid']);
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
    // Periksa dan kurangi saldo akun pengirim
    $stmt = $conn->prepare("SELECT available_balance FROM m_portfolio_account WHERE account_number = ? AND m_customer_id = ? FOR UPDATE");
    $stmt->bind_param("si", $from_account, $customer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $available_balance = $row['available_balance'];
        if ($available_balance < $amount) {
            throw new Exception("Saldo tidak mencukupi");
        }
        
        $new_balance = $available_balance - $amount;
        $update_stmt = $conn->prepare("UPDATE m_portfolio_account SET available_balance = ? WHERE account_number = ?");
        $update_stmt->bind_param("ds", $new_balance, $from_account);
        $update_stmt->execute();
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

    // Log transaksi sukses
    logTransaction($conn, $customer_id, $amount, 'success', $from_account, $to_account, 'TRANSFER');

    $conn->commit();
    echo json_encode(['status' => 'success', 'message' => 'Transfer berhasil']);
} catch (Exception $e) {
    $conn->rollback();
    logTransaction($conn, $customer_id, $amount, 'failed', $from_account, $to_account, 'TRANSFER', $e->getMessage());
    
    $errorMessage = $e->getMessage();
    if (strpos($errorMessage, "Saldo tidak mencukupi") !== false) {
        echo json_encode(['status' => 'error', 'message' => 'Saldo tidak mencukupi']);
    } elseif (strpos($errorMessage, "tidak ditemukan") !== false) {
        echo json_encode(['status' => 'error', 'message' => 'Akun tidak ditemukan']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Terjadi kesalahan saat melakukan transfer']);
    }
} finally {
    $conn->close();
}
?>