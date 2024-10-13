<?php
include 'config.php'; // Ensure you have created config.php for database connection

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $customer_name = $_POST['customer_name'];
    $customer_username = $_POST['customer_username'];
    $customer_pin = $_POST['customer_pin'];
    $customer_phone = $_POST['customer_phone'];
    $customer_email = $_POST['customer_email'];
    
    // Encrypt PIN before storing
    $hashed_pin = password_hash($customer_pin, PASSWORD_DEFAULT);

    // Start transaction
    $conn->begin_transaction();

    try {
        // Insert into m_customer table
        $stmt = $conn->prepare("INSERT INTO m_customer (customer_name, customer_username, customer_pin, customer_phone, customer_email, created, createdby) VALUES (?, ?, ?, ?, ?, NOW(), 1)");
        $stmt->bind_param("sssss", $customer_name, $customer_username, $hashed_pin, $customer_phone, $customer_email);
        $stmt->execute();
        $last_id = $stmt->insert_id;

        // Create accounts for different types
        $account_types = ['local', 'interbank', 'interserver'];
        foreach ($account_types as $type) {
            $account_number = "ACC" . str_pad($last_id, 5, "0", STR_PAD_LEFT) . "_" . strtoupper($type);
            $initial_balance = 100000; // Initial balance of 100,000 for each account

            $stmt_account = $conn->prepare("INSERT INTO m_portfolio_account (m_customer_id, account_number, account_name, account_type, available_balance, clear_balance, created, createdby) VALUES (?, ?, ?, ?, ?, ?, NOW(), 1)");
            $stmt_account->bind_param("isssdd", $last_id, $account_number, $customer_username, $type, $initial_balance, $initial_balance);
            $stmt_account->execute();
        }

        // Commit transaction
        $conn->commit();
        echo "<p class='text-success text-center'>Registration successful! You can now log in.</p>";
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        echo "<p class='text-danger text-center'>An error occurred: " . $e->getMessage() . "</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h2 class="text-center">Registration</h2>
        <form method="POST" action="">
            <div class="form-group">
                <label for="customer_name">Full Name</label>
                <input type="text" name="customer_name" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="customer_username">Username</label>
                <input type="text" name="customer_username" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="customer_pin">PIN</label>
                <input type="password" name="customer_pin" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="customer_phone">Phone</label>
                <input type="text" name="customer_phone" class="form-control">
            </div>
            <div class="form-group">
                <label for="customer_email">Email</label>
                <input type="email" name="customer_email" class="form-control">
            </div>
            <button type="submit" class="btn btn-primary btn-block">Register</button>
        </form>
        <p class="text-center mt-3">Already have an account? <a href="login.php">Login here</a></p>
    </div>
</body>
</html>