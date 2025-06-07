<?php
// process_order.php
session_start(); // Pastikan sesi dimulai

// Memasukkan file koneksi database
include 'db_connect.php';

// Pastikan request adalah POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php"); // Redirect jika bukan POST
    exit();
}

// Pastikan keranjang tidak kosong
if (empty($_SESSION['cart'])) {
    echo "<script>alert('Keranjang Anda kosong. Tidak dapat memproses pesanan.'); window.location.href='index.php';</script>";
    exit();
}

// Ambil data dari form POST
$customer_name = htmlspecialchars(trim($_POST['nama_penerima']));
$delivery_location = htmlspecialchars(trim($_POST['lokasi_antar']));
$notes = htmlspecialchars(trim($_POST['catatan']));
$total_amount_from_form = floatval($_POST['total_amount']); // Ambil total dari form (akan diverifikasi ulang)
$customer_id = isset($_POST['customer_id']) && is_numeric($_POST['customer_id']) ? intval($_POST['customer_id']) : null; // Get customer_id

// ==============================================================
// VALIDASI DAN VERIFIKASI DATA (PENTING UNTUK APLIKASI NYATA)
// ==============================================================
// Hitung ulang subtotal dan total dari sesi untuk mencegah manipulasi harga dari client-side
$calculated_subtotal = 0;
foreach ($_SESSION['cart'] as $item) {
    $calculated_subtotal += ($item['price'] * $item['quantity']);
}
$handling_fee = 2000;
$calculated_total = $calculated_subtotal + $handling_fee;

// Verifikasi apakah total dari form sesuai dengan yang dihitung ulang
if ($calculated_total != $total_amount_from_form) {
    // Jika tidak sesuai, ini bisa jadi upaya manipulasi atau error.
    // Lakukan penanganan error yang sesuai, misalnya log, atau redirect ke halaman error.
    echo "<script>alert('Terjadi kesalahan validasi pembayaran. Silakan coba lagi.'); window.location.href='cart.php';</script>";
    exit();
}

// ==============================================================
// MEMULAI TRANSAKSI DATABASE (PENTING UNTUK INTEGRITAS DATA)
// ==============================================================
$conn->begin_transaction(); // Mulai transaksi

$order_id = null; // Initialize order_id

try {
    // 1. Masukkan data ke tabel `orders`
    if ($customer_id) {
        $sql_insert_order = "INSERT INTO orders (customer_id, customer_name, delivery_location, notes, total_amount, status) VALUES (?, ?, ?, ?, ?, 'Pending')";
        $stmt_order = $conn->prepare($sql_insert_order);
        $stmt_order->bind_param("isssd", $customer_id, $customer_name, $delivery_location, $notes, $calculated_total); // isssd = integer, string, string, string, double
    } else {
        $sql_insert_order = "INSERT INTO orders (customer_name, delivery_location, notes, total_amount, status) VALUES (?, ?, ?, ?, 'Pending')";
        $stmt_order = $conn->prepare($sql_insert_order);
        $stmt_order->bind_param("sssd", $customer_name, $delivery_location, $notes, $calculated_total); // sssd = string, string, string, double
    }
    $stmt_order->execute();

    $order_id = $conn->insert_id; // Dapatkan ID pesanan yang baru saja dimasukkan
    $stmt_order->close();

    // 2. Masukkan item keranjang ke tabel `order_items`
    $sql_insert_item = "INSERT INTO order_items (order_id, menu_item_id, quantity, price_at_purchase) VALUES (?, ?, ?, ?)";
    $stmt_item = $conn->prepare($sql_insert_item);

    foreach ($_SESSION['cart'] as $item_id => $item_data) {
        $menu_item_id = $item_data['id'];
        $quantity = $item_data['quantity'];
        $price_at_purchase = $item_data['price']; // Gunakan harga saat item ditambahkan ke keranjang

        $stmt_item->bind_param("iiid", $order_id, $menu_item_id, $quantity, $price_at_purchase);
        $stmt_item->execute();
    }
    $stmt_item->close();

    // Jika semua berhasil, commit transaksi
    $conn->commit();

    // Kosongkan keranjang setelah pesanan berhasil disimpan
    unset($_SESSION['cart']);

    // Pesan sukses untuk ditampilkan di halaman QRIS
    $success_message = "Pesanan Anda berhasil dibuat! Silakan lakukan pembayaran.";

} catch (mysqli_sql_exception $e) {
    // Jika ada kesalahan, rollback transaksi
    $conn->rollback();
    error_log("Gagal memproses pesanan: " . $e->getMessage()); // Catat error ke log server
    $error_message = "Terjadi kesalahan saat memproses pesanan Anda. Silakan coba lagi. " . $e->getMessage();
    // Redirect ke halaman error atau tampilkan pesan error
    echo "<script>alert('".$error_message."'); window.location.href='cart.php';</script>";
    exit();
}

