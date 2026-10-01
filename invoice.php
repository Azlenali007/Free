<?php
require_once __DIR__ . '/includes/functions.php';
require_login();

$user = current_user();
$order_id = (int)($_GET['id'] ?? 0);

if (!$order_id) {
    header("Location: /orders.php");
    exit;
}

$stmt = $pdo->prepare("
    SELECT o.*, oi.product_id, oi.product_name, oi.diamonds_amount, oi.price as item_price, oi.quantity,
           u.name as customer_name, u.username as customer_username, u.email as customer_email,
           p.name as gateway_name
    FROM orders o 
    JOIN users u ON o.user_id = u.id
    LEFT JOIN order_items oi ON o.id = oi.order_id 
    LEFT JOIN payment_gateways p ON o.payment_method = p.code
    WHERE o.id = ? AND (o.user_id = ? OR " . (is_admin_logged_in() ? "1=1" : "0=1") . ")
");
$stmt->execute([$order_id, $user['id']]);
$order = $stmt->fetch();

if (!$order) {
    die("Invoice not found or unauthorized access.");
}

$site_name = get_setting('site_name', 'FireZone Store');
$support_email = get_setting('support_email', 'support@firezonestore.com');
$support_phone = get_setting('support_whatsapp', '+1 555 374 8391');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #<?php echo e($order['order_number']); ?> - <?php echo e($site_name); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: #fff !important; color: #000 !important; }
            .print-border { border-color: #ddd !important; }
            .print-bg { background-color: #f9f9f9 !important; }
        }
    </style>
</head>
<body class="bg-[#0a0a0f] text-zinc-100 min-h-screen py-8 px-4 sm:px-6">

<div class="max-w-3xl mx-auto">
    <!-- Top Action Bar -->
    <div class="no-print flex items-center justify-between mb-6 pb-4 border-b border-zinc-800">
        <a href="/order-details.php?id=<?php echo $order['id']; ?>" class="text-xs text-red-400 hover:text-red-300 font-mono">
            &larr; Back to Order Details
        </a>
        <div class="flex items-center gap-3">
            <button onclick="window.print()" class="px-4 py-2 rounded-lg bg-red-600 hover:bg-red-700 text-white font-bold text-xs flex items-center gap-2 shadow-lg transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                <span>Print / Save PDF</span>
            </button>
        </div>
    </div>

    <!-- Main Printable Invoice Card -->
    <div class="bg-[#12121a] print-bg border border-zinc-800 print-border rounded-2xl p-6 sm:p-10 shadow-2xl space-y-8">
        <!-- Invoice Header -->
        <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-6 pb-6 border-b border-zinc-800 print-border">
            <div class="space-y-1">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-red-600 flex items-center justify-center text-white font-black text-sm">
                        FZ
                    </div>
                    <span class="font-bold text-2xl tracking-wider text-white print:text-black">
                        <?php echo e($site_name); ?>
                    </span>
                </div>
                <p class="text-xs text-zinc-400 print:text-zinc-600">Official Free Fire Direct Top-Up Receipt</p>
                <p class="text-[11px] text-zinc-400 print:text-zinc-600"><?php echo e($support_email); ?> | <?php echo e($support_phone); ?></p>
            </div>

            <div class="sm:text-right space-y-1">
                <span class="text-xs uppercase font-mono tracking-widest text-red-500 font-bold">OFFICIAL INVOICE</span>
                <h2 class="text-xl font-mono font-extrabold text-white print:text-black"><?php echo e($order['order_number']); ?></h2>
                <p class="text-xs text-zinc-400 print:text-zinc-600">Date: <?php echo date('M d, Y h:i A', strtotime($order['created_at'])); ?></p>
                <div class="pt-1">
                    <span class="inline-block px-2.5 py-0.5 rounded text-[11px] font-mono font-bold uppercase <?php echo $order['payment_status'] === 'paid' ? 'bg-emerald-950 text-emerald-400 border border-emerald-800' : 'bg-amber-950 text-amber-400 border border-amber-800'; ?>">
                        Payment: <?php echo strtoupper(e($order['payment_status'])); ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Bill To & Game Delivery Info -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 text-xs">
            <div class="space-y-1 p-4 rounded-xl bg-[#0a0a0f] print-bg border border-zinc-800 print-border">
                <span class="text-[10px] uppercase font-mono text-zinc-400 font-bold block mb-1">Customer Details</span>
                <p class="font-bold text-white print:text-black text-sm"><?php echo e($order['customer_name']); ?></p>
                <p class="text-zinc-400 print:text-zinc-600">Username: <span class="font-mono text-zinc-200 print:text-black"><?php echo e($order['customer_username']); ?></span></p>
                <p class="text-zinc-400 print:text-zinc-600">Email: <?php echo e($order['customer_email']); ?></p>
            </div>

            <div class="space-y-1 p-4 rounded-xl bg-[#0a0a0f] print-bg border border-zinc-800 print-border">
                <span class="text-[10px] uppercase font-mono text-red-400 font-bold block mb-1">Delivery Destination</span>
                <p class="font-bold text-white print:text-black text-sm">Free Fire UID: <span class="font-mono text-red-400"><?php echo e($order['ff_uid']); ?></span></p>
                <?php if (!empty($order['ff_nickname'])): ?>
                    <p class="text-zinc-400 print:text-zinc-600">Player Nickname: <span class="text-zinc-200 print:text-black"><?php echo e($order['ff_nickname']); ?></span></p>
                <?php endif; ?>
                <p class="text-zinc-400 print:text-zinc-600">Server Region: <?php echo e($order['ff_region']); ?></p>
            </div>
        </div>

        <!-- Items Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-zinc-800 print-border text-zinc-400 uppercase font-mono text-[10px]">
                        <th class="py-3 px-2">Package Description</th>
                        <th class="py-3 px-2 text-center">Diamonds</th>
                        <th class="py-3 px-2 text-center">Qty</th>
                        <th class="py-3 px-2 text-right">Price</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-800/60 print-border">
                    <tr>
                        <td class="py-4 px-2">
                            <span class="font-bold text-white print:text-black text-sm block"><?php echo e($order['product_name']); ?></span>
                            <?php if (!empty($order['variant_name'])): ?>
                                <span class="text-[11px] text-zinc-400 print:text-zinc-600">Variant: <?php echo e($order['variant_name']); ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="py-4 px-2 text-center font-mono font-bold text-amber-400 print:text-black">
                            <?php echo e($order['diamonds_amount']); ?> 💎
                        </td>
                        <td class="py-4 px-2 text-center text-zinc-300 print:text-black">
                            1
                        </td>
                        <td class="py-4 px-2 text-right font-mono font-bold text-white print:text-black">
                            <?php echo format_currency($order['original_amount'] ?: $order['total_amount']); ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Totals Calculation -->
        <div class="flex flex-col sm:flex-row justify-between items-start gap-4 pt-4 border-t border-zinc-800 print-border text-xs">
            <div class="space-y-1 text-zinc-400 print:text-zinc-600">
                <p>Payment Method: <span class="text-zinc-200 print:text-black font-semibold uppercase"><?php echo e($order['gateway_name'] ?: $order['payment_method']); ?></span></p>
                <p>Order Status: <span class="text-zinc-200 print:text-black font-semibold uppercase"><?php echo e($order['order_status']); ?></span></p>
                <?php if (!empty($order['payment_reference'])): ?>
                    <p class="font-mono text-[11px]">Payment Ref: <?php echo e($order['payment_reference']); ?></p>
                <?php endif; ?>
                <?php if (!empty($order['provider_order_id'])): ?>
                    <p class="font-mono text-[11px] text-emerald-400 print:text-black">Provider Ref: <?php echo e($order['provider_order_id']); ?></p>
                <?php endif; ?>
            </div>

            <div class="w-full sm:w-64 space-y-2">
                <div class="flex items-center justify-between text-zinc-400 print:text-zinc-600">
                    <span>Subtotal:</span>
                    <span class="font-mono"><?php echo format_currency($order['original_amount'] ?: $order['total_amount']); ?></span>
                </div>
                <?php if ($order['discount_amount'] > 0): ?>
                    <div class="flex items-center justify-between text-emerald-400 print:text-black font-semibold">
                        <span>Coupon Discount (<?php echo e($order['coupon_code']); ?>):</span>
                        <span class="font-mono">-<?php echo format_currency($order['discount_amount']); ?></span>
                    </div>
                <?php endif; ?>
                <div class="flex items-center justify-between text-zinc-400 print:text-zinc-600">
                    <span>Delivery Fee:</span>
                    <span class="font-mono text-emerald-400 print:text-black">FREE ($0.00)</span>
                </div>
                <div class="flex items-center justify-between pt-2 border-t border-zinc-800 print-border text-sm font-bold">
                    <span class="text-white print:text-black">TOTAL PAID:</span>
                    <span class="text-red-500 font-mono text-lg"><?php echo format_currency($order['total_amount']); ?></span>
                </div>
            </div>
        </div>

        <!-- Footer Notice -->
        <div class="pt-6 border-t border-zinc-800 print-border text-center text-[11px] text-zinc-500 print:text-zinc-600 space-y-1">
            <p>Thank you for choosing <?php echo e($site_name); ?>. Your diamond recharge was delivered via direct Player UID.</p>
            <p>For inquiries or support, open a ticket at <?php echo e($_SERVER['HTTP_HOST'] ?? 'firezonestore.com'); ?>/tickets.php</p>
        </div>
    </div>
</div>

</body>
</html>
