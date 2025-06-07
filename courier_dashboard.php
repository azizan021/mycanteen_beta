<?php
// courier_dashboard.php
session_start();
include 'db_connect.php'; // Memasukkan file koneksi database

// Check if courier is logged in
if (!isset($_SESSION['courier_logged_in']) || $_SESSION['courier_logged_in'] !== true) {
    header("Location: login.php"); // Arahkan ke halaman login terpadu
    exit();
}

// Ambil ID kurir yang sedang login
$current_courier_id = $_SESSION['courier_id'];

// Handle order status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'])) {
    $order_id = intval($_POST['order_id']);
    $action = $_POST['action'] ?? '';

    // Mulai transaksi untuk memastikan integritas data
    $conn->begin_transaction();
    $success = false;

    try {
        if ($action === 'accept') {
            $sql_update = "UPDATE orders SET status = 'Accepted', courier_id = ? WHERE id = ? AND status = 'Pending' AND courier_id IS NULL";
            $stmt_update = $conn->prepare($sql_update);
            $stmt_update->bind_param("ii", $current_courier_id, $order_id);
            if (!$stmt_update->execute()) {
                throw new Exception($conn->error);
            }
            if ($stmt_update->affected_rows === 0) {
                // Jika tidak ada baris yang terpengaruh, berarti pesanan sudah diambil kurir lain atau status tidak pending
                throw new Exception("Pesanan sudah diambil oleh kurir lain atau tidak lagi pending.");
            }
            $stmt_update->close();
            $success = true;

        } elseif ($action === 'deliver') {
            $sql_update = "UPDATE orders SET status = 'Delivered' WHERE id = ? AND status = 'Accepted' AND courier_id = ?";
            $stmt_update = $conn->prepare($sql_update);
            $stmt_update->bind_param("ii", $order_id, $current_courier_id);
            if (!$stmt_update->execute()) {
                throw new Exception($conn->error);
            }
            $stmt_update->close();
            $success = true;
        }

        if ($success) {
            $conn->commit();
        } else {
             $conn->rollback();
        }

    } catch (Exception $e) {
        $conn->rollback();
    }
    $conn->close();
    exit();
}

