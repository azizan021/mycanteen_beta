<?php
// db_connect.php

$servername = "localhost"; // Biasanya localhost untuk XAMPP
$username = "root";      // Username default XAMPP
$password = "";          // Password default XAMPP (kosong)
$dbname = "mycanteen_beta_db"; // Nama database Anda yang sudah kita sesuaikan

// Membuat koneksi menggunakan MySQLi
$conn = new mysqli($servername, $username, $password, $dbname);

// Memeriksa koneksi
if ($conn->connect_error) {
    die("Koneksi database gagal: " . $conn->connect_error);
}

// Opsional: Atur charset ke utf8mb4 jika ada masalah dengan karakter
$conn->set_charset("utf8mb4");

//echo "Koneksi database berhasil!"; // Anda bisa mengaktifkan ini untuk tes koneksi
?>