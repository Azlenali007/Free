<?php
$page_title = "Admin Dashboard";
require_once __DIR__ . '/includes/admin_header.php';

// Real MySQL Queries for Statistics (No mock/fake data!)
$total_users = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$total_products = (int)$pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$total_orders = (int)$pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$pending_orders = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE order_status = 'pending'")->fetchColumn();
$completed_orders = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE order_status = 'completed'")->fetchColumn();

// Real revenue from completed orders in MySQL
$total_revenue = (float)$pdo->query("SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE payment_status = 'paid' OR order_status = 'completed'")->fetchColumn();

// Recent orders query with user details
$recent_orders = $pdo->query("
    SELECT o.*, u.username, u.name as customer_name, oi.product_name 
    FROM orders o 
    LEFT JOIN users u ON o.user_id = u.id 
    LEFT JOIN order_items oi ON o.id = oi.order_id 
    ORDER BY o.id DESC 
    LIMIT 6
")->fetchAll();

// Pending wallet deposits
$pending_deposits = $pdo->query("
    SELECT wt.*, u.username, u.name as user_name 
    FROM wallet_transactions wt 
    LEFT JOIN users u ON wt.user_id = u.id 
    WHERE wt.status = 'pending' AND wt.type = 'credit' 
    ORDER BY wt.id DESC 
    LIMIT 5
")->fetchAll();
?>

<div class="space-y-8">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="font-gaming text-2xl sm:text-3xl font-bold text-white tracking-wide flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-red-500 animate-pulse"></span> STORE OVERVIEW & METRICS
            </h1>
            <p class="text-xs text-zinc-400 mt-1">Real-time database statistics and administrative controls</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="/admin/product-add.php" class="btn-gaming-red text-white text-xs font-gaming font-bold px-4 py-2.5 rounded-xl shadow-red-subtle flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span>Add Product</span>
            </a>
            <a href="/admin/orders.php" class="px-4 py-2.5 rounded-xl bg-gaming-800 hover:bg-gaming-750 text-xs font-semibold text-zinc-300 border border-gaming-border">
                Manage Orders
            </a>
        </div>
    </div>

    <!-- 6 Real Metric Cards -->
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
        <!-- Total Users -->
        <div class="bg-gaming-850 border border-gaming-border rounded-xl p-4 space-y-1">
            <span class="text-[11px] font-semibold text-zinc-400 uppercase">Total Users</span>
            <div class="font-gaming text-2xl font-bold text-white"><?php echo $total_users; ?></div>
            <a href="/admin/users.php" class="text-[10px] text-red-400 hover:underline block">Manage Users &rarr;</a>
        </div>

        <!-- Total Products -->
        <div class="bg-gaming-850 border border-gaming-border rounded-xl p-4 space-y-1">
            <span class="text-[11px] font-semibold text-zinc-400 uppercase">Active Catalog</span>
            <div class="font-gaming text-2xl font-bold text-white"><?php echo $total_products; ?></div>
            <a href="/admin/products.php" class="text-[10px] text-red-400 hover:underline block">Catalog Items &rarr;</a>
        </div>

        <!-- Total Orders -->
        <div class="bg-gaming-850 border border-gaming-border rounded-xl p-4 space-y-1">
            <span class="text-[11px] font-semibold text-zinc-400 uppercase">Total Orders</span>
            <div class="font-gaming text-2xl font-bold text-white"><?php echo $total_orders; ?></div>
            <a href="/admin/orders.php" class="text-[10px] text-red-400 hover:underline block">All Orders &rarr;</a>
        </div>

        <!-- Pending Orders -->
        <div class="bg-gaming-850 border border-amber-600/30 rounded-xl p-4 space-y-1">
            <span class="text-[11px] font-semibold text-amber-400 uppercase">Pending Orders</span>
            <div class="font-gaming text-2xl font-bold text-amber-400"><?php echo $pending_orders; ?></div>
            <a href="/admin/orders.php?status=pending" class="text-[10px] text-amber-400 hover:underline block">Requires Action &rarr;</a>
        </div>

        <!-- Completed Orders -->
        <div class="bg-gaming-850 border border-emerald-600/30 rounded-xl p-4 space-y-1">
            <span class="text-[11px] font-semibold text-emerald-400 uppercase">Completed</span>
            <div class="font-gaming text-2xl font-bold text-emerald-400"><?php echo $completed_orders; ?></div>
            <span class="text-[10px] text-zinc-500 block">Fulfilled orders</span>
        </div>

        <!-- Total Revenue -->
        <div class="bg-gradient-to-br from-gaming-850 to-gaming-900 border border-red-500/40 rounded-xl p-4 space-y-1 shadow-red-subtle">
            <span class="text-[11px] font-semibold text-red-400 uppercase">Total Revenue</span>
            <div class="font-gaming text-2xl font-extrabold text-white"><?php echo format_currency($total_revenue); ?></div>
            <span class="text-[10px] text-zinc-400 block">Gross Sales</span>
        </div>
    </div>

    <!-- Pending Wallet Deposits Requiring Admin Approval -->
    <?php if (!empty($pending_deposits)): ?>
        <div class="bg-gaming-850 border border-amber-500/40 rounded-2xl p-6 space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-gaming-border">
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-amber-400 animate-ping"></span>
                    <h3 class="font-gaming text-lg font-bold text-amber-300">PENDING WALLET DEPOSITS (APPROVAL NEEDED)</h3>
                </div>
                <a href="/admin/payments.php" class="text-xs text-amber-400 hover:underline font-semibold">View All Deposits &rarr;</a>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="text-zinc-400 border-b border-gaming-border pb-2 uppercase">
                            <th class="py-2 px-3">Txn ID</th>
                            <th class="py-2 px-3">User</th>
                            <th class="py-2 px-3">Amount</th>
                            <th class="py-2 px-3">Method / Reference</th>
                            <th class="py-2 px-3">Date</th>
                            <th class="py-2 px-3 text-right">Quick Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gaming-border/60">
                        <?php foreach ($pending_deposits as $dep): ?>
                            <tr>
                                <td class="py-3 px-3 font-mono font-bold text-white"><?php echo e($dep['transaction_id']); ?></td>
                                <td class="py-3 px-3 text-white font-semibold"><?php echo e($dep['username']); ?></td>
                                <td class="py-3 px-3 font-gaming font-bold text-emerald-400 text-sm"><?php echo format_currency($dep['amount']); ?></td>
                                <td class="py-3 px-3">
                                    <div class="text-zinc-300"><?php echo e($dep['payment_method']); ?></div>
                                    <div class="font-mono text-zinc-500 text-[11px]"><?php echo e($dep['reference_no']); ?></div>
                                </td>
                                <td class="py-3 px-3 text-zinc-400"><?php echo date('M d, H:i', strtotime($dep['created_at'])); ?></td>
                                <td class="py-3 px-3 text-right">
                                    <a href="/admin/payments.php?action=review&id=<?php echo $dep['id']; ?>" 
                                       class="px-3 py-1.5 rounded-lg bg-emerald-950 text-emerald-300 border border-emerald-700/50 hover:bg-emerald-900 font-bold">
                                        Review & Approve
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

    <!-- Recent Orders Table -->
    <div class="bg-gaming-850 border border-gaming-border rounded-2xl p-6 space-y-4 shadow-2xl">
        <div class="flex items-center justify-between pb-3 border-b border-gaming-border">
            <h2 class="font-gaming text-xl font-bold text-white tracking-wide">RECENT ORDERS</h2>
            <a href="/admin/orders.php" class="text-xs text-red-400 hover:text-red-300 font-semibold">View All Orders &rarr;</a>
        </div>

        <?php if (!empty($recent_orders)): ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-gaming-900 border-b border-gaming-border text-zinc-400 uppercase font-gaming">
                            <th class="py-3 px-3">Order ID</th>
                            <th class="py-3 px-3">Customer</th>
                            <th class="py-3 px-3">Product</th>
                            <th class="py-3 px-3">Free Fire UID</th>
                            <th class="py-3 px-3">Amount</th>
                            <th class="py-3 px-3">Status</th>
                            <th class="py-3 px-3">Date</th>
                            <th class="py-3 px-3 text-right">Manage</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gaming-border/60">
                        <?php foreach ($recent_orders as $ord): 
                            $status_badge = match($ord['order_status']) {
                                'completed' => 'bg-emerald-950/80 text-emerald-400 border-emerald-800/40',
                                'processing' => 'bg-blue-950/80 text-blue-400 border-blue-800/40',
                                'cancelled' => 'bg-red-950/80 text-red-400 border-red-800/40',
                                default => 'bg-amber-950/80 text-amber-400 border-amber-800/40'
                            };
                        ?>
                            <tr class="hover:bg-gaming-800/40 transition-colors">
                                <td class="py-3 px-3 font-mono font-bold text-white"><?php echo e($ord['order_number']); ?></td>
                                <td class="py-3 px-3">
                                    <span class="font-semibold text-white"><?php echo e($ord['customer_name'] ?: $ord['username']); ?></span>
                                    <span class="block text-[10px] text-zinc-500 font-mono">@<?php echo e($ord['username']); ?></span>
                                </td>
                                <td class="py-3 px-3 text-zinc-200"><?php echo e($ord['product_name']); ?></td>
                                <td class="py-3 px-3 font-mono text-red-400 font-bold"><?php echo e($ord['ff_uid']); ?></td>
                                <td class="py-3 px-3 font-gaming font-bold text-white text-sm"><?php echo format_currency($ord['total_amount']); ?></td>
                                <td class="py-3 px-3">
                                    <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold uppercase border <?php echo $status_badge; ?>">
                                        <?php echo e($ord['order_status']); ?>
                                    </span>
                                </td>
                                <td class="py-3 px-3 text-zinc-400"><?php echo date('M d, H:i', strtotime($ord['created_at'])); ?></td>
                                <td class="py-3 px-3 text-right">
                                    <a href="/admin/order-details.php?id=<?php echo $ord['id']; ?>" 
                                       class="px-2.5 py-1 rounded bg-gaming-800 hover:bg-gaming-750 text-red-400 border border-gaming-border font-semibold">
                                        Details &rarr;
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-8 text-zinc-500 text-xs">No orders placed yet.</div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
