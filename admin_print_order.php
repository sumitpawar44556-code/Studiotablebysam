<?php
require_once __DIR__ . '/inc/config.php';
require_once __DIR__ . '/inc/auth.php';
require_admin();
$orders = read_orders();
$order = $orders[(int)($_GET['i'] ?? -1)] ?? null;
if (!$order) exit('Order not found.');
$items = $order['items'] ?? [['name' => $order['name'] ?? 'Order item', 'qty' => 1, 'price' => $order['price'] ?? 0]];
?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Invoice <?= e($order['orderId'] ?? '') ?> - Studio Table by Sam</title>
	<style>
		:root{--accent:#e85d04;--ink:#211c18;--line:#eadfd5;--soft:#fbf7f2}
		*{box-sizing:border-box}body{margin:0;background:#eee8e1;color:var(--ink);font:14px Arial,sans-serif}
		.invoice{max-width:760px;margin:32px auto;background:#fff;padding:42px;box-shadow:0 14px 35px rgba(33,28,24,.14)}
		.brand{display:flex;align-items:center;gap:18px;border-bottom:3px solid var(--accent);padding-bottom:22px}.logo{width:76px;height:76px;object-fit:contain}.brand h1{margin:0;font:700 30px Georgia,serif}.brand p{margin:5px 0 0;color:#777;letter-spacing:1px;text-transform:uppercase;font-size:11px}
		.invoice-meta{display:flex;justify-content:space-between;gap:20px;margin:26px 0}.meta-block strong{display:block;color:#888;font-size:11px;text-transform:uppercase;letter-spacing:1px;margin-bottom:6px}.meta-block span{font-weight:700}.status{color:#fff;background:#e85d04;padding:5px 10px;border-radius:999px;font-size:12px}
		table{width:100%;border-collapse:collapse;margin:22px 0}th{background:var(--ink);color:#fff;text-align:left;padding:12px}td{padding:13px 12px;border-bottom:1px solid var(--line)}th:last-child,td:last-child{text-align:right}.qty{text-align:center}.summary{margin-left:auto;max-width:300px;background:var(--soft);padding:18px}.summary div{display:flex;justify-content:space-between;padding:6px 0}.summary .grand{border-top:2px solid var(--accent);margin-top:8px;padding-top:12px;font-size:20px;font-weight:700;color:var(--accent)}.thankyou{text-align:center;margin:35px 0 5px;color:#777}.print-button{display:block;margin:26px auto 0;border:0;background:var(--accent);color:#fff;padding:11px 22px;border-radius:5px;cursor:pointer;font-weight:700}@media print{body{background:#fff}.invoice{margin:0;box-shadow:none;max-width:none}.print-button{display:none}}
	</style>
</head>
<body>
	<main class="invoice">
		<header class="brand"><img class="logo" src="images/logofinal.jpg" alt="Studio Table logo"><div><h1>Studio Table by Sam</h1><p>Restaurant invoice</p></div></header>
		<section class="invoice-meta"><div class="meta-block"><strong>Order ID</strong><span><?= e($order['orderId'] ?? 'N/A') ?></span><br><strong style="margin-top:12px">Placed</strong><span><?= e($order['timestamp'] ?? 'N/A') ?></span></div><div class="meta-block"><strong>Customer</strong><span><?= e($order['customer'] ?? 'N/A') ?></span><br><strong style="margin-top:12px">Table / Payment</strong><span><?= e($order['tableNumber'] ?? 'N/A') ?> / <?= e($order['paymentOption'] ?? 'Cash') ?></span></div><div><span class="status"><?= e($order['status'] ?? 'Pending') ?></span></div></section>
		<table><thead><tr><th>Dish</th><th class="qty">Qty</th><th>Unit price</th><th>Total</th></tr></thead><tbody><?php foreach($items as $item): $qty=(int)($item['qty']??1); $price=(float)($item['price']??0); ?><tr><td><?= e($item['name']??'') ?></td><td class="qty"><?= $qty ?></td><td>Rs <?= number_format($price,2) ?></td><td>Rs <?= number_format($price*$qty,2) ?></td></tr><?php endforeach; ?></tbody></table>
		<section class="summary"><div><span>Subtotal</span><strong>Rs <?= number_format((float)($order['price']??0),2) ?></strong></div><div class="grand"><span>Total</span><strong>Rs <?= number_format((float)($order['price']??0),2) ?></strong></div></section>
		<p class="thankyou">Thank you for dining with us. We hope to see you again.</p><button class="print-button" onclick="window.print()">Print invoice</button>
	</main>
</body>
</html>
<?php require_once __DIR__.'/inc/config.php'; require_once __DIR__.'/inc/auth.php'; require_admin(); $orders=read_orders(); $o=$orders[(int)($_GET['i']??-1)]??null; if(!$o) exit('Order not found.'); ?><!doctype html><html><head><meta charset="utf-8"><title>Order <?=e($o['orderId']??'')?></title><style>body{font:16px Arial;padding:30px}.bill{max-width:650px;margin:auto;border:2px solid #e85d04;padding:25px}h1{color:#e85d04}table{width:100%;border-collapse:collapse}td,th{padding:10px;border-bottom:1px solid #ddd;text-align:left}@media print{button{display:none}}</style></head><body><div class="bill"><h1>Studio Table by Sam</h1><p><strong>Order ID:</strong> <?=e($o['orderId']??'')?> | <strong>Status:</strong> <?=e($o['status']??'Pending')?></p><p><strong>Customer:</strong> <?=e($o['customer']??'')?> | <strong>Table:</strong> <?=e($o['tableNumber']??'')?></p><table><tr><th>Item</th><th>Qty</th><th>Price</th></tr><?php foreach(($o['items']??[]) as $item):?><tr><td><?=e($item['name'])?></td><td><?=e($item['qty']??1)?></td><td>Rs <?=number_format((float)($item['price']??0),2)?></td></tr><?php endforeach;?></table><h2>Total: Rs <?=number_format((float)($o['price']??0),2)?></h2><button onclick="print()">Print</button></div></body></html>
