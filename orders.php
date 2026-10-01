<?php
$page_title = "My Free Fire Orders";
require_once __DIR__ . '/includes/functions.php';
require_login();

$user = current_user();
$status_filter = sanitize($_GET['status'] ?? 'all');

$where = ["o.user_id = ?"];
$params = [$user['id']];

if (in_array($status_filter, ['pending', 'processing', 'completed', 'failed', 'cancelled', 'refunded'])) {
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
        <a href="/orders.php?status=failed" 
           class="px-4 py-2 rounded-xl text-xs font-semibold transition-all <?php echo $status_filter === 'failed' ? 'bg-red-600 text-white shadow-red-subtle' : 'bg-gaming-850 text-zinc-400 hover:text-white border border-gaming-border'; ?>">
            Failed
        </a>
        <a href="/orders.php?status=refunded" 
           class="px-4 py-2 rounded-xl text-xs font-semibold transition-all <?php echo $status_filter === 'refunded' ? 'bg-purple-600 text-white shadow-red-subtle' : 'bg-gaming-850 text-zinc-400 hover:text-white border border-gaming-border'; ?>">
            Refunded
        </a>
    </div>

    <!-- Orders Content -->
    <?php if (empty($orders)): ?>
        <div class="text-center py-20 rounded-2xl bg-gaming-900 border border-gaming-border p-8">
            <div class="w-16 h-16 mx-auto rounded-full bg-red-950/60 border border-red-800/40 flex items-center justify-center text-red-500 mb-4">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
            </div>
            <h3 class="font-gaming text-xl font-bold text-white mb-2">No Orders Found</h3>
            <p class="text-xs text-zinc-400 max-w-sm mx-auto mb-6">You haven't placed any diamond top-up orders with this status yet.</p>
            <a href="/products.php" class="btn-gaming-red text-white text-xs font-gaming font-bold px-6 py-2.5 rounded-xl shadow-red-subtle">
                Browse Diamonds & Top-Up
            </a>
        </div>
    <?php else: ?>
        <!-- Mobile Cards Layout (Requirement 2 & 10: responsive card layout for mobile) -->
        <div class="grid grid-cols-1 gap-4 md:hidden">
            <?php foreach ($orders as $ord): 
                $badge = match($ord['order_status']) {
                    'completed' => 'bg-emerald-950 text-emerald-400 border-emerald-800',
                    'processing' => 'bg-blue-950 text-blue-400 border-blue-800',
                    'failed' => 'bg-red-950 text-red-400 border-red-800',
                    'cancelled' => 'bg-zinc-800 text-zinc-400 border-zinc-700',
                    'refunded' => 'bg-purple-950 text-purple-400 border-purple-800',
                    default => 'bg-amber-950 text-amber-400 border-amber-800'
                };
            ?>
                <div class="p-5 rounded-2xl bg-gaming-850 border border-gaming-border space-y-4">
                    <div class="flex items-start justify-between">
                        <div>
                            <span class="text-[11px] font-mono text-zinc-400 block"><?php echo date('M d, Y h:i A', strtotime($ord['created_at'])); ?></span>
                            <span class="font-mono text-sm font-bold text-white"><?php echo e($ord['order_number']); ?></span>
                        </div>
                        <span class="px-2.5 py-1 rounded text-[11px] font-bold uppercase border <?php echo $badge; ?>">
                            <?php echo e($ord['order_status']); ?>
                        </span>
                    </div>

                    <div class="p-3 rounded-xl bg-gaming-900 border border-gaming-border space-y-1 text-xs">
                        <div class="flex justify-between">
                            <span class="text-zinc-400">Item:</span>
                            <span class="font-bold text-white"><?php echo e($ord['product_name']); ?></span>
                        </div>
                        <?php if (!empty($ord['variant_name'])): ?>
                            <div class="flex justify-between">
                                <span class="text-zinc-400">Variant:</span>
                                <span class="text-zinc-200"><?php echo e($ord['variant_name']); ?></span>
                            </div>
                        <?php endif; ?>
                        <div class="flex justify-between">
                            <span class="text-zinc-400">Player UID:</span>
                            <span class="font-mono text-red-400"><?php echo e($ord['ff_uid']); ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-zinc-400">Amount:</span>
                            <span class="font-bold text-white"><?php echo format_currency($ord['total_amount']); ?></span>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <a href="/order-details.php?id=<?php echo $ord['id']; ?>" class="flex-1 text-center py-2 rounded-xl bg-gaming-800 hover:bg-gaming-750 text-xs font-semibold text-white border border-gaming-border">
                            Order Details
                        </a>
                        <a href="/invoice.php?id=<?php echo $ord['id']; ?>" class="px-3 py-2 rounded-xl bg-red-950/60 hover:bg-red-900 text-xs font-semibold text-red-300 border border-red-800/40">
                            Invoice
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Desktop Table Layout -->
        <div class="hidden md:block overflow-hidden rounded-2xl bg-gaming-850 border border-gaming-border">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-gaming-border bg-gaming-900 text-zinc-400 uppercase font-mono text-[10px]">
                        <th class="py-4 px-6">Order ID & Date</th>
                        <th class="py-4 px-4">Item & Variant</th>
                        <th class="py-4 px-4">Free Fire UID</th>
                        <th class="py-4 px-4">Amount</th>
                        <th class="py-4 px-4">Status</th>
                        <th class="py-4 px-6 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gaming-border/60">
                    <?php foreach ($orders as $ord): 
                        $badge = match($ord['order_status']) {
                            'completed' => 'bg-emerald-950 text-emerald-400 border-emerald-800',
                            'processing' => 'bg-blue-950 text-blue-400 border-blue-800',
                            'failed' => 'bg-red-950 text-red-400 border-red-800',
                            'cancelled' => 'bg-zinc-800 text-zinc-400 border-zinc-700',
                            'refunded' => 'bg-purple-950 text-purple-400 border-purple-800',
                            default => 'bg-amber-950 text-amber-400 border-amber-800'
                        };
                    ?>
                        <tr class="hover:bg-gaming-800/50 transition-colors">
                            <td class="py-4 px-6">
                                <span class="font-mono font-bold text-white block"><?php echo e($ord['order_number']); ?></span>
                                <span class="text-[11px] text-zinc-400"><?php echo date('M d, Y h:i A', strtotime($ord['created_at'])); ?></span>
                            </td>
                            <td class="py-4 px-4">
                                <span class="font-semibold text-white block"><?php echo e($ord['product_name']); ?></span>
                                <?php if (!empty($ord['variant_name'])): ?>
                                    <span class="text-[10px] text-zinc-400"><?php echo e($ord['variant_name']); ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="py-4 px-4">
                                <span class="font-mono text-red-400 font-bold"><?php echo e($ord['ff_uid']); ?></span>
                                <?php if (!empty($ord['ff_nickname'])): ?>
                                    <span class="text-[10px] text-zinc-400 block"><?php echo e($ord['ff_nickname']); ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="py-4 px-4 font-mono font-bold text-white">
                                <?php echo format_currency($ord['total_amount']); ?>
                            </td>
                            <td class="py-4 px-4">
                                <span class="inline-block px-2.5 py-0.5 rounded text-[11px] font-bold uppercase border <?php echo $badge; ?>">
                                    <?php echo e($ord['order_status']); ?>
                                </span>
                            </td>
                            <td class="py-4 px-6 text-right space-x-2">
                                <a href="/invoice.php?id=<?php echo $ord['id']; ?>" class="px-2.5 py-1.5 rounded-lg bg-gaming-800 hover:bg-gaming-750 text-zinc-300 hover:text-white border border-gaming-border">
                                    Invoice
                                </a>
                                <a href="/order-details.php?id=<?php echo $ord['id']; ?>" class="px-3 py-1.5 rounded-lg bg-red-600 hover:bg-red-700 text-white font-bold shadow-red-subtle">
                                    Details &rarr;
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
