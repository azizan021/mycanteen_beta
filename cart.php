<?php
// cart.php
session_start(); // HARUS dipanggil di awal setiap skrip yang menggunakan sesi

// Memasukkan file koneksi database (opsional di sini jika semua detail produk ada di sesi, tapi praktik yang baik)
include 'db_connect.php';

// Inisialisasi keranjang jika belum ada
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$cart_items = $_SESSION['cart'];
$subtotal = 0;
$handling_fee = 2000; // Biaya penanganan tetap Rp 2000

// Hitung subtotal
foreach ($cart_items as $item_id => $item) {
    $subtotal += ($item['price'] * $item['quantity']);
}

$total = $subtotal + $handling_fee;

// Menghitung jumlah item di keranjang untuk ditampilkan di header
$cart_item_count = 0;
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cart_item_count += $item['quantity'];
    }
}

$conn->close(); // Tutup koneksi database
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keranjang Belanja - Mycanteen</title>
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
        .form-control-custom {
            border-radius: 0.375rem;
            border: 1px solid #ced4da;
            padding: 0.75rem 1rem;
        }
        .form-control-custom:focus {
            border-color: #86b7fe;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
            outline: 0;
        }
        .item-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 0.5rem;
        }
        .quantity-control button {
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            font-weight: bold;
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
                <?php if (isset($_SESSION['customer_logged_in']) && $_SESSION['customer_logged_in'] === true): ?>
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
                <a href="cart.php" class="position-relative text-primary" aria-label="Keranjang Belanja">
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
        <h1 class="h3 fw-bold text-gray-900 mb-4">Keranjang Belanja Anda</h1>

        <?php if (empty($cart_items)): ?>
            <div class="card shadow-sm p-4 text-center">
                <p class="lead text-muted">Keranjang belanja Anda kosong. Yuk, cari makanan favorit Anda!</p>
                <a href="index.php" class="btn btn-primary btn-lg mt-3">Mulai Belanja</a>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <div class="col-md-8">
                    <div class="card shadow-sm p-4">
                        <h2 class="h5 fw-semibold text-gray-800 mb-4">Ringkasan Pesanan</h2>
                        <?php foreach ($cart_items as $item_id => $item): ?>
                            <div class="d-flex align-items-center border-bottom pb-3 mb-3">
                                <img src="<?php echo htmlspecialchars($item['image_url']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="item-image me-4">
                                <div class="flex-grow-1">
                                    <h3 class="h6 fw-medium text-gray-900 mb-1"><?php echo htmlspecialchars($item['name']); ?></h3>
                                    <p class="text-primary fw-semibold mb-0">Rp<?php echo number_format($item['price'], 0, ',', '.'); ?>,-</p>
                                </div>
                                <div class="d-flex align-items-center quantity-control me-3">
                                    <form action="update_cart.php" method="post" class="d-inline-flex">
                                        <input type="hidden" name="menu_id" value="<?php echo htmlspecialchars($item_id); ?>">
                                        <input type="hidden" name="action" value="decrease">
                                        <button type="submit" class="btn btn-outline-secondary rounded-circle">-</button>
                                    </form>
                                    <span class="mx-2 fw-bold text-gray-900"><?php echo htmlspecialchars($item['quantity']); ?></span>
                                    <form action="update_cart.php" method="post" class="d-inline-flex">
                                        <input type="hidden" name="menu_id" value="<?php echo htmlspecialchars($item_id); ?>">
                                        <input type="hidden" name="action" value="increase">
                                        <button type="submit" class="btn btn-primary rounded-circle">+</button>
                                    </form>
                                </div>
                                <div class="text-end">
                                    <p class="fw-bold text-gray-900 mb-1">Rp<?php echo number_format($item['price'] * $item['quantity'], 0, ',', '.'); ?>,-</p>
                                    <form action="update_cart.php" method="post">
                                        <input type="hidden" name="menu_id" value="<?php echo htmlspecialchars($item_id); ?>">
                                        <input type="hidden" name="action" value="remove">
                                        <button type="submit" class="btn btn-link text-danger p-0 text-decoration-none">Hapus</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <div class="pt-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-muted fw-semibold">Subtotal:</span>
                                <span class="text-gray-900 fw-semibold">Rp<?php echo number_format($subtotal, 0, ',', '.'); ?>,-</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="text-muted fw-semibold">Biaya Penanganan:</span>
                                <span class="text-gray-900 fw-semibold">Rp<?php echo number_format($handling_fee, 0, ',', '.'); ?>,-</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center border-top pt-3 fw-bold fs-5">
                                <span class="text-gray-900">Total:</span>
                                <span class="text-primary">Rp<?php echo number_format($total, 0, ',', '.'); ?>,-</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card shadow-sm p-4">
                        <h2 class="h5 fw-semibold text-gray-800 mb-4">Detail Pengiriman</h2>
                        <form action="process_order.php" method="post">
                            <div class="mb-3">
                                <label for="nama_penerima" class="form-label text-muted fw-medium mb-1">Nama Penerima:</label>
                                <input type="text" id="nama_penerima" name="nama_penerima" class="form-control form-control-custom" placeholder="Masukkan nama Anda" value="<?php echo htmlspecialchars($_SESSION['customer_name'] ?? ''); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="lokasi_antar" class="form-label text-muted fw-medium mb-1">Lokasi Antar (ex: Gedung A, A.201):</label>
                                <textarea id="lokasi_antar" name="lokasi_antar" rows="3" class="form-control form-control-custom" placeholder="Contoh: Gedung A, Lantai 2, Ruang A.201" required></textarea>
                            </div>
                            <div class="mb-4">
                                <label for="catatan" class="form-label text-muted fw-medium mb-1">Catatan (Opsional):</label>
                                <textarea id="catatan" name="catatan" rows="3" class="form-control form-control-custom" placeholder="Contoh: Tanpa bawang, tambahkan sendok"></textarea>
                            </div>

                            <input type="hidden" name="total_amount" value="<?php echo $total; ?>">
                            <?php if (isset($_SESSION['customer_id'])): ?>
                                <input type="hidden" name="customer_id" value="<?php echo htmlspecialchars($_SESSION['customer_id']); ?>">
                            <?php endif; ?>
                            <button type="submit" class="btn btn-success btn-lg w-100 fw-bold">
                                Bayar Sekarang
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>