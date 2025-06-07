<?php
// update_cart.php
session_start();
include 'db_connect.php'; // Included for consistency, though not strictly needed for cart manipulation if all data is in session

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $menu_id = isset($_POST['menu_id']) ? intval($_POST['menu_id']) : 0;
    $action = $_POST['action'] ?? ''; // 'increase', 'decrease', 'remove'

    if ($menu_id > 0 && isset($_SESSION['cart'][$menu_id])) {
        if ($action === 'increase') {
            $_SESSION['cart'][$menu_id]['quantity']++;
        } elseif ($action === 'decrease') {
            $_SESSION['cart'][$menu_id]['quantity']--;
            if ($_SESSION['cart'][$menu_id]['quantity'] <= 0) {
                unset($_SESSION['cart'][$menu_id]); // Remove item if quantity drops to 0 or less
            }
        } elseif ($action === 'remove') {
            unset($_SESSION['cart'][$menu_id]);
        }
    }
}

$conn->close(); // Close database connection
header("Location: cart.php"); // Redirect back to the cart page
exit();
?>