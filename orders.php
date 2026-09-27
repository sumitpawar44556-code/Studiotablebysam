<?php
require_once __DIR__ . '/inc/config.php';
require_once __DIR__ . '/inc/auth.php';
header('Content-Type: application/json; charset=utf-8');
$data = json_decode(file_get_contents('php://input'), true);
if (!$data || empty($data['customer']) || empty($data['items'])) { http_response_code(400); echo json_encode(['success'=>false,'message'=>'Customer and items are required.']); exit; }
$orders = read_orders();
$data['orderId'] = 'ST-' . date('YmdHis') . '-' . strtoupper(bin2hex(random_bytes(2)));
$data['status'] = 'Pending';
$data['timestamp'] = date('c');
$data['qty'] = 1;
$data['price'] = array_reduce($data['items'], function($sum, $item) { return $sum + ((float)($item['price'] ?? 0) * (int)($item['qty'] ?? 1)); }, 0);
$orders[] = $data;
if (!save_orders($orders)) { http_response_code(500); echo json_encode(['success'=>false,'message'=>'Could not save order. Check orders.json permissions.']); exit; }
echo json_encode(['success'=>true,'orderId'=>$data['orderId'],'status'=>$data['status']]);