$conn->close(); // Tutup koneksi jika tidak ada POST action
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Kurir - Mycanteen</title>
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
        .order-card-buttons {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
    </style>
</head>
<body>
    <header class="navbar navbar-expand-lg navbar-gofood">
        <div class="container py-2">
            <a class="navbar-brand text-primary fw-bold" href="#">
                Mycanteen
                <span class="d-block text-secondary fs-6 fw-normal">Kurir Dashboard</span>
            </a>
            <div class="d-flex align-items-center ms-auto">
                <a href="courier_logout.php" class="btn btn-outline-danger btn-sm">Logout</a>
            </div>
        </div>
    </header>

    <main class="container my-4">
        <h1 class="h3 fw-bold text-gray-900 mb-4">Daftar Pesanan</h1>

        <section class="mb-4">
            <h2 class="h5 fw-semibold text-gray-800 mb-3">Pesanan Menunggu Diterima</h2>
            <div id="pendingOrdersContainer" class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                <div class="col-12"><p class="text-muted">Memuat pesanan...</p></div>
            </div>
        </section>

        <section>
            <h2 class="h5 fw-semibold text-gray-800 mb-3">Pesanan Dalam Pengiriman (oleh Anda)</h2>
            <div id="acceptedOrdersContainer" class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                <div class="col-12"><p class="text-muted">Memuat pesanan...</p></div>
            </div>
        </section>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

    <script>
        const pendingOrdersContainer = document.getElementById('pendingOrdersContainer');
        const acceptedOrdersContainer = document.getElementById('acceptedOrdersContainer');

        // Fungsi untuk mengonversi data pesanan menjadi HTML card
        function createOrderCard(order, type) {
            let buttonsHtml = '';
            if (type === 'pending') {
                buttonsHtml = `
                    <form class="d-inline-block update-order-form">
                        <input type="hidden" name="order_id" value="${order.id}">
                        <input type="hidden" name="action" value="accept">
                        <button type="submit" class="btn btn-success btn-sm">
                            Terima Pesanan Ini
                        </button>
                    </form>
                `;
            } else if (type === 'accepted') {
                buttonsHtml = `
                    <form class="d-inline-block update-order-form">
                        <input type="hidden" name="order_id" value="${order.id}">
                        <input type="hidden" name="action" value="deliver">
                        <button type="submit" class="btn btn-primary btn-sm">
                            Tandai Selesai
                        </button>
                    </form>
                `;
            }

            return `
                <div class="col">
                    <div class="card shadow-sm p-3 h-100">
                        <h3 class="card-title h6 fw-medium text-gray-900 mb-2">Pesanan #${order.id}</h3>
                        <p class="card-text text-muted mb-1"><strong>Penerima:</strong> ${order.customer_name}</p>
                        <p class="card-text text-muted mb-1"><strong>Lokasi:</strong> ${order.delivery_location}</p>
                        <p class="card-text text-muted mb-3"><strong>Total:</strong> Rp${Number(order.total_amount).toLocaleString('id-ID')},-</p>
                        <div class="order-card-buttons mt-auto">
                            ${buttonsHtml}
                            <a href="courier_order_receipt.php?order_id=${order.id}" class="btn btn-outline-info btn-sm">Lihat Struk</a>
                        </div>
                    </div>
                </div>
            `;
        }

        // Fungsi untuk mengambil dan menampilkan pesanan
        async function fetchAndDisplayOrders() {
            try {
                const response = await fetch('fetch_courier_orders.php');
                const data = await response.json();

                if (data.status === 'success') {
                    // Update Pending Orders
                    pendingOrdersContainer.innerHTML = '';
                    if (data.pending_orders.length > 0) {
                        data.pending_orders.forEach(order => {
                            pendingOrdersContainer.innerHTML += createOrderCard(order, 'pending');
                        });
                    } else {
                        pendingOrdersContainer.innerHTML = '<div class="col-12"><p class="text-muted">Tidak ada pesanan menunggu saat ini.</p></div>';
                    }

                    // Update Accepted Orders
                    acceptedOrdersContainer.innerHTML = '';
                    if (data.accepted_orders.length > 0) {
                        data.accepted_orders.forEach(order => {
                            acceptedOrdersContainer.innerHTML += createOrderCard(order, 'accepted');
                        });
                    } else {
                        acceptedOrdersContainer.innerHTML = '<div class="col-12"><p class="text-muted">Tidak ada pesanan yang sedang Anda antar.</p></div>';
                    }

                    // Re-attach event listeners to new forms
                    attachFormEventListeners();

                } else {
                    console.error('Error fetching orders:', data.message);
                    pendingOrdersContainer.innerHTML = `<div class="col-12"><p class="text-danger">Gagal memuat pesanan: ${data.message}</p></div>`;
                    acceptedOrdersContainer.innerHTML = `<div class="col-12"><p class="text-danger">Gagal memuat pesanan: ${data.message}</p></div>`;
                }
            } catch (error) {
                console.error('Network error fetching orders:', error);
                pendingOrdersContainer.innerHTML = '<div class="col-12"><p class="text-danger">Gagal memuat pesanan (kesalahan jaringan).</p></div>';
                acceptedOrdersContainer.innerHTML = '<div class="col-12"><p class="text-danger">Gagal memuat pesanan (kesalahan jaringan).</p></div>';
            }
        }

        // Fungsi untuk melampirkan event listener ke form update
        function attachFormEventListeners() {
            document.querySelectorAll('.update-order-form').forEach(form => {
                form.onsubmit = async function(event) {
                    event.preventDefault(); // Mencegah refresh halaman
                    const formData = new FormData(this);

                    try {
                        const response = await fetch('courier_dashboard.php', {
                            method: 'POST',
                            body: formData
                        });
                        // Jika PHP mengembalikan JSON, Anda bisa parse di sini:
                        // const result = await response.json();
                        // if (result.status === 'success') {
                        //     alert(result.message);
                        // } else {
                        //     alert(result.message);
                        // }

                        fetchAndDisplayOrders(); // Perbarui daftar setelah aksi
                    } catch (error) {
                        console.error('Error updating order:', error);
                        alert('Terjadi kesalahan saat memperbarui pesanan.');
                    }
                };
            });
        }


        // Panggil fungsi saat halaman dimuat pertama kali
        fetchAndDisplayOrders();

        // Refresh setiap 1 detik
        setInterval(fetchAndDisplayOrders, 1000);
    </script>
</body>
</html>