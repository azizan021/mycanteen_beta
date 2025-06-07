<?php
// courier_order_receipt.php
session_start();
include 'db_connect.php'; // Memasukkan file koneksi database

// Check if courier is logged in
if (!isset($_SESSION['courier_logged_in']) || $_SESSION['courier_logged_in'] !== true) {
    header("Location: login.php"); // Arahkan ke halaman login terpadu
    exit();
}

$order = null;
$order_items = [];
$order_id = null;

if (isset($_GET['order_id']) && is_numeric($_GET['order_id'])) {
    $order_id = intval($_GET['order_id']);

    // Fetch order details, including courier_name
    $sql_order = "SELECT o.id, o.customer_id, o.courier_id, o.customer_name, o.delivery_location, o.notes, o.total_amount, o.status, o.order_date, c.name AS courier_name, cust.name AS customer_full_name
                  FROM orders o
                  LEFT JOIN couriers c ON o.courier_id = c.id
                  LEFT JOIN customers cust ON o.customer_id = cust.id
                  WHERE o.id = ?";
    $stmt_order = $conn->prepare($sql_order);
    $stmt_order->bind_param("i", $order_id);
    $stmt_order->execute();
    $result_order = $stmt_order->get_result();

    if ($result_order->num_rows > 0) {
        $order = $result_order->fetch_assoc();

        // **MODIFIKASI LOGIKA VALIDASI AKSES DI SINI**
        $is_authorized = false;
        // Kurir yang ditugaskan selalu memiliki akses
        if ($order['courier_id'] === $_SESSION['courier_id']) {
            $is_authorized = true;
        }
        // Kurir juga bisa melihat pesanan yang masih 'Pending' (belum diambil siapa-siapa)
        if ($order['status'] === 'Pending') {
            $is_authorized = true;
        }

        if (!$is_authorized) {
             echo "<script>alert('Anda tidak memiliki akses untuk struk pesanan ini.'); window.location.href='courier_dashboard.php';</script>";
             exit();
        }

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
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk Pesanan #<?php echo htmlspecialchars($order_id); ?> - Mycanteen</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f0f2f5;
        }
        .navbar-gofood {
            background-color: #fff;
            box-shadow: 0 2px 4px rgba(0,0,0,.08);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        .item-image-receipt {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 0.25rem;
        }
        @media print {
            body {
                background-color: #fff;
            }
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <header class="navbar navbar-expand-lg navbar-gofood no-print">
        <div class="container py-2">
            <a class="navbar-brand text-primary fw-bold" href="courier_dashboard.php">
                Mycanteen
                <span class="d-block text-secondary fs-6 fw-normal">Kurir Struk</span>
            </a>
            <div class="d-flex align-items-center ms-auto">
                <a href="courier_dashboard.php" class="text-secondary me-3" aria-label="Kembali ke Dashboard">
                    <i class="bi bi-arrow-left-circle fs-5"></i>
                </a>
                <button onclick="window.print()" class="btn btn-link text-secondary p-0" aria-label="Cetak Struk">
                    <i class="bi bi-printer fs-5"></i>
                </button>
            </div>
        </div>
    </header>

    <main class="container my-4">
        <?php if ($order):?>
            <div class="card shadow-sm p-4 mx-auto" style="max-width: 700px;">
                <h1 class="h4 fw-bold text-center text-gray-900 mb-4">Struk Pesanan</h1>
                <p class="text-center text-muted mb-4">Tanggal: <?php echo htmlspecialchars(date('d F Y H:i', strtotime($order['order_date'])));?></p>

                <div class="mb-4 pb-3 border-bottom">
                    <h2 class="h5 fw-semibold text-gray-800 mb-2">Detail Pesanan #<?php echo htmlspecialchars($order['id']);?></h2>
                    <p class="text-muted mb-1"><strong>Nama Penerima:</strong> <?php echo htmlspecialchars($order['customer_name']);?></p>
                    <p class="text-muted mb-1"><strong>Lokasi Antar:</strong> <?php echo htmlspecialchars($order['delivery_location']);?></p>
                    <p class="text-muted mb-1"><strong>Catatan:</strong> <?php echo htmlspecialchars($order['notes'] ?: '-');?></p>
                    <p class="text-muted mb-1"><strong>Status:</strong> <?php echo htmlspecialchars($order['status']);?></p>
                    <?php if (!empty($order['courier_name'])):?>
                        <p class="text-muted mb-0"><strong>Kurir Pengantar:</strong> <?php echo htmlspecialchars($order['courier_name']);?></p>
                    <?php endif; ?>
                </div>

                <h2 class="h5 fw-semibold text-gray-800 mb-3">Item Pesanan:</h2>
                <div class="border rounded-lg overflow-hidden mb-4">
                    <?php if (!empty($order_items)):?>
                        <?php foreach ($order_items as $item):?>
                            <div class="d-flex align-items-center p-3 border-bottom last:border-bottom-0">
                                <img src="<?php echo htmlspecialchars($item['image_url']);?>" alt="<?php echo htmlspecialchars($item['menu_name']);?>" class="item-image-receipt me-3">
                                <div class="flex-grow-1">
                                    <p class="fw-medium text-gray-900 mb-0"><?php echo htmlspecialchars($item['menu_name']);?></p>
                                    <small class="text-muted"><?php echo htmlspecialchars($item['quantity']);?> x Rp<?php echo number_format($item['price_at_purchase'], 0, ',', '.');?>,-</small>
                                </div>
                                <div class="fw-bold text-gray-900">
                                    Rp<?php echo number_format($item['quantity'] * $item['price_at_purchase'], 0, ',', '.');?>,-
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="p-3 text-muted">Tidak ada item dalam pesanan ini.</p>
                    <?php endif; ?>
                </div>

                <div class="text-end">
                    <p class="h6 fw-semibold text-gray-800 mb-1">Total Pembayaran:</p>
                    <p class="h3 fw-bold text-primary">Rp<?php echo number_format($order['total_amount'], 0, ',', '.');?>, -</p>
                </div>

                <div class="mt-4 text-center no-print">
                    <?php
                    // Tombol chat untuk kurir hanya jika pesanan belum selesai
                    // Periksa juga apakah courier_id sudah ada (pesanan sudah diambil)
                    if ((isset($order['status']) && ($order['status'] == 'Accepted' && $order['courier_id'] == $_SESSION['courier_id'])) || (isset($order['status']) && $order['status'] == 'Pending')) :
                    ?>
                        <a href="chat.php?order_id=<?php echo htmlspecialchars($order['id']);?>" class="btn btn-success fw-bold me-2">
                            Chat dengan Pelanggan
                        </a>
                    <?php endif; ?>
                    <a href="courier_dashboard.php" class="btn btn-primary fw-bold">
                        Kembali ke Dashboard
                    </a>
                </div>
            </div>
        <?php else: ?>
            <div class="card shadow-sm p-4 text-center mx-auto" style="max-width: 600px;">
                <p class="lead text-muted">Pesanan tidak ditemukan atau ID tidak valid.</p>
                <a href="courier_dashboard.php" class="btn btn-primary btn-lg mt-3">Kembali ke Dashboard</a>
            </div>
        <?php endif; ?>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>