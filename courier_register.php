<?php
// courier_register.php
session_start();
include 'db_connect.php';

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    // Basic validation
    if (empty($username) || empty($password) || empty($confirm_password) || empty($name) || empty($phone)) {
        $error_message = 'Semua field harus diisi.';
    } elseif ($password !== $confirm_password) {
        $error_message = 'Konfirmasi password tidak cocok.';
    } elseif (strlen($password) < 6) {
        $error_message = 'Password harus minimal 6 karakter.';
    } elseif (!preg_match('/^\d{10,15}$/', $phone)) { // Basic phone number validation
        $error_message = 'Format nomor telepon tidak valid.';
    } else {
        // Check if username or phone already exists
        $sql_check = "SELECT id FROM couriers WHERE username = ? OR phone = ?";
        $stmt_check = $conn->prepare($sql_check);
        $stmt_check->bind_param("ss", $username, $phone);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();

        if ($result_check->num_rows > 0) {
            $existing_courier = $result_check->fetch_assoc();
            if ($existing_courier) {
                // Determine if username or phone already exists
                $sql_check_username = "SELECT id FROM couriers WHERE username = ?";
                $stmt_check_username = $conn->prepare($sql_check_username);
                $stmt_check_username->bind_param("s", $username);
                $stmt_check_username->execute();
                $result_check_username = $stmt_check_username->get_result();

                $sql_check_phone = "SELECT id FROM couriers WHERE phone = ?";
                $stmt_check_phone = $conn->prepare($sql_check_phone);
                $stmt_check_phone->bind_param("s", $phone);
                $stmt_check_phone->execute();
                $result_check_phone = $stmt_check_phone->get_result();


                if ($result_check_username->num_rows > 0 && $result_check_phone->num_rows > 0) {
                    $error_message = 'Username dan Nomor Telepon sudah terdaftar.';
                } elseif ($result_check_username->num_rows > 0) {
                    $error_message = 'Username sudah terdaftar.';
                } elseif ($result_check_phone->num_rows > 0) {
                    $error_message = 'Nomor Telepon sudah terdaftar.';
                }
                $stmt_check_username->close();
                $stmt_check_phone->close();
            }
        } else {
            // Hash the password before storing
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Insert new courier
            $sql_insert = "INSERT INTO couriers (username, password, name, phone) VALUES (?, ?, ?, ?)";
            $stmt_insert = $conn->prepare($sql_insert);
            $stmt_insert->bind_param("ssss", $username, $hashed_password, $name, $phone);

            if ($stmt_insert->execute()) {
                $success_message = 'Pendaftaran kurir berhasil! Anda bisa login sekarang.';
            } else {
                $error_message = 'Terjadi kesalahan saat mendaftar: ' . $conn->error;
            }
            $stmt_insert->close();
        }
        $stmt_check->close();
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Kurir - Mycanteen</title>
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
        <h1 class="text-3xl font-bold text-gray-900 text-center mb-6">Daftar Kurir Baru</h1>

        <?php if ($success_message): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline"><?php echo htmlspecialchars($success_message); ?></span>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline"><?php echo htmlspecialchars($error_message); ?></span>
            </div>
        <?php endif; ?>

        <form action="courier_register.php" method="post">
            <div class="mb-4">
                <label for="username" class="block text-gray-700 text-sm font-medium mb-2">Username:</label>
                <input type="text" id="username" name="username" class="form-input" value="<?php echo htmlspecialchars($username ?? ''); ?>" required>
            </div>
            <div class="mb-4">
                <label for="name" class="block text-gray-700 text-sm font-medium mb-2">Nama Lengkap:</label>
                <input type="text" id="name" name="name" class="form-input" value="<?php echo htmlspecialchars($name ?? ''); ?>" required>
            </div>
            <div class="mb-4">
                <label for="phone" class="block text-gray-700 text-sm font-medium mb-2">Nomor Telepon:</label>
                <input type="tel" id="phone" name="phone" class="form-input" value="<?php echo htmlspecialchars($phone ?? ''); ?>" required>
            </div>
            <div class="mb-4">
                <label for="password" class="block text-gray-700 text-sm font-medium mb-2">Password:</label>
                <input type="password" id="password" name="password" class="form-input" required>
            </div>
            <div class="mb-6">
                <label for="confirm_password" class="block text-gray-700 text-sm font-medium mb-2">Konfirmasi Password:</label>
                <input type="password" id="confirm_password" name="confirm_password" class="form-input" required>
            </div>
            <button type="submit" class="w-full bg-green-600 text-white py-3 px-4 rounded-full font-bold text-lg hover:bg-green-700 transition-colors duration-200">
                Daftar
            </button>
        </form>
        <p class="mt-4 text-center text-gray-600">
            Sudah terdaftar? <a href="courier_login.php" class="text-blue-500 hover:underline">Login di sini</a>
        </p>
    </div>
</body>
</html>