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
    SELECT o.*, oi.product_id, oi.product_name, oi.diamonds_amount, oi.price as item_price, oi.quantity 
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

// Handle Review Submission (Requirement 9)
$review_success = false;
$review_error = '';

// Check if user already submitted a review for this order
$stmt_rev = $pdo->prepare("SELECT * FROM reviews WHERE order_id = ? AND user_id = ?");
$stmt_rev->execute([$order['id'], $user['id']]);
$existing_review = $stmt_rev->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_review') {
    csrf_validate();
    if ($order['order_status'] !== 'completed') {
        $review_error = "Reviews can only be submitted for completed orders.";
    } else {
        $rating = (int)($_POST['rating'] ?? 5);
        $review_text = sanitize($_POST['review_text'] ?? '');
        
        if ($rating < 1 || $rating > 5) {
            $review_error = "Please provide a valid rating between 1 and 5 stars.";
        } elseif (empty($review_text) || strlen($review_text) < 5) {
            $review_error = "Please write at least a short review (5+ characters).";
        } else {
            if ($existing_review) {
                $stmt_upd_rev = $pdo->prepare("UPDATE reviews SET rating = ?, review_text = ?, updated_at = NOW() WHERE id = ?");
                $stmt_upd_rev->execute([$rating, $review_text, $existing_review['id']]);
                set_flash('success', 'Your review has been updated!');
            } else {
                $stmt_ins_rev = $pdo->prepare("INSERT INTO reviews (product_id, user_id, order_id, rating, review_text) VALUES (?, ?, ?, ?, ?)");
                $stmt_ins_rev->execute([$order['product_id'], $user['id'], $order['id'], $rating, $review_text]);
                set_flash('success', 'Thank you! Your verified player review has been published.');
            }
            header("Location: /order-details.php?id=" . $order['id']);
            exit;
        }
    }
}

require_once __DIR__ . '/includes/header.php';

