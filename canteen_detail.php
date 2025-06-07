<?php
session_start(); // PENTING: Harus di baris paling atas untuk sesi
// Memasukkan file koneksi database
include 'db_connect.php';

// Menghitung jumlah item di keranjang untuk ditampilkan di header
$cart_item_count = 0;
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cart_item_count += $item['quantity'];
    }
}

$canteen_id = null;
$canteen = null;
$menu_items = [];

// Memeriksa apakah ada ID kantin di URL (misal: canteen_detail.php?id=1)
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $canteen_id = intval($_GET['id']); // Ambil ID kantin dan pastikan itu integer

    // 1. Ambil detail kantin
    $sql_canteen = "SELECT id, name, rating, reviews, image_url FROM canteens WHERE id = ?";
    $stmt_canteen = $conn->prepare($sql_canteen); // Persiapkan statement SQL
    $stmt_canteen->bind_param("i", $canteen_id); // Bind parameter ID (i = integer)
    $stmt_canteen->execute(); // Jalankan query
    $result_canteen = $stmt_canteen->get_result(); // Ambil hasilnya

    if ($result_canteen->num_rows > 0) {
        $canteen = $result_canteen->fetch_assoc(); // Ambil satu baris hasil
    } else {
        // Jika kantin tidak ditemukan
        $canteen = null; // Set null agar bisa menampilkan pesan "Kantin tidak ditemukan"
    }
    $stmt_canteen->close(); // Tutup statement

    // 2. Ambil semua menu dari kantin ini
    if ($canteen) { // Hanya ambil menu jika kantin ditemukan
        $sql_menu_items = "SELECT id, name, description, price, image_url FROM menu_items WHERE canteen_id = ?";
        $stmt_menu = $conn->prepare($sql_menu_items);
        $stmt_menu->bind_param("i", $canteen_id);
        $stmt_menu->execute();
        $result_menu = $stmt_menu->get_result();

        if ($result_menu->num_rows > 0) {
            while ($row = $result_menu->fetch_assoc()) {
                $menu_items[] = $row;
            }
        }
        $stmt_menu->close();
    }

} else {
    // Jika tidak ada ID di URL atau ID tidak valid
    // Kita bisa mengarahkan kembali ke halaman utama
    header("Location: index.php");
    exit();
}

// Menutup koneksi database setelah semua data diambil
$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $canteen ? htmlspecialchars($canteen['name']) : 'Detail Kantin'; ?> - Mycanteen</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f0f2f5;
        }
    </style>
</head>
<body>
    <header class="navbar navbar-expand-lg navbar-light bg-white shadow-sm rounded-bottom">
        <div class="container-fluid">
            <a class="navbar-brand text-primary fw-bold" href="#">
                Mycanteen
                <span class="d-block text-secondary fs-6 fw-normal">Food Delivery</span>
            </a>
            <div class="d-flex align-items-center">
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
        <?php if ($canteen): /* cite: 2 */?>
            <section class="bg-white rounded-lg shadow-md overflow-hidden mb-4">
                <img src="<?php echo htmlspecialchars($canteen['image_url']); /* cite: 2 */?>" alt="Gambar Kantin <?php echo htmlspecialchars($canteen['name']); /* cite: 2 */?>" class="w-100 object-cover" style="height: 256px;">
                <div class="p-4">
                    <h1 class="h3 fw-bold text-gray-900 mb-2"><?php echo htmlspecialchars($canteen['name']); /* cite: 2 */?></h1>
                    <div class="d-flex align-items-center text-warning mb-3">
                        <span class="fs-5"><?php echo str_repeat('★', floor($canteen['rating'])); /* cite: 2 */?><?php echo ($canteen['rating'] - floor($canteen['rating']) > 0) ? '½' : ''; /* cite: 2 */?></span>
                        <span class="ms-2 text-muted fs-6">(<?php echo htmlspecialchars($canteen['reviews']); /* cite: 2 */?> Ulasan)</span>
                    </div>
                    <p class="text-gray-700 mb-4">
                        Kantin ini menyajikan berbagai hidangan lezat dengan kualitas terbaik.
                    </p>
                </div>
            </section>

            <section>
                <h2 class="h4 fw-semibold text-gray-800 mb-3">Daftar Menu</h2>
                <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                    <?php if (!empty($menu_items)): ?>
                        <?php foreach ($menu_items as $menu): /* cite: 2 */?>
                            <div class="col">
                                <div class="card shadow-sm h-100 d-flex flex-row transition-transform-hover">
                                    <img src="<?php echo htmlspecialchars($menu['image_url']); /* cite: 2 */?>" alt="Gambar <?php echo htmlspecialchars($menu['name']); /* cite: 2 */?>" class="w-40 object-cover" style="width: 40%; height: auto;">
                                    <div class="card-body d-flex flex-column justify-content-between flex-grow-1">
                                        <div>
                                            <h3 class="card-title h5 fw-medium text-gray-900 mb-1"><?php echo htmlspecialchars($menu['name']); /* cite: 2 */?></h3>
                                            <?php if (!empty($menu['description'])): /* cite: 2 */?>
                                                <p class="text-muted fs-6 mb-2"><?php echo htmlspecialchars($menu['description']); /* cite: 2 */?></p>
                                            <?php endif; ?>
                                            <p class="text-gray-600 fw-semibold fs-6">Rp<?php echo number_format($menu['price'], 0, ',', '.'); /* cite: 2 */?>,-</p>
                                        </div>
                                        <div class="mt-auto text-end">
                                            <form action="add_to_cart.php" method="post" class="d-inline-block">
                                                <input type="hidden" name="menu_id" value="<?php echo htmlspecialchars($menu['id']); /* cite: 2 */?>">
                                                <input type="hidden" name="quantity" value="1">
                                                <input type="hidden" name="redirect_to" value="canteen_detail.php?id=<?php echo htmlspecialchars($canteen['id']); /* cite: 2 */?>">
                                                <button type="submit" class="btn btn-primary btn-sm rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                                    <i class="bi bi-plus-lg fs-6"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted col-12">Belum ada menu yang tersedia untuk kantin ini.</p>
                    <?php endif; ?>
                </div>
            </section>
        <?php else: ?>
            <div class="bg-white rounded-lg shadow-md p-4 text-center">
                <h1 class="h4 fw-bold text-danger mb-3">Kantin Tidak Ditemukan</h1>
                <p class="text-gray-700">Maaf, kantin yang Anda cari tidak ada atau ID kantin tidak valid.</p>
                <a href="index.php" class="btn btn-primary mt-3">Kembali ke Beranda</a>
            </div>
        <?php endif; ?>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>