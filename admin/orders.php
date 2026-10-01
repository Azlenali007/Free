<?php
$page_title = "Manage Orders";
require_once __DIR__ . '/includes/admin_header.php';

// Quick status change handler
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'quick_status') {
    csrf_validate();
    $order_id = (int)($_POST['order_id'] ?? 0);
    $new_status = sanitize($_POST['new_status'] ?? '');
    
    if ($order_id && in_array($new_status, ['pending', 'processing', 'completed', 'cancelled'])) {
        $payment_status = ($new_status === 'completed') ? 'paid' : null;
        if ($payment_status) {
            $stmt = $pdo->prepare("UPDATE orders SET order_status = ?, payment_status = ? WHERE id = ?");
            $stmt->execute([$new_status, $payment_status, $order_id]);
        } else {
            $stmt = $pdo->prepare("UPDATE orders SET order_status = ? WHERE id = ?");
            $stmt->execute([$new_status, $order_id]);
        }
        set_flash('success', "Order #$order_id status updated to " . strtoupper($new_status));
        header("Location: /admin/orders.php");
        exit;
    }
}

$status_filter = sanitize($_GET['status'] ?? 'all');
$search = sanitize($_GET['q'] ?? '');

$where = ["1=1"];
$params = [];

if (in_array($status_filter, ['pending', 'processing', 'completed', 'cancelled'])) {
    $where[] = "o.order_status = ?";
    $params[] = $status_filter;
}
if (!empty($search)) {
    $where[] = "(o.order_number LIKE ? OR o.ff_uid LIKE ? OR u.username LIKE ? OR u.name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sql = "
    SELECT o.*, u.username, u.name as customer_name, u.email as customer_email, oi.product_name, oi.diamonds_amount 
    FROM orders o 
    LEFT JOIN users u ON o.user_id = u.id 
    LEFT JOIN order_items oi ON o.id = oi.order_id 
    WHERE " . implode(' AND ', $where) . " 
    ORDER BY o.id DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();
?>

<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="font-gaming text-2xl sm:text-3xl font-bold text-white tracking-wide flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-red-500"></span> ORDER MANAGEMENT
            </h1>
            <p class="text-xs text-zinc-400 mt-1">Review Free Fire player IDs, verify payments, and fulfill diamonds</p>
        </div>
    </div>

    <!-- Filters & Search Toolbar -->
    <div class="bg-gaming-850 border border-gaming-border rounded-xl p-4 flex flex-col md:flex-row items-center justify-between gap-4">
        <form method="GET" action="/admin/orders.php" class="flex flex-wrap items-center gap-3 w-full md:w-auto flex-1">
            <input type="text" name="q" value="<?php echo e($search); ?>" 
                   placeholder="Search Order ID, UID, or Username..." 
                   class="px-3.5 py-2 rounded-lg bg-gaming-900 border border-gaming-border text-xs text-white focus:outline-none w-full sm:w-72">
            
            <select name="status" onchange="this.form.submit()"
                    class="px-3 py-2 rounded-lg bg-gaming-900 border border-gaming-border text-xs text-white focus:outline-none">
                <option value="all">All Statuses</option>
                <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                <option value="processing" <?php echo $status_filter === 'processing' ? 'selected' : ''; ?>>Processing</option>
                <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
            </select>

            <button type="submit" class="px-4 py-2 rounded-lg bg-gaming-800 hover:bg-gaming-750 text-xs font-semibold text-zinc-200 border border-gaming-border">
                Search
            </button>
            <?php if (!empty($search) || $status_filter !== 'all'): ?>
                <a href="/admin/orders.php" class="text-xs text-red-400 hover:underline">Reset</a>
            <?php endif; ?>
        </form>

        <span class="text-xs text-zinc-400 font-mono"><?php echo count($orders); ?> Orders</span>
    </div>

    <!-- Orders Table -->
    <div class="bg-gaming-850 border border-gaming-border rounded-2xl overflow-hidden shadow-2xl">
        <?php if (!empty($orders)): ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-gaming-900 border-b border-gaming-border text-zinc-400 uppercase font-gaming">
                            <th class="py-3.5 px-4">Order ID</th>
                            <th class="py-3.5 px-4">Customer</th>
                            <th class="py-3.5 px-4">Free Fire UID</th>
                            <th class="py-3.5 px-4">Product / Diamonds</th>
                            <th class="py-3.5 px-4">Amount</th>
                            <th class="py-3.5 px-4">Payment</th>
                            <th class="py-3.5 px-4">Status</th>
                            <th class="py-3.5 px-4 text-right">Quick Change & Details</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gaming-border/60">
                        <?php foreach ($orders as $ord): 
                            $status_badge = match($ord['order_status']) {
                                'completed' => 'bg-emerald-950/80 text-emerald-400 border-emerald-800/40',
                                'processing' => 'bg-blue-950/80 text-blue-400 border-blue-800/40',
                                'cancelled' => 'bg-red-950/80 text-red-400 border-red-800/40',
                                default => 'bg-amber-950/80 text-amber-400 border-amber-800/40'
                            };
                        ?>
                            <tr class="hover:bg-gaming-800/40 transition-colors">
                                <td class="py-3.5 px-4 font-mono font-bold text-white"><?php echo e($ord['order_number']); ?></td>
                                <td class="py-3.5 px-4">
                                    <span class="font-bold text-white block"><?php echo e($ord['customer_name'] ?: $ord['username']); ?></span>
                                    <span class="text-[10px] text-zinc-500 font-mono">@<?php echo e($ord['username']); ?></span>
                                </td>
                                <td class="py-3.5 px-4">
                                    <div class="font-mono font-bold text-red-400"><?php echo e($ord['ff_uid']); ?></div>
                                    <div class="text-[10px] text-zinc-500"><?php echo e($ord['ff_region']); ?></div>
                                </td>
                                <td class="py-3.5 px-4">
                                    <span class="text-zinc-200 font-semibold block"><?php echo e($ord['product_name']); ?></span>
                                    <?php if ($ord['diamonds_amount'] > 0): ?>
                                        <span class="text-[11px] text-emerald-400 font-semibold"><?php echo $ord['diamonds_amount']; ?> Diamonds</span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3.5 px-4 font-gaming font-bold text-white text-sm">
                                    <?php echo format_currency($ord['total_amount']); ?>
                                </td>
                                <td class="py-3.5 px-4">
                                    <span class="capitalize text-zinc-300 block"><?php echo e($ord['payment_method']); ?></span>
                                    <span class="text-[10px] uppercase font-bold <?php echo $ord['payment_status'] === 'paid' ? 'text-emerald-400' : 'text-amber-400'; ?>">
                                        <?php echo e($ord['payment_status']); ?>
                                    </span>
                                </td>
                                <td class="py-3.5 px-4">
                                    <span class="inline-block px-2.5 py-0.5 rounded text-[10px] font-bold uppercase border <?php echo $status_badge; ?>">
                                        <?php echo e($ord['order_status']); ?>
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <!-- Quick Status dropdown -->
                                        <form method="POST" action="/admin/orders.php" class="inline">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="action" value="quick_status">
                                            <input type="hidden" name="order_id" value="<?php echo $ord['id']; ?>">
                                            <select name="new_status" onchange="this.form.submit()" 
                                                    class="px-2 py-1 rounded bg-gaming-950 border border-gaming-border text-[11px] text-zinc-300 focus:outline-none">
                                                <option value="pending" <?php echo $ord['order_status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                <option value="processing" <?php echo $ord['order_status'] === 'processing' ? 'selected' : ''; ?>>Processing</option>
                                                <option value="completed" <?php echo $ord['order_status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                                <option value="cancelled" <?php echo $ord['order_status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                            </select>
                                        </form>

                                        <a href="/admin/order-details.php?id=<?php echo $ord['id']; ?>" 
                                           class="px-2.5 py-1 rounded bg-gaming-800 hover:bg-gaming-750 text-red-400 font-semibold border border-gaming-border">
                                            Details &rarr;
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-12 text-zinc-500 text-xs">
                No orders match your filter criteria.
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
