<?php
$page_title = "Gamer Dashboard";
require_once __DIR__ . '/includes/functions.php';
require_login();

$user = current_user();

// Real Database Queries for Logged-In User
$stmt_total = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE user_id = ?");
$stmt_total->execute([$user['id']]);
$total_orders = (int)$stmt_total->fetchColumn();

$stmt_pending = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE user_id = ? AND order_status IN ('pending', 'processing')");
$stmt_pending->execute([$user['id']]);
$pending_orders = (int)$stmt_pending->fetchColumn();

$stmt_completed = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE user_id = ? AND order_status = 'completed'");
$stmt_completed->execute([$user['id']]);
$completed_orders = (int)$stmt_completed->fetchColumn();

// Recent orders from MySQL
$stmt_recent = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY id DESC LIMIT 5");
$stmt_recent->execute([$user['id']]);
$recent_orders = $stmt_recent->fetchAll();

// Quick Buy packs
$quick_packs = $pdo->query("SELECT * FROM products WHERE status = 'active' ORDER BY price ASC LIMIT 4")->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
    <!-- Welcome Header & Profile Summary -->
    <div class="bg-gaming-850 border border-gaming-border rounded-2xl p-6 relative overflow-hidden flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
        <div class="absolute -right-16 -top-16 w-56 h-56 bg-red-600/10 blur-3xl rounded-full pointer-events-none"></div>
        
        <div class="flex items-center gap-4">
            <div class="w-14 h-14 rounded-2xl bg-gradient-to-tr from-red-600 to-amber-500 p-0.5 shadow-red-subtle">
                <div class="w-full h-full bg-gaming-950 rounded-[14px] flex items-center justify-center font-gaming text-2xl font-bold text-white">
                    <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                </div>
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="font-gaming text-2xl font-bold text-white">
                        <?php echo e($user['name'] ?: $user['username']); ?>
                    </h1>
                    <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-red-950 text-red-400 border border-red-800/40 uppercase">Gamer</span>
                </div>
                <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-zinc-400 mt-1 font-mono">
                    <span>@<?php echo e($user['username']); ?></span>
                    <span>•</span>
                    <span>UID: <strong class="text-white"><?php echo e($user['ff_uid'] ?: 'Not configured'); ?></strong></span>
                    <span>•</span>
                    <span>Region: <?php echo e($user['ff_region'] ?: 'Global'); ?></span>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-3 w-full sm:w-auto">
            <a href="/profile.php" class="flex-1 sm:flex-initial text-center px-4 py-2 rounded-xl bg-gaming-800 hover:bg-gaming-750 text-xs font-semibold text-zinc-200 border border-gaming-border transition-colors">
                Edit Profile / UID
            </a>
            <a href="/wallet.php" class="flex-1 sm:flex-initial text-center btn-gaming-red text-white text-xs font-gaming font-bold px-5 py-2 rounded-xl shadow-red-subtle flex items-center justify-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                <span>Add Money</span>
            </a>
        </div>
    </div>

    <!-- 4 Real Database Metric Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <!-- Wallet Balance Card -->
        <div class="bg-gradient-to-br from-gaming-850 to-gaming-900 border border-emerald-500/30 rounded-2xl p-5 relative overflow-hidden group">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-semibold text-emerald-400 uppercase tracking-wider">Wallet Balance</span>
                <div class="w-8 h-8 rounded-lg bg-emerald-950/80 border border-emerald-800/40 flex items-center justify-center text-emerald-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                    </svg>
                </div>
            </div>
            <div class="font-gaming text-3xl font-extrabold text-white">
                <?php echo format_currency($user['wallet_balance']); ?>
            </div>
            <a href="/wallet.php" class="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-400 hover:text-emerald-300 mt-2">
                Deposit or Manage Funds &rarr;
            </a>
        </div>

        <!-- Total Orders Card -->
        <div class="bg-gaming-850 border border-gaming-border rounded-2xl p-5 relative overflow-hidden">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-semibold text-zinc-400 uppercase tracking-wider">Total Orders</span>
                <div class="w-8 h-8 rounded-lg bg-gaming-800 border border-gaming-border flex items-center justify-center text-zinc-300">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                    </svg>
                </div>
            </div>
            <div class="font-gaming text-3xl font-extrabold text-white">
                <?php echo $total_orders; ?>
            </div>
            <a href="/orders.php" class="inline-flex items-center gap-1 text-[11px] font-semibold text-zinc-400 hover:text-white mt-2">
                View All History &rarr;
            </a>
        </div>

        <!-- Pending Orders Card -->
        <div class="bg-gaming-850 border border-gaming-border rounded-2xl p-5 relative overflow-hidden">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-semibold text-amber-400 uppercase tracking-wider">Pending Orders</span>
                <div class="w-8 h-8 rounded-lg bg-amber-950/80 border border-amber-800/40 flex items-center justify-center text-amber-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <div class="font-gaming text-3xl font-extrabold text-amber-400">
                <?php echo $pending_orders; ?>
            </div>
            <span class="text-[11px] text-zinc-400 block mt-2">
                Processing in queue
            </span>
        </div>

        <!-- Completed Orders Card -->
        <div class="bg-gaming-850 border border-gaming-border rounded-2xl p-5 relative overflow-hidden">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-semibold text-red-400 uppercase tracking-wider">Completed Orders</span>
                <div class="w-8 h-8 rounded-lg bg-red-950/80 border border-red-800/40 flex items-center justify-center text-red-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <div class="font-gaming text-3xl font-extrabold text-white">
                <?php echo $completed_orders; ?>
            </div>
            <span class="text-[11px] text-zinc-400 block mt-2">
                Diamonds delivered
            </span>
        </div>
    </div>

    <!-- Quick Buy Section -->
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <h2 class="font-gaming text-xl font-bold text-white tracking-wide flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-red-500"></span> QUICK TOP-UP PACKS
            </h2>
            <a href="/products.php" class="text-xs font-semibold text-red-400 hover:text-red-300">Browse Full Catalog &rarr;</a>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <?php foreach ($quick_packs as $pack): ?>
                <div class="gaming-card p-4 rounded-xl border border-gaming-border flex items-center justify-between gap-3 group">
                    <div class="flex items-center gap-3 overflow-hidden">
                        <div class="w-12 h-12 rounded-lg bg-gaming-950 border border-gaming-border flex items-center justify-center p-1 shrink-0">
                            <img src="<?php echo e($pack['image'] ?: '/assets/images/diamonds.svg'); ?>" alt="" class="max-h-8 max-w-full">
                        </div>
                        <div class="overflow-hidden">
                            <h3 class="font-gaming text-sm font-bold text-white truncate"><?php echo e($pack['name']); ?></h3>
                            <span class="font-gaming text-red-400 font-bold text-xs"><?php echo format_currency($pack['price']); ?></span>
                        </div>
                    </div>
                    <a href="/checkout.php?product_id=<?php echo $pack['id']; ?>" 
                       class="btn-gaming-red text-white text-xs font-bold font-gaming px-3 py-1.5 rounded-lg shrink-0">
                        Buy
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Recent Orders Table / Responsive Card View -->
    <div class="bg-gaming-850 border border-gaming-border rounded-2xl p-6 space-y-4">
        <div class="flex items-center justify-between pb-4 border-b border-gaming-border">
            <div>
                <h2 class="font-gaming text-xl font-bold text-white tracking-wide">RECENT ORDERS</h2>
                <p class="text-xs text-zinc-400 mt-0.5">Real-time order statuses from your database</p>
            </div>
            <a href="/orders.php" class="text-xs font-semibold text-red-400 hover:text-red-300">View All Orders &rarr;</a>
        </div>

        <?php if (!empty($recent_orders)): ?>
            <!-- Desktop Table View -->
            <div class="hidden sm:block overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-gaming-border text-xs text-zinc-400 uppercase font-gaming">
                            <th class="py-3 px-3">Order ID</th>
                            <th class="py-3 px-3">Amount</th>
                            <th class="py-3 px-3">Free Fire UID</th>
                            <th class="py-3 px-3">Status</th>
                            <th class="py-3 px-3">Date</th>
                            <th class="py-3 px-3 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gaming-border/60 text-xs">
                        <?php foreach ($recent_orders as $ord): 
                            $status_class = match($ord['order_status']) {
                                'completed' => 'bg-emerald-950/80 text-emerald-400 border-emerald-800/40',
                                'processing' => 'bg-blue-950/80 text-blue-400 border-blue-800/40',
                                'cancelled' => 'bg-red-950/80 text-red-400 border-red-800/40',
                                default => 'bg-amber-950/80 text-amber-400 border-amber-800/40'
                            };
                        ?>
                            <tr class="hover:bg-gaming-800/50 transition-colors">
                                <td class="py-3 px-3 font-mono font-bold text-white"><?php echo e($ord['order_number']); ?></td>
                                <td class="py-3 px-3 font-gaming font-bold text-white"><?php echo format_currency($ord['total_amount']); ?></td>
                                <td class="py-3 px-3 font-mono text-zinc-300"><?php echo e($ord['ff_uid']); ?></td>
                                <td class="py-3 px-3">
                                    <span class="inline-block px-2.5 py-0.5 rounded text-[10px] font-bold uppercase border <?php echo $status_class; ?>">
                                        <?php echo e($ord['order_status']); ?>
                                    </span>
                                </td>
                                <td class="py-3 px-3 text-zinc-400"><?php echo date('M d, Y H:i', strtotime($ord['created_at'])); ?></td>
                                <td class="py-3 px-3 text-right">
                                    <a href="/order-details.php?id=<?php echo $ord['id']; ?>" class="text-xs text-red-400 hover:text-red-300 font-semibold underline underline-offset-2">
                                        Details
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Mobile Card View -->
            <div class="sm:hidden space-y-3">
                <?php foreach ($recent_orders as $ord): 
                    $status_class = match($ord['order_status']) {
                        'completed' => 'bg-emerald-950/80 text-emerald-400 border-emerald-800/40',
                        'processing' => 'bg-blue-950/80 text-blue-400 border-blue-800/40',
                        'cancelled' => 'bg-red-950/80 text-red-400 border-red-800/40',
                        default => 'bg-amber-950/80 text-amber-400 border-amber-800/40'
                    };
                ?>
                    <div class="p-3.5 rounded-xl bg-gaming-900 border border-gaming-border space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="font-mono text-xs font-bold text-white"><?php echo e($ord['order_number']); ?></span>
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase border <?php echo $status_class; ?>">
                                <?php echo e($ord['order_status']); ?>
                            </span>
                        </div>
                        <div class="flex items-center justify-between text-xs text-zinc-400">
                            <span>UID: <strong class="text-zinc-200"><?php echo e($ord['ff_uid']); ?></strong></span>
                            <span class="font-gaming font-bold text-white text-sm"><?php echo format_currency($ord['total_amount']); ?></span>
                        </div>
                        <div class="pt-2 border-t border-gaming-border/60 flex items-center justify-between text-[11px]">
                            <span class="text-zinc-500"><?php echo date('M d, Y', strtotime($ord['created_at'])); ?></span>
                            <a href="/order-details.php?id=<?php echo $ord['id']; ?>" class="text-red-400 font-semibold">View Details &rarr;</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-8 text-zinc-500 text-xs">
                No orders placed yet. Select a product to place your first top-up!
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