$conn->close(); // Tutup koneksi database

// Menghitung jumlah item di keranjang untuk ditampilkan di header (seharusnya 0 sekarang)
$cart_item_count = 0; // Setelah unset($_SESSION['cart']), ini akan 0
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran Pesanan - Mycanteen</title>
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
        .qris-image {
            max-width: 300px; /* Batasi ukuran QRIS */
            height: auto;
            display: block;
            margin: 2rem auto;
            border: 1px solid #ccc;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <header class="navbar navbar-expand-lg navbar-gofood">
        <div class="container py-2">
            <a class="navbar-brand text-primary fw-bold" href="index.php">
                Mycanteen
                <span class="d-block text-secondary fs-6 fw-normal">Food Delivery</span>
            </a>
            <div class="d-flex align-items-center ms-auto">
                <a href="index.php" class="text-secondary me-3" aria-label="Kembali ke Beranda">
                    <i class="bi bi-arrow-left-circle fs-5"></i>
                </a>
                <?php if (isset($_SESSION['customer_logged_in']) && $_SESSION['customer_logged_in'] === true): /* cite: 1 */?>
                    <a href="customer_dashboard.php" class="text-secondary me-3" aria-label="Dashboard Pelanggan">
                        <i class="bi bi-person-circle fs-5"></i>
                    </a>
                    <a href="customer_logout.php" class="text-secondary text-sm me-3">Logout</a>
                <?php else: ?>
                    <a href="login.php" class="text-secondary me-3" aria-label="Login">
                        <i class="bi bi-person-circle fs-5"></i>
                    </a>
                    <a href="login.php" class="text-secondary text-sm me-3">Login</a>
                    <span class="text-muted me-3">|</span>
                    <a href="customer_register.php" class="text-secondary text-sm me-3">Daftar</a>
                <?php endif; ?>
                <a href="cart.php" class="position-relative text-secondary" aria-label="Keranjang Belanja">
                    <i class="bi bi-cart-fill fs-5"></i>
                    <?php if ($cart_item_count > 0): ?>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                            <?php echo $cart_item_count; ?>
                            <span class="visually-hidden">items in cart</span>
                        </span>
                    <?php endif; ?>
                </a>
            </div>
        </div>
    </header>

    <main class="container my-4">
        <div class="card shadow-sm p-4 text-center mx-auto" style="max-width: 600px;">
            <h1 class="h3 fw-bold text-success mb-3">Pesanan Berhasil Dibuat!</h1>
            <p class="lead text-muted mb-4"><?php echo $success_message ?? ''; ?></p>

            <h2 class="h5 fw-semibold text-gray-800 mb-3">Lakukan Pembayaran Melalui QRIS</h2>
            <p class="text-muted mb-3">Scan QRIS di bawah ini untuk menyelesaikan pembayaran sebesar:</p>
            <p class="h3 fw-bold text-primary mb-4">Rp<?php echo number_format($calculated_total, 0, ',', '.'); ?>,-</p>

            <img src="https://via.placeholder.com/300x300/28a745/ffffff?text=QRIS+Placeholder" alt="QRIS Pembayaran" class="qris-image">

            <p class="text-sm text-muted mt-3">
                * Ini adalah placeholder QRIS. Untuk implementasi nyata, Anda perlu mengintegrasikan dengan penyedia layanan pembayaran QRIS.
            </p>

            <div class="mt-4">
                <?php if ($order_id): // Ensure order_id is set before generating the link ?>
                    <a href="track_order.php?order_id=<?php echo htmlspecialchars($order_id); ?>" class="btn btn-primary btn-lg fw-bold">
                        Lacak Pesanan Anda
                    </a>
                <?php else: ?>
                     <a href="index.php" class="btn btn-primary btn-lg fw-bold">
                        Kembali ke Beranda
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>