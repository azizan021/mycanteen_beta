<?php
// track_order.php
session_start();
include 'db_connect.php'; // Memasukkan file koneksi database

$order = null;
$order_items = [];
$order_id = null;

// Get order_id from URL
if (isset($_GET['order_id']) && is_numeric($_GET['order_id'])) {
    $order_id = intval($_GET['order_id']);

    // Fetch order details, including courier_name
    $sql_order = "SELECT o.id, o.customer_id, o.courier_id, o.customer_name, o.delivery_location, o.notes, o.total_amount, o.status, o.order_date, c.name AS courier_name
                  FROM orders o
                  LEFT JOIN couriers c ON o.courier_id = c.id
                  WHERE o.id = ?";
    $stmt_order = $conn->prepare($sql_order);
    $stmt_order->bind_param("i", $order_id);
    $stmt_order->execute();
    $result_order = $stmt_order->get_result();

    if ($result_order->num_rows > 0) {
        $order = $result_order->fetch_assoc();

        // Fetch order items
        $sql_order_items = "SELECT oi.quantity, oi.price_at_purchase, mi.name AS menu_name, mi.image_url
                            FROM order_items oi
                            JOIN menu_items mi ON oi.menu_item_id = mi.id
                            WHERE oi.order_id = ?";
        $stmt_items = $conn->prepare($sql_order_items);
        $stmt_items->bind_param("i", $order_id);
        $stmt_items->execute();
        $result_items = $stmt_items->get_result();

        while ($row = $result_items->fetch_assoc()) {
            $order_items[] = $row;
        }
        $stmt_items->close();
    }
    $stmt_order->close();
}

$conn->close();

