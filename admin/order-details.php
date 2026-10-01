<?php
$page_title = "Admin - Order Details";
require_once __DIR__ . '/includes/admin_header.php';

$order_id = (int)($_GET['id'] ?? 0);
if (!$order_id) {
    header("Location: /admin/orders.php");
    exit;
}

$stmt = $pdo->prepare("
    SELECT o.*, u.name as customer_name, u.username, u.email as customer_email, u.phone as customer_phone, u.wallet_balance,
           oi.product_name, oi.diamonds_amount, oi.price as item_price, oi.quantity 
    FROM orders o 
    LEFT JOIN users u ON o.user_id = u.id 
    LEFT JOIN order_items oi ON o.id = oi.order_id 
    WHERE o.id = ?
");
$stmt->execute([$order_id]);
$order = $stmt->fetch();

if (!$order) {
    set_flash('error', "Order #$order_id not found.");
    header("Location: /admin/orders.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validate();

    $new_order_status = sanitize($_POST['order_status'] ?? $order['order_status']);
    $new_payment_status = sanitize($_POST['payment_status'] ?? $order['payment_status']);
    $admin_notes = trim($_POST['admin_notes'] ?? '');

    $stmt_upd = $pdo->prepare("
        UPDATE orders 
        SET order_status = ?, payment_status = ?, admin_notes = ? 
        WHERE id = ?
    ");
    $stmt_upd->execute([$new_order_status, $new_payment_status, $admin_notes, $order_id]);

    set_flash('success', "Order #{$order['order_number']} updated successfully!");
    header("Location: /admin/order-details.php?id=" . $order_id);
    exit;
}

$status_badge = match($order['order_status']) {
    'completed' => 'bg-emerald-950 text-emerald-400 border-emerald-800',
    'processing' => 'bg-blue-950 text-blue-400 border-blue-800',
    'cancelled' => 'bg-red-950 text-red-400 border-red-800',
    default => 'bg-amber-950 text-amber-400 border-amber-800'
};
?>

<div class="max-w-5xl mx-auto space-y-6">
    <div class="flex items-center justify-between pb-4 border-b border-gaming-border">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="font-gaming text-2xl font-bold text-white tracking-wide">
                    MANAGE ORDER <span class="text-red-500 font-mono"><?php echo e($order['order_number']); ?></span>
                </h1>
                <span class="px-2.5 py-0.5 rounded text-xs font-bold uppercase border <?php echo $status_badge; ?>">
                    <?php echo e($order['order_status']); ?>
                </span>
            </div>
            <p class="text-xs text-zinc-400 mt-1">Placed on <?php echo date('M d, Y h:i A', strtotime($order['created_at'])); ?></p>
        </div>
        <a href="/admin/orders.php" class="text-xs font-semibold text-zinc-400 hover:text-white">
            &larr; Back to Orders
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Order & Recipient Information -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Free Fire Player UID Box -->
            <div class="bg-gaming-850 border border-gaming-border rounded-2xl p-6 space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="font-gaming text-sm font-bold text-red-400 uppercase tracking-wider flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-red-500"></span> Free Fire Recipient Account
                    </h3>
                    <span class="text-xs font-mono text-zinc-400">Target Account</span>
                </div>

                <div class="p-4 rounded-xl bg-gaming-900 border border-red-900/40 grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <span class="text-xs text-zinc-400 block mb-1">Player UID:</span>
                        <div class="font-mono text-xl font-bold text-white"><?php echo e($order['ff_uid']); ?></div>
                        <button type="button" onclick="navigator.clipboard.writeText('<?php echo e($order['ff_uid']); ?>'); alert('UID copied!');"
                                class="text-[10px] text-red-400 hover:underline mt-1">
                            📋 Copy Player UID
                        </button>
                    </div>
                    <div>
                        <span class="text-xs text-zinc-400 block mb-1">In-Game Nickname:</span>
                        <div class="text-sm font-semibold text-zinc-200"><?php echo e($order['ff_nickname'] ?: 'Not Provided'); ?></div>
                    </div>
                    <div>
                        <span class="text-xs text-zinc-400 block mb-1">Server Region:</span>
                        <div class="text-sm font-semibold text-zinc-200"><?php echo e($order['ff_region']); ?></div>
                    </div>
                </div>
            </div>

            <!-- Items Information -->
            <div class="bg-gaming-850 border border-gaming-border rounded-2xl p-6 space-y-4">
                <h3 class="font-gaming text-sm font-bold text-white uppercase tracking-wider">Item Details</h3>
                <div class="p-4 rounded-xl bg-gaming-900 border border-gaming-border flex items-center justify-between">
                    <div>
                        <h4 class="font-gaming text-base font-bold text-white"><?php echo e($order['product_name']); ?></h4>
                        <?php if ($order['diamonds_amount'] > 0): ?>
                            <span class="text-xs text-red-400 font-semibold"><?php echo $order['diamonds_amount']; ?> Diamonds Total</span>
                        <?php endif; ?>
                    </div>
                    <div class="text-right">
                        <span class="font-gaming text-lg font-bold text-white"><?php echo format_currency($order['total_amount']); ?></span>
                        <span class="text-xs text-zinc-500 block">Qty: 1</span>
                    </div>
                </div>
            </div>

            <!-- Customer Profile Snapshot -->
            <div class="bg-gaming-850 border border-gaming-border rounded-2xl p-6 space-y-3 text-xs">
                <h3 class="font-gaming text-sm font-bold text-white uppercase tracking-wider">Customer Snapshot</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-zinc-300">
                    <div>
                        <span class="text-zinc-500 block">Customer Name:</span>
                        <strong class="text-white"><?php echo e($order['customer_name']); ?></strong> (@<?php echo e($order['username']); ?>)
                    </div>
                    <div>
                        <span class="text-zinc-500 block">Email Address:</span>
                        <span><?php echo e($order['customer_email']); ?></span>
                    </div>
                    <div>
                        <span class="text-zinc-500 block">Phone:</span>
                        <span><?php echo e($order['customer_phone'] ?: 'N/A'); ?></span>
                    </div>
                    <div>
                        <span class="text-zinc-500 block">Wallet Balance:</span>
                        <strong class="text-emerald-400 font-gaming"><?php echo format_currency($order['wallet_balance']); ?></strong>
                    </div>
                </div>
            </div>
        </div>

        <!-- Order Management Form -->
        <div class="space-y-6">
            <div class="bg-gaming-850 border border-gaming-border rounded-2xl p-6 shadow-2xl space-y-5">
                <h3 class="font-gaming text-base font-bold text-white pb-3 border-b border-gaming-border">
                    UPDATE STATUS & NOTES
                </h3>

                <form method="POST" action="/admin/order-details.php?id=<?php echo $order['id']; ?>" class="space-y-4">
                    <?php echo csrf_field(); ?>

                    <div>
                        <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1">Order Fulfillment Status</label>
                        <select name="order_status" class="w-full px-3.5 py-2.5 rounded-xl bg-gaming-900 border border-gaming-border focus:border-red-500 text-sm text-white focus:outline-none">
                            <option value="pending" <?php echo $order['order_status'] === 'pending' ? 'selected' : ''; ?>>Pending (Awaiting Verification)</option>
                            <option value="processing" <?php echo $order['order_status'] === 'processing' ? 'selected' : ''; ?>>Processing (In Top-Up Queue)</option>
                            <option value="completed" <?php echo $order['order_status'] === 'completed' ? 'selected' : ''; ?>>Completed (Diamonds Delivered)</option>
                            <option value="cancelled" <?php echo $order['order_status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1">Payment Status</label>
                        <select name="payment_status" class="w-full px-3.5 py-2.5 rounded-xl bg-gaming-900 border border-gaming-border focus:border-red-500 text-sm text-white focus:outline-none">
                            <option value="unpaid" <?php echo $order['payment_status'] === 'unpaid' ? 'selected' : ''; ?>>Unpaid</option>
                            <option value="paid" <?php echo $order['payment_status'] === 'paid' ? 'selected' : ''; ?>>Paid</option>
                            <option value="failed" <?php echo $order['payment_status'] === 'failed' ? 'selected' : ''; ?>>Failed</option>
                            <option value="refunded" <?php echo $order['payment_status'] === 'refunded' ? 'selected' : ''; ?>>Refunded</option>
                        </select>
                    </div>

                    <div class="p-3 rounded-lg bg-gaming-900 text-xs space-y-1 text-zinc-400">
                        <div class="flex justify-between">
                            <span>Method:</span>
                            <span class="font-semibold text-white capitalize"><?php echo e($order['payment_method']); ?></span>
                        </div>
                        <?php if (!empty($order['payment_reference'])): ?>
                            <div>
                                <span>Ref / UTR:</span>
                                <span class="font-mono text-zinc-200 block break-all"><?php echo e($order['payment_reference']); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1">Admin Internal / Customer Note</label>
                        <textarea name="admin_notes" rows="4"
                                  class="w-full px-3.5 py-2.5 rounded-xl bg-gaming-900 border border-gaming-border focus:border-red-500 text-xs text-white focus:outline-none"
                                  placeholder="e.g. Recharged via UID top-up API. Confirmation ID: 948102..."><?php echo e($order['admin_notes']); ?></textarea>
                    </div>

                    <button type="submit" class="w-full btn-gaming-red text-white font-gaming text-sm font-bold py-3 rounded-xl shadow-red-glow">
                        SAVE ORDER UPDATES &rarr;
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
