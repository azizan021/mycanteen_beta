<?php
// fetch_messages.php
session_start();
include 'db_connect.php'; // Memasukkan file koneksi database

header('Content-Type: application/json');

$response = ['status' => 'error', 'messages' => []];

$order_id = intval($_GET['order_id'] ?? 0);
$last_id = intval($_GET['last_id'] ?? 0); // For polling, only fetch messages newer than this ID

$user_id = null;
$user_type = null;

// Determine user type and ID for authorization
if (isset($_SESSION['customer_logged_in']) && $_SESSION['customer_logged_in'] === true) {
    $user_id = $_SESSION['customer_id'];
    $user_type = 'customer';
} elseif (isset($_SESSION['courier_logged_in']) && $_SESSION['courier_logged_in'] === true) {
    $user_id = $_SESSION['courier_id'];
    $user_type = 'courier';
}

if (!$user_id || !$user_type || $order_id <= 0) {
    $response['message'] = 'Tidak terautentikasi atau ID pesanan tidak valid.';
    echo json_encode($response);
    $conn->close();
    exit();
}

// Verify user's access to this order's chat
$sql_auth = "SELECT customer_id, courier_id, status FROM orders WHERE id = ?";
$stmt_auth = $conn->prepare($sql_auth);
$stmt_auth->bind_param("i", $order_id);
$stmt_auth->execute();
$result_auth = $stmt_auth->get_result();
$order_auth = $result_auth->fetch_assoc();
$stmt_auth->close();

if (!$order_auth ||
    ($user_type === 'customer' && $order_auth['customer_id'] != $user_id) ||
    ($user_type === 'courier' && $order_auth['courier_id'] != $user_id && $order_auth['status'] !== 'Pending') // Kurir bisa akses pending, tapi kalau sudah diambil kurir lain tidak bisa.
) {
    $response['message'] = 'Anda tidak memiliki akses ke chat ini.';
    echo json_encode($response);
    $conn->close();
    exit();
}

// Fetch messages
$sql_fetch = "SELECT cm.id, cm.sender_id, cm.sender_type, cm.message, cm.timestamp,
                     CASE
                         WHEN cm.sender_type = 'customer' THEN cust.name
                         WHEN cm.sender_type = 'courier' THEN c.name
                         ELSE 'Unknown'
                     END AS sender_name
              FROM chat_messages cm
              LEFT JOIN customers cust ON cm.sender_id = cust.id AND cm.sender_type = 'customer'
              LEFT JOIN couriers c ON cm.sender_id = c.id AND cm.sender_type = 'courier'
              WHERE cm.order_id = ? AND cm.id > ?
              ORDER BY cm.timestamp ASC";

$stmt_fetch = $conn->prepare($sql_fetch);
$stmt_fetch->bind_param("ii", $order_id, $last_id);
$stmt_fetch->execute();
$result_fetch = $stmt_fetch->get_result();

$messages = [];
while ($row = $result_fetch->fetch_assoc()) {
    $messages[] = $row;
}
$stmt_fetch->close();

$response['status'] = 'success';
$response['messages'] = $messages;

$conn->close();
echo json_encode($response);
?>