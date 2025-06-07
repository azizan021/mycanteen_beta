<?php
// login.php
session_start();
include 'db_connect.php';

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $user_type = $_POST['user_type'] ?? ''; // 'customer' or 'courier'

    if (empty($username) || empty($password) || empty($user_type)) {
        $error_message = 'Username, password, dan tipe pengguna harus diisi.';
    } else {
        if ($user_type === 'customer') {
            $sql = "SELECT id, username, password, name FROM customers WHERE username = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();
                if (password_verify($password, $user['password'])) {
                    $_SESSION['customer_logged_in'] = true;
                    $_SESSION['customer_id'] = $user['id'];
                    $_SESSION['customer_username'] = $user['username'];
                    $_SESSION['customer_name'] = $user['name'];
                    header("Location: customer_dashboard.php"); // Arahkan ke dashboard pelanggan
                    exit();
                } else {
                    $error_message = 'Username atau password salah untuk pelanggan.';
                }
            } else {
                $error_message = 'Username atau password salah untuk pelanggan.';
            }
            $stmt->close();

        } elseif ($user_type === 'courier') {
            $sql = "SELECT id, username, password, name FROM couriers WHERE username = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();
                if (password_verify($password, $user['password'])) {
                    $_SESSION['courier_logged_in'] = true;
                    $_SESSION['courier_id'] = $user['id'];
                    $_SESSION['courier_username'] = $user['username'];
                    $_SESSION['courier_name'] = $user['name'];
                    header("Location: courier_dashboard.php"); // Arahkan ke dashboard kurir
                    exit();
                } else {
                    $error_message = 'Username atau password salah untuk kurir.';
                }
            } else {
                $error_message = 'Username atau password salah untuk kurir.';
            }
            $stmt->close();
        } else {
            $error_message = 'Tipe pengguna tidak valid.';
        }
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Mycanteen</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f0f2f5;
        }
        .form-input {
            width: 100%;
            padding: 0.75rem;
            border-radius: 0.375rem; /* rounded-md */
            border: 1px solid #d1d5db; /* border-gray-300 */
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05); /* shadow-sm */
            font-size: 1rem;
            line-height: 1.5;
            transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        }
        .form-input:focus {
            border-color: #3b82f6; /* focus:border-blue-500 */
            outline: 0;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.25); /* focus:ring focus:ring-blue-200 */
        }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen">
    <div class="bg-white rounded-lg shadow-md p-8 max-w-md w-full">
        <h1 class="text-3xl font-bold text-gray-900 text-center mb-6">Login ke Mycanteen</h1>

        <?php if ($error_message): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline"><?php echo htmlspecialchars($error_message); ?></span>
            </div>
        <?php endif; ?>

        <form action="login.php" method="post">
            <div class="mb-4">
                <label for="username" class="block text-gray-700 text-sm font-medium mb-2">Username:</label>
                <input type="text" id="username" name="username" class="form-input" required>
            </div>
            <div class="mb-6">
                <label for="password" class="block text-gray-700 text-sm font-medium mb-2">Password:</label>
                <input type="password" id="password" name="password" class="form-input" required>
            </div>
            <div class="mb-6">
                <label class="block text-gray-700 text-sm font-medium mb-2">Login sebagai:</label>
                <div class="flex items-center space-x-4">
                    <label class="inline-flex items-center">
                        <input type="radio" class="form-radio text-blue-600" name="user_type" value="customer" required>
                        <span class="ml-2 text-gray-700">Pelanggan</span>
                    </label>
                    <label class="inline-flex items-center">
                        <input type="radio" class="form-radio text-blue-600" name="user_type" value="courier" required>
                        <span class="ml-2 text-gray-700">Kurir</span>
                    </label>
                </div>
            </div>
            <button type="submit" class="w-full bg-blue-600 text-white py-3 px-4 rounded-full font-bold text-lg hover:bg-blue-700 transition-colors duration-200">
                Login
            </button>
        </form>
        <p class="mt-4 text-center text-gray-600">
            Belum punya akun?
            <a href="customer_register.php" class="text-blue-500 hover:underline">Daftar sebagai Pelanggan</a>
            atau
            <a href="courier_register.php" class="text-blue-500 hover:underline">Daftar sebagai Kurir</a>
        </p>
    </div>
</body>
</html>