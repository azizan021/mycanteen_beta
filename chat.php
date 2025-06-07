<?php
// chat.php
session_start();
include 'db_connect.php'; // Memasukkan file koneksi database

$order_id = null;
$order_info = null;
$user_id = null;
$user_type = null; // 'customer' or 'courier'

// Determine user type and ID
if (isset($_SESSION['customer_logged_in']) && $_SESSION['customer_logged_in'] === true) {
    $user_id = $_SESSION['customer_id'];
    $user_type = 'customer';
} elseif (isset($_SESSION['courier_logged_in']) && $_SESSION['courier_logged_in'] === true) {
    $user_id = $_SESSION['courier_id'];
    $user_type = 'courier';
} else {
    // If not logged in as customer or courier, redirect to login
    header("Location: login.php");
    exit();
}

// Get order ID from URL
if (isset($_GET['order_id']) && is_numeric($_GET['order_id'])) {
    $order_id = intval($_GET['order_id']);

    // Fetch order information to verify access and display context
    $sql_order = "SELECT o.id, o.customer_id, o.courier_id, o.customer_name, o.delivery_location, o.status, c.name AS courier_name, cust.name AS customer_full_name
                  FROM orders o
                  LEFT JOIN couriers c ON o.courier_id = c.id
                  LEFT JOIN customers cust ON o.customer_id = cust.id
                  WHERE o.id = ?";
    $stmt_order = $conn->prepare($sql_order);
    $stmt_order->bind_param("i", $order_id);
    $stmt_order->execute();
    $result_order = $stmt_order->get_result();

    if ($result_order->num_rows > 0) {
        $order_info = $result_order->fetch_assoc();

        // Security check: Ensure the logged-in user is either the customer or the assigned courier for this order
        $is_authorized = false;
        if ($user_type === 'customer' && $order_info['customer_id'] == $user_id) {
            $is_authorized = true;
        } elseif ($user_type === 'courier') {
            // Kurir bisa akses chat jika dia yang ditugaskan ATAU pesanan masih pending (belum ada yang ambil)
            if ($order_info['courier_id'] == $user_id || $order_info['status'] === 'Pending') {
                $is_authorized = true;
            }
        }

        if (!$is_authorized) {
            // Unauthorized access to chat
            echo "<script>alert('Anda tidak memiliki akses ke chat ini.'); window.location.href='index.php';</script>";
            exit();
        }

    } else {
        // Order not found
        echo "<script>alert('Pesanan tidak ditemukan.'); window.location.href='index.php';</script>";
        exit();
    }
    $stmt_order->close();
} else {
    // No order ID provided
    echo "<script>alert('ID pesanan tidak diberikan.'); window.location.href='index.php';</script>";
    exit();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat Pesanan #<?php echo htmlspecialchars($order_id); ?> - Mycanteen</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f0f2f5;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        main {
            flex-grow: 1;
        }
        .chat-container {
            display: flex;
            flex-direction: column;
            height: calc(100vh - 150px); /* Adjust based on header/footer height */
            max-height: 800px; /* Max height for chat area */
        }
        .messages-box {
            flex-grow: 1;
            overflow-y: auto;
            border: 1px solid #e5e7eb; /* gray-200 */
            border-radius: 0.5rem; /* rounded-lg */
            background-color: #fff;
            padding: 1rem;
            display: flex;
            flex-direction: column; /* Messages stack from top */
        }
        .message {
            max-width: 70%;
            padding: 0.5rem 0.75rem;
            border-radius: 0.75rem; /* rounded-xl */
            margin-bottom: 0.5rem;
            word-wrap: break-word;
        }
        .message.self {
            background-color: #3b82f6; /* blue-500 */
            color: white;
            align-self: flex-end; /* Align to right */
        }
        .message.other {
            background-color: #e5e7eb; /* gray-200 */
            color: #1f2937; /* gray-800 */
            align-self: flex-start; /* Align to left */
        }
        .message-sender {
            font-size: 0.75rem; /* text-xs */
            color: #6b7280; /* gray-500 */
            margin-top: 0.25rem;
            text-align: right; /* For self messages */
        }
        .message.other .message-sender {
            text-align: left;
        }
    </style>
