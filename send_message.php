<?php
// send_message.php
session_start();
include 'db_connect.php'; // Memasukkan file koneksi database

header('Content-Type: application/json');

$response = ['status' => 'error', 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_id = intval($_POST['order_id'] ?? 0);
    $sender_id_from_form = intval($_POST['user_id'] ?? 0); // ID pengirim dari form
    $sender_type_from_form = $_POST['user_type'] ?? '';    // Tipe pengirim dari form
    $message = trim($_POST['message'] ?? '');

    $logged_in_user_id = null;
    $logged_in_user_type = null;

    // Verify sender identity from session (security critical!)
    if (isset($_SESSION['customer_logged_in']) && $_SESSION['customer_logged_in'] === true && $_SESSION['customer_id'] == $sender_id_from_form && $sender_type_from_form === 'customer') {
        $logged_in_user_id = $_SESSION['customer_id'];
        $logged_in_user_type = 'customer';
    } elseif (isset($_SESSION['courier_logged_in']) && $_SESSION['courier_logged_in'] === true && $_SESSION['courier_id'] == $sender_id_from_form && $sender_type_from_form === 'courier') {
        $logged_in_user_id = $_SESSION['courier_id'];
        $logged_in_user_type = 'courier';
    }

    if (!$logged_in_user_id || !$logged_in_user_type || empty($message) || $order_id <= 0) {
        $response['message'] = 'Data tidak lengkap atau tidak terautentikasi.';
    } else {
        // Optional: Verify order and user's participation in that order again here for stronger security
        // (Similar to chat.php's access check)
        $sql_auth = "SELECT customer_id, courier_id, status FROM orders WHERE id = ?";
        $stmt_auth = $conn->prepare($sql_auth);
        $stmt_auth->bind_param("i", $order_id);
        $stmt_auth->execute();
        $result_auth = $stmt_auth->get_result();
        $order_auth = $result_auth->fetch_assoc();
        $stmt_auth->close();

        if (!$order_auth ||
            ($logged_in_user_type === 'customer' && $order_auth['customer_id'] != $logged_in_user_id) ||
            ($logged_in_user_type === 'courier' && $order_auth['courier_id'] != $logged_in_user_id && $order_auth['status'] !== 'Pending') // Kurir bisa chat pending, tapi kalau sudah diambil kurir lain tidak bisa.
        ) {
            $response['message'] = 'Anda tidak memiliki izin untuk mengirim pesan ke chat ini.';
            echo json_encode($response);
            $conn->close();
            exit();
        }

        $sql_insert = "INSERT INTO chat_messages (order_id, sender_id, sender_type, message) VALUES (?, ?, ?, ?)";
        $stmt_insert = $conn->prepare($sql_insert);
        $stmt_insert->bind_param("iiss", $order_id, $logged_in_user_id, $logged_in_user_type, $message);

        if ($stmt_insert->execute()) {
            $response['status'] = 'success';
            $response['message'] = 'Pesan berhasil dikirim.';
        } else {
            $response['message'] = 'Gagal menyimpan pesan: ' . $conn->error;
        }
        $stmt_insert->close();
    }
} else {
    $response['message'] = 'Metode request tidak valid.';
}

$conn->close();
echo json_encode($response);
?>