<?php
$page_title = "Order Confirmation";
require_once __DIR__ . '/includes/functions.php';
require_login();

$user = current_user();
$order_id = (int)($_GET['id'] ?? 0);

if (!$order_id) {
    header("Location: /orders.php");
    exit;
}

// Fetch order belonging to this user
$stmt = $pdo->prepare("
    SELECT o.*, oi.product_name, oi.diamonds_amount, oi.price as item_price, oi.quantity 
    FROM orders o 
    LEFT JOIN order_items oi ON o.id = oi.order_id 
    WHERE o.id = ? AND o.user_id = ?
");
$stmt->execute([$order_id, $user['id']]);
$order = $stmt->fetch();

if (!$order) {
    set_flash('error', 'Order not found.');
    header("Location: /orders.php");
    exit;
}

require_once __DIR__ . '/includes/header.php';

$status_info = match($order['order_status']) {
    'completed' => [
        'bg' => 'bg-emerald-950/60 border-emerald-500/50 text-emerald-300',
        'badge' => 'bg-emerald-950 text-emerald-400 border-emerald-800',
        'title' => 'ORDER COMPLETED & DELIVERED',
        'desc' => 'Your diamonds have been credited directly to your Free Fire account.'
    ],
    'processing' => [
        'bg' => 'bg-blue-950/60 border-blue-500/50 text-blue-300',
        'badge' => 'bg-blue-950 text-blue-400 border-blue-800',
        'title' => 'ORDER PROCESSING IN QUEUE',
        'desc' => 'Your order is currently in the automated delivery queue (approx 0-5 mins).'
    ],
    'cancelled' => [
        'bg' => 'bg-red-950/60 border-red-500/50 text-red-300',
        'badge' => 'bg-red-950 text-red-400 border-red-800',
        'title' => 'ORDER CANCELLED',
        'desc' => 'This order was cancelled. Please check admin notes or support.'
    ],
    default => [
        'bg' => 'bg-amber-950/60 border-amber-500/50 text-amber-300',
        'badge' => 'bg-amber-950 text-amber-400 border-amber-800',
        'title' => 'PAYMENT PENDING VERIFICATION',
        'desc' => 'Your manual payment is queued for admin review. It will be verified shortly.'
    ]
};
?>

<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-12 space-y-8">
    <!-- Success Banner Card -->
    <div class="rounded-2xl border p-6 md:p-8 text-center space-y-3 relative overflow-hidden <?php echo $status_info['bg']; ?>">
        <div class="w-14 h-14 rounded-full bg-black/40 border border-white/20 flex items-center justify-center mx-auto text-2xl font-bold">
            <?php echo $order['order_status'] === 'completed' ? '✓' : '⚡'; ?>
        </div>
        <h1 class="font-gaming text-2xl sm:text-3xl font-bold tracking-wider text-white">
            <?php echo $status_info['title']; ?>
        </h1>
        <p class="text-xs sm:text-sm max-w-lg mx-auto opacity-90">
            <?php echo $status_info['desc']; ?>
        </p>
        <div class="pt-2">
            <span class="inline-block px-3 py-1 rounded-full text-xs font-bold font-mono tracking-widest uppercase border <?php echo $status_info['badge']; ?>">
                Status: <?php echo e($order['order_status']); ?>
            </span>
        </div>
    </div>

    <!-- Receipt Details Card -->
    <div class="bg-gaming-850 border border-gaming-border rounded-2xl p-6 sm:p-8 space-y-6 shadow-2xl">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-4 border-b border-gaming-border gap-2">
            <div>
                <span class="text-xs text-zinc-400">Order Number</span>
                <div class="font-mono text-xl font-bold text-white"><?php echo e($order['order_number']); ?></div>
            </div>
            <div class="text-left sm:text-right">
                <span class="text-xs text-zinc-400">Order Placed</span>
                <div class="text-xs text-zinc-300"><?php echo date('M d, Y - h:i A', strtotime($order['created_at'])); ?></div>
            </div>
        </div>

        <!-- Target Player ID Info -->
        <div class="p-4 rounded-xl bg-gaming-900 border border-gaming-border space-y-2">
            <span class="text-[11px] font-bold text-red-400 uppercase tracking-wider block">Recipient Free Fire Account</span>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs">
                <div>
                    <span class="text-zinc-400 block">Player UID:</span>
                    <strong class="text-base font-mono text-white"><?php echo e($order['ff_uid']); ?></strong>
                </div>
                <div>
                    <span class="text-zinc-400 block">Player Nickname:</span>
                    <strong class="text-zinc-200"><?php echo e($order['ff_nickname'] ?: 'N/A'); ?></strong>
                </div>
                <div>
                    <span class="text-zinc-400 block">Server / Region:</span>
                    <strong class="text-zinc-200"><?php echo e($order['ff_region']); ?></strong>
                </div>
            </div>
        </div>

        <!-- Product Details -->
        <div>
            <span class="text-[11px] font-bold text-zinc-400 uppercase tracking-wider block mb-3">Order Items</span>
            <div class="p-4 rounded-xl bg-gaming-900 border border-gaming-border flex items-center justify-between">
                <div>
                    <h3 class="font-gaming text-base font-bold text-white"><?php echo e($order['product_name']); ?></h3>
                    <?php if ($order['diamonds_amount'] > 0): ?>
                        <p class="text-xs text-red-400 font-semibold"><?php echo $order['diamonds_amount']; ?> Total Diamonds</p>
                    <?php endif; ?>
                </div>
                <div class="text-right">
                    <span class="font-gaming text-lg font-bold text-white"><?php echo format_currency($order['total_amount']); ?></span>
                    <span class="block text-[10px] text-zinc-500 uppercase"><?php echo e($order['payment_method']); ?></span>
                </div>
            </div>
        </div>

        <!-- Payment Info -->
        <div class="p-4 rounded-xl bg-gaming-950 border border-gaming-border space-y-1.5 text-xs text-zinc-400">
            <div class="flex justify-between">
                <span>Payment Method:</span>
                <span class="font-semibold text-white capitalize"><?php echo e($order['payment_method']); ?></span>
            </div>
            <div class="flex justify-between">
                <span>Payment Status:</span>
                <span class="font-semibold <?php echo $order['payment_status'] === 'paid' ? 'text-emerald-400' : 'text-amber-400'; ?> uppercase">
                    <?php echo e($order['payment_status']); ?>
                </span>
            </div>
            <?php if (!empty($order['payment_reference'])): ?>
                <div class="flex justify-between">
                    <span>Reference / Note:</span>
                    <span class="font-mono text-zinc-300"><?php echo e($order['payment_reference']); ?></span>
                </div>
            <?php endif; ?>
        </div>

        <!-- Action Buttons -->
        <div class="pt-4 flex flex-col sm:flex-row gap-3 justify-center">
            <a href="/orders.php" class="btn-gaming-red text-white text-center font-gaming text-sm font-bold px-6 py-3 rounded-xl shadow-red-glow">
                TRACK IN MY ORDERS &rarr;
            </a>
            <a href="/products.php" class="px-6 py-3 rounded-xl bg-gaming-800 hover:bg-gaming-750 text-center text-xs font-semibold text-zinc-300 border border-gaming-border">
                Continue Shopping
            </a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
