<?php
// add_to_cart.php

session_start(); // HARUS dipanggil di awal setiap skrip yang menggunakan sesi

// Memasukkan file koneksi database (kita butuh data menu)
include 'db_connect.php';

// Pastikan keranjang di sesi sudah diinisialisasi
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = []; // Keranjang berupa array kosong
}

$menu_id = null;
$quantity = 1; // Default quantity 1

// Memeriksa apakah data menu_id dan quantity diterima dari form POST
if (isset($_POST['menu_id']) && is_numeric($_POST['menu_id'])) {
    $menu_id = intval($_POST['menu_id']);

    // Ambil detail menu dari database (untuk harga, nama, dll.)
    $sql_menu_item = "SELECT id, name, price, image_url FROM menu_items WHERE id = ?";
    $stmt = $conn->prepare($sql_menu_item);
    $stmt->bind_param("i", $menu_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $menu_item = $result->fetch_assoc();

        // Tambahkan produk ke keranjang atau perbarui kuantitas jika sudah ada
        if (isset($_SESSION['cart'][$menu_id])) {
            $_SESSION['cart'][$menu_id]['quantity'] += $quantity;
        } else {
            $_SESSION['cart'][$menu_id] = [
                'id' => $menu_item['id'],
                'name' => $menu_item['name'],
                'price' => $menu_item['price'],
                'image_url' => $menu_item['image_url'],
                'quantity' => $quantity
            ];
        }
        // echo "Produk berhasil ditambahkan ke keranjang!"; // Untuk debugging
    } else {
        // echo "Produk tidak ditemukan."; // Untuk debugging
    }
    $stmt->close();
} else {
    // echo "ID produk tidak valid atau tidak diberikan."; // Untuk debugging
}

$conn->close(); // Tutup koneksi database

// Redirect kembali ke halaman sebelumnya atau halaman cart
$redirect_to = isset($_POST['redirect_to']) ? $_POST['redirect_to'] : 'index.php';
header("Location: " . $redirect_to);
exit(); // Penting untuk menghentikan eksekusi skrip setelah redirect
?>