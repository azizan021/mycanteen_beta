<?php
session_start();
include 'db_connect.php'; // Included for consistency

$cart_item_count = 0;
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cart_item_count += $item['quantity'];
    }
}

$canteens = [];
$sql_canteens = "SELECT id, name, rating, reviews, image_url FROM canteens"; // Fetches canteen data
$result_canteens = $conn->query($sql_canteens);

if ($result_canteens->num_rows > 0) {
    while($row = $result_canteens->fetch_assoc()) {
        $canteens[] = $row;
    }
}

$menu_items = [];
// MODIFIKASI: Tambahkan JOIN untuk mendapatkan nama kantin
$sql_menu_items = "SELECT mi.id, mi.canteen_id, mi.name, mi.description, mi.price, mi.image_url, c.name AS canteen_name
                   FROM menu_items mi
                   JOIN canteens c ON mi.canteen_id = c.id
                   LIMIT 6"; // Menambah limit untuk contoh
$result_menu_items = $conn->query($sql_menu_items);

if ($result_menu_items->num_rows > 0) {
    while($row = $result_menu_items->fetch_assoc()) {
        $menu_items[] = $row;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mycanteen - Food Delivery</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f0f2f5;
        }
        /* Custom styles to mimic GoFood-like elements */
        .navbar-gofood {
            background-color: #fff;
            box-shadow: 0 2px 4px rgba(0,0,0,.08);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        .search-bar-gofood {
            background-color: #f0f2f5;
            border-radius: 25px;
            padding: 8px 15px;
            display: flex;
            align-items: center;
            border: 1px solid #e0e0e0;
        }
        .search-bar-gofood input {
            border: none;
            background: transparent;
            width: 100%;
            outline: none;
            padding: 0 5px;
        }
        .category-item {
            text-align: center;
            cursor: pointer;
        }
        .category-item img {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 5px;
            border: 1px solid #e0e0e0;
        }
        .canteen-card img {
            height: 180px; /* Larger image for canteen */
            object-fit: cover;
        }
        .menu-card {
            display: flex;
            flex-direction: column; /* Stack image and text */
            height: 100%;
        }
        .menu-card img {
            height: 150px; /* Consistent height for menu images */
            object-fit: cover;
            width: 100%; /* Make image full width of card */
            border-top-left-radius: 8px;
            border-top-right-radius: 8px;
        }
        .menu-card .card-body {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 10px; /* Adjust padding */
        }
        .menu-card .card-title {
            font-size: 1rem; /* Smaller title for menu items */
            margin-bottom: 0;
        }
        .menu-card .text-muted {
            font-size: 0.85rem;
        }
        .menu-card .text-end {
            align-self: flex-end; /* Push button to bottom right */
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
            <div class="d-flex align-items-center ms-auto"> <?php if (isset($_SESSION['customer_logged_in']) && $_SESSION['customer_logged_in'] === true): ?>
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

                <?php if (isset($_SESSION['customer_logged_in']) && $_SESSION['customer_logged_in'] === true): ?>
                    <a href="customer_dashboard.php#orders" class="position-relative text-secondary me-3" aria-label="Pesanan Saya">
                        <i class="bi bi-receipt-cutoff fs-5"></i>
                    </a>
                <?php else: ?>
                    <a href="login.php" class="position-relative text-secondary me-3" aria-label="Pesanan Saya">
                        <i class="bi bi-receipt-cutoff fs-5"></i>
                    </a>
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
        <div class="mb-4">
            <div class="search-bar-gofood shadow-sm">
                <i class="bi bi-search text-muted me-2"></i>
                <input type="text" placeholder="Cari makanan atau kantin...">
            </div>
        </div>

        <section class="mb-4">
            <h2 class="h5 fw-bold text-gray-800 mb-3">Kantin Pilihan</h2>
            <div class="row row-cols-2 row-cols-md-3 row-cols-lg-4 g-3">
                <?php if (!empty($canteens)): /* cite: 1 */?>
                    <?php foreach ($canteens as $canteen): /* cite: 1 */?>
                        <div class="col">
                            <a href="canteen_detail.php?id=<?php echo htmlspecialchars($canteen['id']); /* cite: 1 */?>" class="text-decoration-none text-dark category-item">
                                <img src="<?php echo htmlspecialchars($canteen['image_url']); /* cite: 1 */?>" alt="<?php echo htmlspecialchars($canteen['name']); /* cite: 1 */?>" class="img-fluid">
                                <span class="fw-medium text-sm"><?php echo htmlspecialchars($canteen['name']); /* cite: 1 */?></span>
                            </a>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-muted col-12">Belum ada kantin yang tersedia.</p>
                <?php endif; ?>
            </div>
        </section>

        <section class="mb-4">
            <h2 class="h5 fw-bold text-gray-800 mb-3">Paling Laris di Mycanteen</h2>
            <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                <?php if (!empty($menu_items)): ?>
                    <?php foreach ($menu_items as $menu): /* cite: 1 */?>
                        <div class="col">
                            <div class="card shadow-sm rounded-lg overflow-hidden menu-card">
                                <img src="<?php echo htmlspecialchars($menu['image_url']); /* cite: 1 */?>" class="card-img-top" alt="Gambar <?php echo htmlspecialchars($menu['name']); /* cite: 1 */?>">
                                <div class="card-body">
                                    <div>
                                        <h3 class="card-title fw-medium text-gray-900 mb-1"><?php echo htmlspecialchars($menu['name']); /* cite: 1 */?></h3>
                                        <p class="text-muted mb-1">dari <?php echo htmlspecialchars($menu['canteen_name']); ?></p>
                                        <p class="card-text fw-semibold text-primary">Rp<?php echo number_format($menu['price'], 0, ',', '.'); /* cite: 1 */?>,-</p>
                                    </div>
                                    <div class="mt-auto text-end">
                                        <form action="add_to_cart.php" method="post" class="d-inline-block">
                                            <input type="hidden" name="menu_id" value="<?php echo htmlspecialchars($menu['id']); /* cite: 1 */?>">
                                            <input type="hidden" name="quantity" value="1">
                                            <button type="submit" class="btn btn-primary btn-sm rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                                <i class="bi bi-plus-lg"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-muted col-12">Belum ada menu favorit yang tersedia.</p>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>