</head>
<body>
    <header class="bg-white shadow-sm py-4 px-6 flex items-center justify-between rounded-b-lg">
        <div class="text-blue-600 font-bold text-2xl">
            Mycanteen
            <span class="block text-gray-500 text-xs font-medium">Chat Pesanan</span>
        </div>
        <div class="flex items-center space-x-4">
            <a href="<?php echo $user_type === 'customer' ? 'customer_dashboard.php' : 'courier_dashboard.php'; ?>" class="text-gray-600 hover:text-blue-500">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
            </a>
            <?php if ($user_type === 'customer'): /* cite: 1 */?>
                <a href="customer_logout.php" class="text-gray-600 hover:text-red-500 text-sm">Logout</a>
            <?php elseif ($user_type === 'courier'): /* cite: 1 */?>
                <a href="courier_logout.php" class="text-gray-600 hover:text-red-500 text-sm">Logout</a>
            <?php endif; ?>
        </div>
    </header>

    <main class="container mx-auto px-4 py-8 flex-grow">
        <div class="bg-white rounded-lg shadow-md p-6 max-w-3xl mx-auto chat-container">
            <h1 class="text-2xl font-bold text-gray-900 mb-4 text-center">Chat untuk Pesanan #<?php echo htmlspecialchars($order_id); ?></h1>
            <p class="text-gray-600 text-center mb-4">
                Pelanggan: <?php echo htmlspecialchars($order_info['customer_full_name'] ?? $order_info['customer_name']); /* cite: 1 */?> |
                Kurir: <?php echo htmlspecialchars($order_info['courier_name'] ?? 'Belum Ditentukan'); /* cite: 1 */?>
            </p>

            <div id="messagesBox" class="messages-box">
                </div>

            <form id="chatForm" class="mt-4 flex">
                <input type="hidden" name="order_id" value="<?php echo htmlspecialchars($order_id); ?>">
                <input type="hidden" name="user_type" value="<?php echo htmlspecialchars($user_type); ?>">
                <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user_id); ?>">
                <textarea id="messageInput" name="message" class="form-input flex-grow mr-2 p-2 border rounded-md" placeholder="Ketik pesan Anda..." rows="1" required></textarea>
                <button type="submit" class="bg-blue-500 text-white px-6 py-2 rounded-full font-bold hover:bg-blue-600 transition-colors duration-200">Kirim</button>
            </form>
        </div>
    </main>

    <script>
        const messagesBox = document.getElementById('messagesBox');
        const chatForm = document.getElementById('chatForm');
        const messageInput = document.getElementById('messageInput');
        const orderId = chatForm.querySelector('input[name="order_id"]').value;
        const userType = chatForm.querySelector('input[name="user_type"]').value;
        const userId = chatForm.querySelector('input[name="user_id"]').value;

        let lastMessageId = 0; // To track the last message loaded

        function fetchMessages() {
            const xhr = new XMLHttpRequest();
            xhr.open('GET', 'fetch_messages.php?order_id=' + orderId + '&last_id=' + lastMessageId, true);
            xhr.onload = function() {
                if (xhr.status === 200) {
                    const response = JSON.parse(xhr.responseText);
                    if (response.status === 'success' && response.messages.length > 0) {
                        response.messages.forEach(msg => {
                            const messageElement = document.createElement('div');
                            messageElement.classList.add('message');
                            if (msg.sender_id == userId && msg.sender_type === userType) {
                                messageElement.classList.add('self');
                            } else {
                                messageElement.classList.add('other');
                            }
                            messageElement.innerHTML = `
                                <div>${msg.message}</div>
                                <div class="message-sender">${msg.sender_name} - ${new Date(msg.timestamp).toLocaleTimeString()}</div>
                            `;
                            messagesBox.appendChild(messageElement);
                            messagesBox.scrollTop = messagesBox.scrollHeight; // Scroll to bottom
                            lastMessageId = Math.max(lastMessageId, msg.id);
                        });
                    }
                }
            };
            xhr.send();
        }

        chatForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const messageText = messageInput.value.trim();
            if (messageText === '') return;

            const formData = new FormData(chatForm);
            formData.append('message', messageText);

            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'send_message.php', true);
            xhr.onload = function() {
                if (xhr.status === 200) {
                    const response = JSON.parse(xhr.responseText);
                    if (response.status === 'success') {
                        messageInput.value = ''; // Clear input
                        fetchMessages(); // Fetch new messages including the one just sent
                    } else {
                        alert('Gagal mengirim pesan: ' + response.message);
                    }
                } else {
                    alert('Terjadi kesalahan server saat mengirim pesan.');
                }
            };
            xhr.send(formData);
        });

        // Initial fetch and then poll every few seconds
        fetchMessages();
        setInterval(fetchMessages, 3000); // Poll every 3 seconds
    </script>
</body>
</html>