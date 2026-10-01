<?php
$page_title = "Order Details";
require_once __DIR__ . '/includes/functions.php';
require_login();

$user = current_user();
$order_id = (int)($_GET['id'] ?? 0);

if (!$order_id) {
    header("Location: /orders.php");
    exit;
}

$stmt = $pdo->prepare("
    SELECT o.*, oi.product_name, oi.diamonds_amount, oi.price as item_price, oi.quantity 
    FROM orders o 
    LEFT JOIN order_items oi ON o.id = oi.order_id 
    WHERE o.id = ? AND o.user_id = ?
");
$stmt->execute([$order_id, $user['id']]);
$order = $stmt->fetch();

if (!$order) {
    set_flash('error', 'Order not found or access denied.');
    header("Location: /orders.php");
    exit;
}

require_once __DIR__ . '/includes/header.php';

$status_badge = match($order['order_status']) {
    'completed' => 'bg-emerald-950 text-emerald-400 border-emerald-800',
    'processing' => 'bg-blue-950 text-blue-400 border-blue-800',
    'cancelled' => 'bg-red-950 text-red-400 border-red-800',
    default => 'bg-amber-950 text-amber-400 border-amber-800'
};
?>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-8">
    <div class="flex items-center justify-between pb-4 border-b border-gaming-border">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="font-gaming text-2xl sm:text-3xl font-bold text-white tracking-wide">
                    ORDER <span class="text-red-500 font-mono"><?php echo e($order['order_number']); ?></span>
                </h1>
                <span class="px-2.5 py-1 rounded text-xs font-bold uppercase border <?php echo $status_badge; ?>">
                    <?php echo e($order['order_status']); ?>
                </span>
            </div>
            <p class="text-xs text-zinc-400 mt-1">Placed on <?php echo date('F d, Y \a\t h:i A', strtotime($order['created_at'])); ?></p>
        </div>
        <a href="/orders.php" class="text-xs font-semibold text-zinc-400 hover:text-white flex items-center gap-1">
            &larr; All Orders
        </a>
    </div>

    <!-- Order Timeline Visual -->
    <div class="bg-gaming-850 border border-gaming-border rounded-2xl p-6">
        <h3 class="font-gaming text-xs font-bold uppercase tracking-wider text-zinc-400 mb-6">Delivery Progress</h3>
        
        <?php
        $steps = [
            'Order Placed' => true,
            'Payment Verified' => $order['payment_status'] === 'paid',
            'Queue Processing' => in_array($order['order_status'], ['processing', 'completed']),
            'Delivered' => $order['order_status'] === 'completed'
        ];
        ?>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <?php foreach ($steps as $title => $active): ?>
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-xs shrink-0 <?php echo $active ? 'bg-red-600 text-white shadow-red-subtle' : 'bg-gaming-900 border border-gaming-border text-zinc-500'; ?>">
                        <?php echo $active ? '✓' : '•'; ?>
                    </div>
                    <div>
                        <p class="text-xs font-gaming font-bold <?php echo $active ? 'text-white' : 'text-zinc-500'; ?>"><?php echo e($title); ?></p>
                        <span class="text-[10px] <?php echo $active ? 'text-red-400' : 'text-zinc-600'; ?>"><?php echo $active ? 'Complete' : 'Pending'; ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Main Details -->
        <div class="md:col-span-2 space-y-6">
            <!-- Free Fire Account Information -->
            <div class="bg-gaming-850 border border-gaming-border rounded-2xl p-6 space-y-4">
                <h3 class="font-gaming text-sm font-bold text-red-400 uppercase tracking-wider flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-red-500"></span> Player UID Details
                </h3>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 p-4 rounded-xl bg-gaming-900 border border-gaming-border text-xs">
                    <div>
                        <span class="text-zinc-400 block mb-1">Player UID:</span>
                        <span class="font-mono text-base font-bold text-white"><?php echo e($order['ff_uid']); ?></span>
                    </div>
                    <div>
                        <span class="text-zinc-400 block mb-1">Player Nickname:</span>
                        <span class="text-sm font-semibold text-zinc-200"><?php echo e($order['ff_nickname'] ?: 'None Specified'); ?></span>
                    </div>
                    <div>
                        <span class="text-zinc-400 block mb-1">Server / Region:</span>
                        <span class="text-sm font-semibold text-zinc-200"><?php echo e($order['ff_region']); ?></span>
                    </div>
                </div>
            </div>

            <!-- Product Items -->
            <div class="bg-gaming-850 border border-gaming-border rounded-2xl p-6 space-y-4">
                <h3 class="font-gaming text-sm font-bold text-red-400 uppercase tracking-wider">Item Details</h3>
                <div class="p-4 rounded-xl bg-gaming-900 border border-gaming-border flex items-center justify-between">
                    <div>
                        <h4 class="font-gaming text-base font-bold text-white"><?php echo e($order['product_name']); ?></h4>
                        <?php if ($order['diamonds_amount'] > 0): ?>
                            <p class="text-xs text-red-400 font-semibold"><?php echo $order['diamonds_amount']; ?> Total Diamonds</p>
                        <?php endif; ?>
                    </div>
                    <div class="text-right">
                        <span class="font-gaming text-xl font-bold text-white"><?php echo format_currency($order['total_amount']); ?></span>
                        <span class="block text-[10px] text-zinc-500">Qty: 1</span>
                    </div>
                </div>
            </div>

            <?php if (!empty($order['admin_notes'])): ?>
                <div class="p-4 rounded-xl bg-blue-950/40 border border-blue-800/40 text-xs text-blue-200 space-y-1">
                    <span class="font-bold uppercase tracking-wider block text-blue-400">Store Administrator Note:</span>
                    <p><?php echo nl2br(e($order['admin_notes'])); ?></p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Sidebar Payment Summary & Support Action -->
        <div class="space-y-6">
            <div class="bg-gaming-850 border border-gaming-border rounded-2xl p-6 space-y-4">
                <h3 class="font-gaming text-sm font-bold uppercase tracking-wider text-white">Payment Summary</h3>

                <div class="space-y-2 text-xs text-zinc-400">
                    <div class="flex justify-between">
                        <span>Payment Method:</span>
                        <span class="font-semibold text-white capitalize"><?php echo e($order['payment_method']); ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span>Payment Status:</span>
                        <span class="font-semibold uppercase <?php echo $order['payment_status'] === 'paid' ? 'text-emerald-400' : 'text-amber-400'; ?>">
                            <?php echo e($order['payment_status']); ?>
                        </span>
                    </div>
                    <?php if (!empty($order['payment_reference'])): ?>
                        <div class="pt-2 border-t border-gaming-border/60">
                            <span class="block text-[11px] text-zinc-500 mb-0.5">Reference ID / Proof:</span>
                            <span class="font-mono text-xs text-zinc-200 break-all"><?php echo e($order['payment_reference']); ?></span>
                        </div>
                    <?php endif; ?>
                    <div class="pt-3 border-t border-gaming-border flex justify-between items-baseline">
                        <span class="font-gaming font-bold text-sm text-white">Total Amount</span>
                        <span class="font-gaming text-xl font-extrabold text-red-400"><?php echo format_currency($order['total_amount']); ?></span>
                    </div>
                </div>

                <div class="pt-4 border-t border-gaming-border space-y-2">
                    <a href="/ticket-create.php?subject=<?php echo urlencode('Inquiry regarding Order #' . $order['order_number']); ?>" 
                       class="w-full block text-center py-2.5 rounded-xl bg-gaming-800 hover:bg-gaming-750 text-xs font-semibold text-zinc-200 border border-gaming-border">
                        Contact Support for this Order
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
