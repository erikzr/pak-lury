<?php
include 'config.php';
header('Content-Type: application/json');

// Fungsi untuk mengirim respons JSON
function sendJsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data);
    exit;
}

// Verifikasi API key
$api_key = $_SERVER['HTTP_X_API_KEY'] ?? '';
$stmt = $conn->prepare("SELECT id FROM m_servers WHERE api_key = ?");
$stmt->bind_param("s", $api_key);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    sendJsonResponse(['error' => 'Unauthorized'], 401);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    if (!isset($data['to_account'], $data['amount'], $data['transaction_id'])) {
        sendJsonResponse(['error' => 'Invalid request'], 400);
    }

    $to_account = $data['to_account'];
    $amount = floatval($data['amount']);
    $transaction_id = $data['transaction_id'];

    // Validasi akun tujuan
    $stmt = $conn->prepare("SELECT id, m_customer_id FROM m_portfolio_account WHERE account_number = ? FOR UPDATE");
    $stmt->bind_param("s", $to_account);
    $stmt->execute();
    $account = $stmt->get_result()->fetch_assoc();

    if (!$account) {
        sendJsonResponse(['error' => 'Account not found'], 404);
    }

    $conn->begin_transaction();

    try {
        // Cek apakah transaksi sudah pernah diproses
        $stmt = $conn->prepare("SELECT id FROM t_transaction WHERE transaction_id = ?");
        $stmt->bind_param("s", $transaction_id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            sendJsonResponse(['message' => 'Transaction already processed'], 200);
        }

        // Update saldo akun tujuan
        $stmt = $conn->prepare("UPDATE m_portfolio_account SET available_balance = available_balance + ? WHERE id = ?");
        $stmt->bind_param("di", $amount, $account['id']);
        $stmt->execute();

        // Simpan data transaksi
        $stmt = $conn->prepare("INSERT INTO t_transaction (transaction_id, m_customer_id, transaction_amount, status, transaction_date, from_account_number, to_account_number, transaction_type) VALUES (?, ?, ?, ?, NOW(), ?, ?, 'TRANSFER')");
        $status = 'success';
        $from_account = 'EXTERNAL';
        $stmt->bind_param("sidssss", $transaction_id, $account['m_customer_id'], $amount, $status, $from_account, $to_account);
        $stmt->execute();

        $conn->commit();
        sendJsonResponse(['success' => true, 'message' => 'Transfer successful']);
    } catch (Exception $e) {
        $conn->rollback();
        sendJsonResponse(['error' => 'Transaction failed: ' . $e->getMessage()], 500);
    }
} else {
    sendJsonResponse(['error' => 'Method not allowed'], 405);
}