$status_badge = match($order['order_status']) {
    'completed' => 'bg-emerald-950 text-emerald-400 border-emerald-800',
    'processing' => 'bg-blue-950 text-blue-400 border-blue-800',
    'failed' => 'bg-red-950 text-red-400 border-red-800',
    'cancelled' => 'bg-zinc-800 text-zinc-400 border-zinc-700',
    'refunded' => 'bg-purple-950 text-purple-400 border-purple-800',
    default => 'bg-amber-950 text-amber-400 border-amber-800'
};
?>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-8">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-gaming-border">
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
        <div class="flex items-center gap-3">
            <a href="/invoice.php?id=<?php echo $order['id']; ?>" class="px-3 py-1.5 rounded-lg bg-gaming-800 hover:bg-gaming-750 text-xs font-semibold text-white border border-gaming-border flex items-center gap-1.5 shadow-sm">
                <svg class="w-4 h-4 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                <span>Print Invoice</span>
            </a>
            <a href="/orders.php" class="text-xs font-semibold text-zinc-400 hover:text-white flex items-center gap-1">
                &larr; All Orders
            </a>
        </div>
    </div>

    <!-- Order Timeline Visual -->
    <div class="bg-gaming-850 border border-gaming-border rounded-2xl p-6">
        <h3 class="font-gaming text-xs font-bold uppercase tracking-wider text-zinc-400 mb-6">Fulfillment Progress</h3>
        
        <?php
        $is_paid = ($order['payment_status'] === 'paid');
        $is_processing = in_array($order['order_status'], ['processing', 'completed']);
        $is_completed = ($order['order_status'] === 'completed');
        $is_failed = in_array($order['order_status'], ['failed', 'cancelled']);
        $is_refunded = ($order['order_status'] === 'refunded');

        $steps = [
            'Order Submitted' => true,
            'Payment Confirmed' => $is_paid,
            'Automated Delivery' => $is_processing,
            'Diamonds Credited' => $is_completed
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

            <!-- Product Items & Variant -->
            <div class="bg-gaming-850 border border-gaming-border rounded-2xl p-6 space-y-4">
                <h3 class="font-gaming text-sm font-bold text-red-400 uppercase tracking-wider">Item Details</h3>
                <div class="p-4 rounded-xl bg-gaming-900 border border-gaming-border flex items-center justify-between">
                    <div>
                        <h4 class="font-gaming text-base font-bold text-white"><?php echo e($order['product_name']); ?></h4>
                        <?php if (!empty($order['variant_name'])): ?>
                            <span class="text-xs text-zinc-400 block mt-0.5">Package Option: <strong class="text-zinc-200"><?php echo e($order['variant_name']); ?></strong></span>
                        <?php endif; ?>
                        <?php if ($order['diamonds_amount'] > 0): ?>
                            <p class="text-xs text-amber-400 font-semibold mt-1"><?php echo $order['diamonds_amount']; ?> Total Diamonds</p>
                        <?php endif; ?>
                    </div>
                    <div class="text-right">
                        <span class="font-gaming text-xl font-bold text-white"><?php echo format_currency($order['total_amount']); ?></span>
                        <span class="block text-[10px] text-zinc-500">Qty: 1</span>
                    </div>
                </div>
            </div>

            <!-- Review Section (Requirement 9: Product Reviews / Ratings) -->
            <?php if ($order['order_status'] === 'completed'): ?>
                <div class="bg-gaming-850 border border-gaming-border rounded-2xl p-6 space-y-4">
                    <h3 class="font-gaming text-sm font-bold text-white uppercase tracking-wider flex items-center gap-2">
                        <span>⭐</span>
                        <span>Rate & Review This Purchase</span>
                    </h3>

                    <?php if (!empty($review_error)): ?>
                        <div class="p-3 rounded-lg bg-red-950/60 border border-red-800 text-red-300 text-xs">
                            <?php echo e($review_error); ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="/order-details.php?id=<?php echo $order['id']; ?>" class="space-y-3">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="action" value="submit_review">
                        
                        <div>
                            <label class="block text-zinc-300 text-xs font-semibold mb-1">Your Rating</label>
                            <select name="rating" class="px-3 py-2 rounded-xl bg-gaming-900 border border-gaming-border text-amber-400 text-xs font-bold focus:outline-none">
                                <option value="5" <?php echo ($existing_review && $existing_review['rating'] == 5) ? 'selected' : ''; ?>>★★★★★ (5/5) - Outstanding Delivery</option>
                                <option value="4" <?php echo ($existing_review && $existing_review['rating'] == 4) ? 'selected' : ''; ?>>★★★★☆ (4/5) - Great Service</option>
                                <option value="3" <?php echo ($existing_review && $existing_review['rating'] == 3) ? 'selected' : ''; ?>>★★★☆☆ (3/5) - Average</option>
                                <option value="2" <?php echo ($existing_review && $existing_review['rating'] == 2) ? 'selected' : ''; ?>>★★☆☆☆ (2/5) - Slow</option>
                                <option value="1" <?php echo ($existing_review && $existing_review['rating'] == 1) ? 'selected' : ''; ?>>★☆☆☆☆ (1/5) - Unsatisfied</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-zinc-300 text-xs font-semibold mb-1">Your Review Feedback</label>
                            <textarea name="review_text" rows="3" required placeholder="Share your experience (e.g. Received diamonds in 2 minutes! Works perfectly!)" class="w-full px-3 py-2 rounded-xl bg-gaming-900 border border-gaming-border text-white text-xs focus:border-red-500 focus:outline-none"><?php echo e($existing_review['review_text'] ?? ''); ?></textarea>
                        </div>

                        <button type="submit" class="btn-gaming-red text-white text-xs font-bold px-5 py-2 rounded-xl shadow-red-subtle">
                            <?php echo $existing_review ? 'Update Review' : 'Submit Verified Review'; ?>
                        </button>
                    </form>
                </div>
            <?php endif; ?>

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
                        <span class="font-semibold text-white uppercase"><?php echo e($order['payment_method']); ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span>Payment Status:</span>
                        <span class="font-semibold uppercase <?php echo $order['payment_status'] === 'paid' ? 'text-emerald-400' : 'text-amber-400'; ?>">
                            <?php echo e($order['payment_status']); ?>
                        </span>
                    </div>
                    <?php if ($order['discount_amount'] > 0): ?>
                        <div class="flex justify-between text-emerald-400">
                            <span>Coupon Discount (<?php echo e($order['coupon_code']); ?>):</span>
                            <span class="font-mono">-<?php echo format_currency($order['discount_amount']); ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($order['payment_reference'])): ?>
                        <div class="pt-2 border-t border-gaming-border/60">
                            <span class="block text-[11px] text-zinc-500 mb-0.5">Reference ID:</span>
                            <span class="font-mono text-xs text-zinc-200 break-all"><?php echo e($order['payment_reference']); ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($order['provider_order_id'])): ?>
                        <div class="pt-2 border-t border-gaming-border/60">
                            <span class="block text-[11px] text-emerald-500 mb-0.5">Provider Delivery ID:</span>
                            <span class="font-mono text-xs text-emerald-400 break-all"><?php echo e($order['provider_order_id']); ?></span>
                        </div>
                    <?php endif; ?>
                    <div class="pt-3 border-t border-gaming-border flex justify-between items-baseline">
                        <span class="font-gaming font-bold text-sm text-white">Total Amount</span>
                        <span class="font-gaming text-xl font-extrabold text-red-400"><?php echo format_currency($order['total_amount']); ?></span>
                    </div>
                </div>

                <div class="pt-4 border-t border-gaming-border space-y-2">
                    <a href="/invoice.php?id=<?php echo $order['id']; ?>" class="w-full block text-center py-2.5 rounded-xl bg-gaming-800 hover:bg-gaming-750 text-xs font-semibold text-white border border-gaming-border">
                        View / Print Receipt
                    </a>
                    <a href="/ticket-create.php?subject=<?php echo urlencode('Inquiry regarding Order #' . $order['order_number']); ?>" 
                       class="w-full block text-center py-2.5 rounded-xl bg-gaming-900 hover:bg-gaming-800 text-xs font-semibold text-zinc-300 border border-gaming-border">
                        Contact Support for this Order
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
