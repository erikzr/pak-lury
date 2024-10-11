<?php
session_start();
include 'config.php'; // Koneksi ke database MySQL

// Fungsi untuk mengirim transfer ke server lain
function sendToOtherServer($to_account, $amount) {
    $data = [
        'to_account' => $to_account,
        'amount' => $amount
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "http://serverlain.com/api_receive_transfer.php"); // URL server penerima
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    curl_close($ch);

    $result = json_decode($response, true);
    return $result;
}

// Proses transfer
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $from_account = $_POST['from_account']; // ID akun pengirim
    $to_account = $_POST['to_account']; // ID akun penerima
    $amount = $_POST['amount']; // Jumlah transfer

    // Validasi apakah akun pengirim memiliki saldo yang cukup
    $query = "SELECT clear_balance FROM m_portfolio_account WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, 'i', $from_account);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $account_data = mysqli_fetch_assoc($result);

    if (!$account_data || $account_data['clear_balance'] < $amount) {
        echo "Saldo tidak mencukupi atau akun tidak valid.";
        exit();
    }

    // Cek apakah penerima berada di server lokal atau server lain
    $is_local_account = true; // Misalnya, Anda bisa membuat fungsi untuk mengecek apakah akun ada di database lokal

    // Jika akun penerima ada di server lain
    if (!$is_local_account) {
        $transfer_result = sendToOtherServer($to_account, $amount);

        if ($transfer_result['status'] == 'success') {
            // Kurangi saldo dari akun pengirim
            mysqli_begin_transaction($conn);
            try {
                // Insert data transaksi
                $query = "INSERT INTO t_transaction (m_customer_id, transaction_type, transaction_amount, from_account_number, to_account_number, transaction_date, status) 
                          VALUES (?, 'transfer', ?, ?, ?, NOW(), 'completed')";
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, 'idss', $from_account, $amount, $from_account, $to_account);
                mysqli_stmt_execute($stmt);

                // Kurangi saldo pengirim
                $query = "UPDATE m_portfolio_account SET clear_balance = clear_balance - ? WHERE id = ?";
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, 'di', $amount, $from_account);
                mysqli_stmt_execute($stmt);

                // Commit transaksi
                mysqli_commit($conn);
                echo "Transfer berhasil ke akun di server lain!";
            } catch (Exception $e) {
                mysqli_rollback($conn);
                echo "Transfer gagal: " . $e->getMessage();
            }
        } else {
            echo "Transfer gagal: " . $transfer_result['message'];
        }

    } else {
        // Transfer ke akun lokal
        mysqli_begin_transaction($conn);
        try {
            // Insert data transaksi
            $query = "INSERT INTO t_transaction (m_customer_id, transaction_type, transaction_amount, from_account_number, to_account_number, transaction_date, status) 
                      VALUES (?, 'transfer', ?, ?, ?, NOW(), 'completed')";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, 'idss', $from_account, $amount, $from_account, $to_account);
            mysqli_stmt_execute($stmt);

            // Kurangi saldo dari akun pengirim
            $query = "UPDATE m_portfolio_account SET clear_balance = clear_balance - ? WHERE id = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, 'di', $amount, $from_account);
            mysqli_stmt_execute($stmt);

            // Tambah saldo ke akun penerima
            $query = "UPDATE m_portfolio_account SET clear_balance = clear_balance + ? WHERE id = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, 'di', $amount, $to_account);
            mysqli_stmt_execute($stmt);

            // Commit transaksi
            mysqli_commit($conn);
            echo "Transfer berhasil!";
        } catch (Exception $e) {
            // Rollback jika ada kesalahan
            mysqli_rollback($conn);
            echo "Transfer gagal: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Transfer Antar Akun</title>
</head>
<body>
    <h1>Transfer Antar Akun</h1>
    <form method="POST" action="">
        Dari Akun: <input type="text" name="from_account" required><br>
        Ke Akun: <input type="text" name="to_account" required><br>
        Jumlah: <input type="number" name="amount" required><br>
        <input type="submit" value="Kirim">
    </form>
</body>
</html>