// Menghitung jumlah item di keranjang untuk ditampilkan di header
$cart_item_count = 0;
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cart_item_count += $item['quantity'];
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lacak Pesanan - Mycanteen</title>
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
            <a href="index.php" class="text-gray-600 hover:text-blue-500">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
            </a>
            <?php if (isset($_SESSION['customer_logged_in']) && $_SESSION['customer_logged_in'] === true): /* cite: 1 */?>
                <a href="customer_dashboard.php" class="text-gray-600 hover:text-blue-500">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                </a>
                <a href="customer_logout.php" class="text-gray-600 hover:text-red-500 text-sm">Logout</a>
            <?php else: ?>
                <a href="login.php" class="text-gray-600 hover:text-blue-500">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                </a>
                <a href="login.php" class="text-gray-600 hover:text-blue-500 text-sm">Login</a>
                <span class="text-gray-400">|</span>
                <a href="customer_register.php" class="text-gray-600 hover:text-blue-500 text-sm">Daftar</a>
            <?php endif; ?>
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
        <h1 class="text-3xl font-bold text-gray-900 mb-6">Lacak Pesanan Anda</h1>

        <?php if ($order): /* cite: 1 */?>
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h2 class="text-2xl font-semibold text-gray-800 mb-4">Detail Pesanan #<?php echo htmlspecialchars($order['id']); /* cite: 1 */?></h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-gray-700">
                    <div>
                        <p><strong>Nama Penerima:</strong> <?php echo htmlspecialchars($order['customer_name']); /* cite: 1 */?></p>
                        <p><strong>Lokasi Antar:</strong> <?php echo htmlspecialchars($order['delivery_location']); /* cite: 1 */?></p>
                        <p><strong>Catatan:</strong> <?php echo htmlspecialchars($order['notes']); /* cite: 1 */?></p>
                    </div>
                    <div>
                        <p><strong>Tanggal Pesanan:</strong> <?php echo htmlspecialchars(date('d F Y H:i', strtotime($order['order_date']))); /* cite: 1 */?></p>
                        <p class="text-lg font-bold">
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
                            <span class="<?php echo $status_color; ?>"><?php echo htmlspecialchars($order['status']); /* cite: 1 */?></span>
                        </p>
                        <?php if (!empty($order['courier_name'])): /* cite: 1 */?>
                            <p class="text-gray-700"><strong>Kurir Pengantar:</strong> <?php echo htmlspecialchars($order['courier_name']); /* cite: 1 */?></p>
                        <?php elseif ($order['status'] == 'Accepted' || $order['status'] == 'Delivered'): /* cite: 1 */?>
                            <p class="text-gray-700"><strong>Kurir Pengantar:</strong> Belum Ditentukan (atau data kurir tidak ditemukan)</p>
                        <?php endif; ?>
                        <p class="text-xl font-extrabold text-blue-600 mt-2">
                            <strong>Total:</strong> Rp<?php echo number_format($order['total_amount'], 0, ',', '.'); /* cite: 1 */?>,-
                        </p>
                    </div>
                </div>

                <h3 class="text-xl font-semibold text-gray-800 mt-6 mb-3">Item Pesanan:</h3>
                <div class="border rounded-lg overflow-hidden">
                    <?php if (!empty($order_items)): /* cite: 1 */?>
                        <?php foreach ($order_items as $item): /* cite: 1 */?>
                            <div class="flex items-center p-4 border-b last:border-b-0">
                                <img src="<?php echo htmlspecialchars($item['image_url']); /* cite: 1 */?>" alt="<?php echo htmlspecialchars($item['menu_name']); /* cite: 1 */?>" class="w-16 h-16 object-cover rounded-md mr-4">
                                <div class="flex-1">
                                    <p class="font-medium text-gray-900"><?php echo htmlspecialchars($item['menu_name']); /* cite: 1 */?></p>
                                    <p class="text-gray-600 text-sm">Jumlah: <?php echo htmlspecialchars($item['quantity']); /* cite: 1 */?></p>
                                    <p class="text-gray-600 text-sm">Harga Satuan: Rp<?php echo number_format($item['price_at_purchase'], 0, ',', '.'); /* cite: 1 */?>,-</p>
                                </div>
                                <div class="text-right font-bold text-gray-900">
                                    Rp<?php echo number_format($item['quantity'] * $item['price_at_purchase'], 0, ',', '.'); /* cite: 1 */?>,-
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="p-4 text-gray-600">Tidak ada item dalam pesanan ini.</p>
                    <?php endif; ?>
                </div>
                <div class="mt-6 text-center">
                    <?php if (isset($_SESSION['customer_logged_in']) && $_SESSION['customer_logged_in'] === true && ($order['status'] == 'Accepted' || $order['status'] == 'Pending')): /* cite: 1 */?>
                        <a href="chat.php?order_id=<?php echo htmlspecialchars($order['id']); /* cite: 1 */?>" class="inline-block bg-green-500 text-white px-6 py-3 rounded-full font-bold text-lg hover:bg-green-600 transition-colors duration-200">
                            Chat dengan Kurir
                        </a>
                    <?php elseif (isset($_SESSION['customer_logged_in']) && $_SESSION['customer_logged_in'] === true && $order['status'] == 'Delivered'): /* cite: 1 */?>
                        <a href="chat.php?order_id=<?php echo htmlspecialchars($order['id']); /* cite: 1 */?>" class="inline-block bg-gray-500 text-white px-6 py-3 rounded-full font-bold text-lg opacity-75 cursor-not-allowed">
                            Chat (Selesai)
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="text-center">
                <a href="index.php" class="inline-block bg-blue-500 text-white px-6 py-3 rounded-full font-bold text-lg hover:bg-blue-600 transition-colors duration-200">
                    Kembali ke Beranda
                </a>
            </div>
        <?php else: ?>
            <div class="bg-white rounded-lg shadow-md p-6 text-center">
                <p class="text-gray-700 text-lg">Pesanan tidak ditemukan atau ID tidak valid.</p>
                <a href="index.php" class="mt-6 inline-block bg-blue-500 text-white px-6 py-3 rounded-full hover:bg-blue-600 transition-colors duration-200 text-lg">Kembali ke Beranda</a>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>