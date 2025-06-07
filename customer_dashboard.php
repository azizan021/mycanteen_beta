<?php
// customer_dashboard.php
session_start();
include 'db_connect.php'; // Memasukkan file koneksi database

// Check if customer is logged in
if (!isset($_SESSION['customer_logged_in']) || $_SESSION['customer_logged_in'] !== true) {
    header("Location: login.php"); // Arahkan ke halaman login terpadu
    exit();
}

// Menghitung jumlah item di keranjang untuk ditampilkan di header
$cart_item_count = 0;
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cart_item_count += $item['quantity'];
    }
}

$customer_orders = [];
$customer_id = $_SESSION['customer_id'];

// Fetch orders for the logged-in customer
// MODIFIKASI: Tambahkan JOIN untuk mendapatkan nama kurir
$sql_customer_orders = "SELECT o.id, o.customer_name, o.delivery_location, o.total_amount, o.status, o.order_date, c.name AS courier_name
                        FROM orders o
                        LEFT JOIN couriers c ON o.courier_id = c.id
                        WHERE o.customer_id = ? ORDER BY o.order_date DESC";
$stmt_customer_orders = $conn->prepare($sql_customer_orders);
$stmt_customer_orders->bind_param("i", $customer_id);
$stmt_customer_orders->execute();
$result_customer_orders = $stmt_customer_orders->get_result();

if ($result_customer_orders->num_rows > 0) {
    while ($row = $result_customer_orders->fetch_assoc()) {
        $customer_orders[] = $row;
    }
}
$stmt_customer_orders->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Pelanggan - Mycanteen</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f0f2f5;
        }
    </style>
</head>
<body>
    <header class="bg-white shadow-sm py-4 px-6 flex items-center justify-between rounded-b-lg">
        <div class="text-blue-600 font-bold text-2xl">
            Mycanteen
            <span class="block text-gray-500 text-xs font-medium">Food Delivery</span>
        </div>
        <div class="flex items-center space-x-4">
            <a href="index.php" class="text-gray-600 hover:text-blue-500">Beranda</a>
            <a href="customer_logout.php" class="text-gray-600 hover:text-red-500">Logout</a>
            <a href="cart.php" class="relative text-gray-600 hover:text-blue-500">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.769.746 1.769H17m0-.747A.75.75 0 0017.75 18a.75.75 0 00.75-.75h-1.5z"></path>
                </svg>
                <?php if ($cart_item_count > 0): ?>
                    <span class="absolute -top-2 -right-2 bg-red-500 text-white text-xs font-bold rounded-full h-5 w-5 flex items-center justify-center">
                        <?php echo $cart_item_count; ?>
                    </span>
                <?php endif; ?>
            </a>
        </div>
    </header>

    <main class="container mx-auto px-4 py-8">
        <div class="bg-white rounded-lg shadow-md p-6 text-center max-w-2xl mx-auto mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-4">Selamat Datang, <?php echo htmlspecialchars($_SESSION['customer_name'] ?? 'Pelanggan'); /* cite: 1 */?>!</h1>
            <p class="text-gray-700 text-lg mb-6">Ini adalah halaman dashboard pelanggan Anda. Di sini Anda bisa melihat riwayat pesanan, mengelola profil, dll.</p>

            <div class="mt-8 flex justify-center space-x-4">
                <a href="index.php" class="inline-block bg-blue-500 text-white px-6 py-3 rounded-full font-bold text-lg hover:bg-blue-600 transition-colors duration-200">
                    Mulai Belanja
                </a>
                <a href="#orders" class="inline-block border border-blue-500 text-blue-500 px-6 py-3 rounded-full font-bold text-lg hover:bg-blue-50 transition-colors duration-200">
                    Lihat Riwayat Pesanan
                </a>
            </div>
        </div>

        <section id="orders" class="mb-8">
            <h2 class="text-2xl font-semibold text-gray-800 mb-4">Riwayat Pesanan Anda</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php if (!empty($customer_orders)): ?>
                    <?php foreach ($customer_orders as $order): ?>
                        <div class="bg-white rounded-lg shadow-md p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-2">Pesanan #<?php echo htmlspecialchars($order['id']); ?></h3>
                            <p class="text-gray-700"><strong>Tanggal:</strong> <?php echo htmlspecialchars(date('d M Y H:i', strtotime($order['order_date']))); ?></p>
                            <p class="text-gray-700"><strong>Penerima:</strong> <?php echo htmlspecialchars($order['customer_name']); ?></p>
                            <p class="text-gray-700"><strong>Lokasi Antar:</strong> <?php echo htmlspecialchars($order['delivery_location']); ?></p>
                            <p class="text-gray-700"><strong>Total:</strong> Rp<?php echo number_format($order['total_amount'], 0, ',', '.'); ?>,-</p>
                            <p class="text-gray-700">
                                <strong>Kurir:</strong>
                                <?php echo !empty($order['courier_name']) ? htmlspecialchars($order['courier_name']) : 'Belum Ditentukan'; ?>
                            </p>
                            <p class="text-lg font-bold mt-2">
                                <strong>Status:</strong>
                                <?php
                                    $status_color = '';
                                    switch ($order['status']) {
                                        case 'Pending':
                                            $status_color = 'text-yellow-600';
                                            break;
                                        case 'Accepted':
                                            $status_color = 'text-blue-600';
                                            break;
                                        case 'Delivered':
                                            $status_color = 'text-green-600';
                                            break;
                                        case 'Cancelled':
                                            $status_color = 'text-red-600';
                                            break;
                                        default:
                                            $status_color = 'text-gray-600';
                                    }
                                ?>
                                <span class="<?php echo $status_color; ?>"><?php echo htmlspecialchars($order['status']); ?></span>
                            </p>
                            <div class="mt-4 text-right">
                                <a href="track_order.php?order_id=<?php echo htmlspecialchars($order['id']); ?>" class="inline-block bg-blue-500 text-white px-4 py-2 rounded-full text-sm font-medium hover:bg-blue-600 transition-colors duration-200">
                                    Lihat Detail
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-gray-600 col-span-full">Anda belum memiliki riwayat pesanan.</p>
                <?php endif; ?>
            </div>
        </section>
    </main>
</body>
</html>