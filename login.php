<?php
include 'config.php';
session_start();

// Fungsi untuk membatasi percobaan login
function checkLoginAttempts($username) {
    if (!isset($_SESSION['login_attempts'][$username])) {
        $_SESSION['login_attempts'][$username] = 1;
    } else {
        $_SESSION['login_attempts'][$username]++;
    }
    
    if ($_SESSION['login_attempts'][$username] > 3) {
        return false;
    }
    return true;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validasi token CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('CSRF token validation failed');
    }

    $customer_username = filter_input(INPUT_POST, 'customer_username', FILTER_SANITIZE_STRING);
    $customer_pin = $_POST['customer_pin'];

    if (checkLoginAttempts($customer_username)) {
        $stmt = $conn->prepare("SELECT id, customer_name, customer_pin FROM m_customer WHERE customer_username = ?");
        $stmt->bind_param("s", $customer_username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            if (password_verify($customer_pin, $row['customer_pin'])) {
                $_SESSION['customer_id'] = $row['id'];
                unset($_SESSION['login_attempts'][$customer_username]);
                header("Location: dashboard.php");
                exit();
            } else {
                $error = "PIN salah. Silakan coba lagi.";
            }
        } else {
            $error = "Username tidak ditemukan.";
        }
    } else {
        $error = "Terlalu banyak percobaan login. Silakan coba lagi nanti.";
    }
}

// Generate CSRF token
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Swift Pay Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="style/stylelogin.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" />
</head>
<body class="bg-white h-screen flex items-center justify-center">
    <div class="w-full max-w-md mx-auto p-6 bg-white shadow-lg rounded-lg flex flex-col h-full justify-between">
        <div>
            <div class="flex items-center mb-6">
                <i class="fas fa-arrow-left text-xl"></i>
            </div>
            <h1 class="text-2xl font-bold mb-6 text-center">Login to your Swift Pay account</h1>
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <div class="mb-4">
                    <label for="customer_username">Username</label>
                    <input class="input-field" name="customer_username" type="text" placeholder="Username" required />
                </div>
                <div class="mb-4 relative">
                    <label for="customer_pin">Password</label>
                    <input class="input-field" name="customer_pin" type="password" placeholder="Password" required autocomplete="off" id="password" />
                    <i class="fas fa-eye absolute right-3 top-10 text-gray-500 cursor-pointer" id="togglePassword"></i>
                </div>
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center">
                        <input class="mr-2 leading-tight" type="checkbox" id="rememberMe" />
                        <label class="text-sm" for="rememberMe"> Remember Me </label>
                    </div>
                    <a href="forgot-password.php" class="text-blue-500 text-sm">Lupa Password?</a>
                </div>
                <?php if (isset($error)) echo "<p class='text-red-500 text-center mb-4'>$error</p>"; ?>
                <div class="mt-6">
                    <button type="submit" class="login-btn">Login</button>
                </div>
            </form>
            <p class="text-center mt-6">Belum punya akun? <a href="register.php" class="text-blue-500">Daftar di sini</a></p>
        </div>
    </div>
    <script>
        document.getElementById('togglePassword').addEventListener('click', function() {
            const password = document.getElementById('password');
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            this.classList.toggle('fa-eye-slash');
        });
    </script>
</body>
</html>