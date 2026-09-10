<?php
header('Content-Type: application/json');
include __DIR__ . '/inc/auth.php';

$data = json_decode(file_get_contents('php://input'), true);
if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}
$ordersFile = __DIR__ . '/orders.json';
$orders = [];
if (file_exists($ordersFile)) {
    $raw = file_get_contents($ordersFile);
    $orders = json_decode($raw, true) ?: [];
}
$data['orderId'] = 'ST-' . date('YmdHis') . '-' . strtoupper(bin2hex(random_bytes(2)));
$data['status'] = 'Pending';
$data['timestamp'] = date('c');
$orders[] = $data;
if (file_put_contents($ordersFile, json_encode($orders, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX) === false) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Unable to save the order. Check orders.json permissions.']);
    exit;
}
// audit order creation
append_audit('create', ['order' => $data]);
echo json_encode(['success' => true, 'message' => 'Order received', 'orderId' => $data['orderId'], 'status' => $data['status']]);
