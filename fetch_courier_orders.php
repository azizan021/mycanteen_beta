<?php
// fetch_courier_orders.php
session_start();
include 'db_connect.php';

header('Content-Type: application/json');

$response = [
    'status' => 'error',
    'message' => 'Unauthorized',
    'pending_orders' => [],
    'accepted_orders' => []
];

// Check if courier is logged in
if (!isset($_SESSION['courier_logged_in']) || $_SESSION['courier_logged_in'] !== true) {
    echo json_encode($response);
    exit();
}

$current_courier_id = $_SESSION['courier_id'];

// Fetch Pending orders (belum ada kurir yang mengambil)
$sql_pending = "SELECT id, customer_name, delivery_location, total_amount, order_date FROM orders WHERE status = 'Pending' AND courier_id IS NULL ORDER BY order_date ASC";
$result_pending = $conn->query($sql_pending);
if ($result_pending->num_rows > 0) {
    while ($row = $result_pending->fetch_assoc()) {
        $response['pending_orders'][] = $row;
    }
}

// Fetch Accepted orders (yang diambil oleh kurir ini)
$sql_accepted = "SELECT o.id, o.customer_name, o.delivery_location, o.total_amount, o.order_date
                 FROM orders o
                 WHERE o.status = 'Accepted' AND o.courier_id = ?
                 ORDER BY o.order_date ASC";
$stmt_accepted = $conn->prepare($sql_accepted);
$stmt_accepted->bind_param("i", $current_courier_id);
$stmt_accepted->execute();
$result_accepted = $stmt_accepted->get_result();
if ($result_accepted->num_rows > 0) {
    while ($row = $result_accepted->fetch_assoc()) {
        $response['accepted_orders'][] = $row;
    }
}
$stmt_accepted->close();

$response['status'] = 'success';
$response['message'] = 'Orders fetched successfully.';

$conn->close();
echo json_encode($response);
?>