<?php
$page_title = "My Free Fire Orders";
require_once __DIR__ . '/includes/functions.php';
require_login();

$user = current_user();
$status_filter = sanitize($_GET['status'] ?? 'all');

$where = ["o.user_id = ?"];
$params = [$user['id']];

if (in_array($status_filter, ['pending', 'processing', 'completed', 'cancelled'])) {
    $where[] = "o.order_status = ?";
    $params[] = $status_filter;
}

$sql = "
    SELECT o.*, oi.product_name, oi.diamonds_amount 
    FROM orders o 
    LEFT JOIN order_items oi ON o.id = oi.order_id 
    WHERE " . implode(' AND ', $where) . " 
    ORDER BY o.id DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-8">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="font-gaming text-3xl font-extrabold text-white tracking-wide flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-red-500"></span> MY FREE FIRE ORDERS
            </h1>
            <p class="text-xs text-zinc-400 mt-1">Track your diamond top-up history and delivery status</p>
        </div>
        <a href="/products.php" class="btn-gaming-red text-white text-xs font-gaming font-bold px-5 py-2.5 rounded-xl self-start sm:self-auto shadow-red-subtle">
            + New Top-Up
        </a>
    </div>

    <!-- Status Tabs -->
    <div class="flex flex-wrap items-center gap-2 border-b border-gaming-border pb-4">
        <a href="/orders.php" 
           class="px-4 py-2 rounded-xl text-xs font-semibold transition-all <?php echo $status_filter === 'all' ? 'bg-red-600 text-white shadow-red-subtle' : 'bg-gaming-850 text-zinc-400 hover:text-white border border-gaming-border'; ?>">
            All Orders
        </a>
        <a href="/orders.php?status=pending" 
           class="px-4 py-2 rounded-xl text-xs font-semibold transition-all <?php echo $status_filter === 'pending' ? 'bg-amber-600 text-white shadow-red-subtle' : 'bg-gaming-850 text-zinc-400 hover:text-white border border-gaming-border'; ?>">
            Pending
        </a>
        <a href="/orders.php?status=processing" 
           class="px-4 py-2 rounded-xl text-xs font-semibold transition-all <?php echo $status_filter === 'processing' ? 'bg-blue-600 text-white shadow-red-subtle' : 'bg-gaming-850 text-zinc-400 hover:text-white border border-gaming-border'; ?>">
            Processing
        </a>
        <a href="/orders.php?status=completed" 
           class="px-4 py-2 rounded-xl text-xs font-semibold transition-all <?php echo $status_filter === 'completed' ? 'bg-emerald-600 text-white shadow-red-subtle' : 'bg-gaming-850 text-zinc-400 hover:text-white border border-gaming-border'; ?>">
            Completed
        </a>
        <a href="/orders.php?status=cancelled" 
           class="px-4 py-2 rounded-xl text-xs font-semibold transition-all <?php echo $status_filter === 'cancelled' ? 'bg-zinc-700 text-white shadow-red-subtle' : 'bg-gaming-850 text-zinc-400 hover:text-white border border-gaming-border'; ?>">
            Cancelled
        </a>
    </div>

    <?php if (!empty($orders)): ?>
        <!-- Desktop Table View -->
        <div class="hidden md:block bg-gaming-850 border border-gaming-border rounded-2xl overflow-hidden shadow-2xl">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gaming-900 border-b border-gaming-border text-xs text-zinc-400 uppercase font-gaming">
                        <th class="py-4 px-4">Order ID</th>
                        <th class="py-4 px-4">Product / Item</th>
                        <th class="py-4 px-4">Player UID</th>
                        <th class="py-4 px-4">Amount</th>
                        <th class="py-4 px-4">Status</th>
                        <th class="py-4 px-4">Date</th>
                        <th class="py-4 px-4 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gaming-border/60 text-xs">
                    <?php foreach ($orders as $ord): 
                        $status_badge = match($ord['order_status']) {
                            'completed' => 'bg-emerald-950/80 text-emerald-400 border-emerald-800/40',
                            'processing' => 'bg-blue-950/80 text-blue-400 border-blue-800/40',
                            'cancelled' => 'bg-red-950/80 text-red-400 border-red-800/40',
                            default => 'bg-amber-950/80 text-amber-400 border-amber-800/40'
                        };
                    ?>
                        <tr class="hover:bg-gaming-800/40 transition-colors">
                            <td class="py-4 px-4 font-mono font-bold text-white"><?php echo e($ord['order_number']); ?></td>
                            <td class="py-4 px-4">
                                <div class="font-bold text-white"><?php echo e($ord['product_name']); ?></div>
                                <?php if ($ord['diamonds_amount'] > 0): ?>
                                    <div class="text-[11px] text-red-400 font-semibold"><?php echo $ord['diamonds_amount']; ?> Diamonds</div>
                                <?php endif; ?>
                            </td>
                            <td class="py-4 px-4 font-mono text-zinc-300">
                                <div><?php echo e($ord['ff_uid']); ?></div>
                                <div class="text-[10px] text-zinc-500"><?php echo e($ord['ff_region']); ?></div>
                            </td>
                            <td class="py-4 px-4 font-gaming font-bold text-white text-sm"><?php echo format_currency($ord['total_amount']); ?></td>
                            <td class="py-4 px-4">
                                <span class="inline-block px-2.5 py-1 rounded text-[10px] font-bold uppercase border <?php echo $status_badge; ?>">
                                    <?php echo e($ord['order_status']); ?>
                                </span>
                            </td>
                            <td class="py-4 px-4 text-zinc-400"><?php echo date('M d, Y H:i', strtotime($ord['created_at'])); ?></td>
                            <td class="py-4 px-4 text-right">
                                <a href="/order-details.php?id=<?php echo $ord['id']; ?>" 
                                   class="px-3 py-1.5 rounded-lg bg-gaming-800 hover:bg-gaming-750 text-red-400 hover:text-red-300 font-semibold border border-gaming-border transition-colors">
                                    View Details
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Mobile Card View (Strict Requirement: cards rather than forcing a large table on mobile) -->
        <div class="md:hidden space-y-4">
            <?php foreach ($orders as $ord): 
                $status_badge = match($ord['order_status']) {
                    'completed' => 'bg-emerald-950/80 text-emerald-400 border-emerald-800/40',
                    'processing' => 'bg-blue-950/80 text-blue-400 border-blue-800/40',
                    'cancelled' => 'bg-red-950/80 text-red-400 border-red-800/40',
                    default => 'bg-amber-950/80 text-amber-400 border-amber-800/40'
                };
            ?>
                <div class="bg-gaming-850 border border-gaming-border rounded-xl p-4 space-y-3">
                    <div class="flex items-center justify-between pb-2 border-b border-gaming-border/60">
                        <span class="font-mono text-xs font-bold text-white"><?php echo e($ord['order_number']); ?></span>
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase border <?php echo $status_badge; ?>">
                            <?php echo e($ord['order_status']); ?>
                        </span>
                    </div>

                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="font-gaming font-bold text-sm text-white"><?php echo e($ord['product_name']); ?></h4>
                            <?php if ($ord['diamonds_amount'] > 0): ?>
                                <span class="text-xs text-red-400 font-semibold"><?php echo $ord['diamonds_amount']; ?> Diamonds</span>
                            <?php endif; ?>
                        </div>
                        <span class="font-gaming text-base font-extrabold text-white"><?php echo format_currency($ord['total_amount']); ?></span>
                    </div>

                    <div class="text-xs text-zinc-400 space-y-1 bg-gaming-900/60 p-2.5 rounded-lg border border-gaming-border/40">
                        <div class="flex justify-between">
                            <span>UID:</span>
                            <span class="font-mono font-bold text-white"><?php echo e($ord['ff_uid']); ?> (<?php echo e($ord['ff_region']); ?>)</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Date:</span>
                            <span><?php echo date('M d, Y h:i A', strtotime($ord['created_at'])); ?></span>
                        </div>
                    </div>

                    <div class="pt-1">
                        <a href="/order-details.php?id=<?php echo $ord['id']; ?>" 
                           class="w-full block text-center py-2 rounded-lg bg-gaming-800 text-xs font-semibold text-red-400 border border-gaming-border hover:bg-gaming-750">
                            View Order Details &rarr;
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

    <?php else: ?>
        <div class="p-16 text-center rounded-2xl bg-gaming-850 border border-gaming-border space-y-3">
            <svg class="w-12 h-12 text-zinc-600 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
            </svg>
            <h3 class="font-gaming text-lg font-bold text-white">No orders found</h3>
            <p class="text-xs text-zinc-400">You don't have any orders matching the selected filter.</p>
            <a href="/products.php" class="inline-block mt-2 btn-gaming-red text-white text-xs font-bold font-gaming px-5 py-2.5 rounded-xl shadow-red-subtle">
                Browse Diamonds & Passes &rarr;
            </a>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
