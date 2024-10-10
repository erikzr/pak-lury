<?php
session_start();
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $pin = $_POST['pin'];

    // Validasi login
    $stmt = $conn->prepare("SELECT * FROM m_customer WHERE customer_username = ? AND customer_pin = ?");
    $stmt->bind_param("ss", $username, $pin);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $_SESSION['user'] = $username;
        header("Location: index.php");
    } else {
        $error = "Username atau PIN salah.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
</head>
<body>
    <h2>Login</h2>
    <?php if (isset($error)) echo "<p>$error</p>"; ?>
    <form method="POST" action="">
        Username: <input type="text" name="username" required><br>
        PIN: <input type="password" name="pin" required><br>
        <input type="submit" value="Login">
    </form>
</body>
</html>
