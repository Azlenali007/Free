<?php
$page_title = "Sales & Financial Reports";
require_once __DIR__ . '/includes/admin_header.php';

// Period filtering
$period = sanitize($_GET['period'] ?? '7days');
$start_date = sanitize($_GET['start_date'] ?? '');
$end_date = sanitize($_GET['end_date'] ?? '');

$now = time();
switch ($period) {
    case 'today':
        $from = date('Y-m-d 00:00:00');
        $to = date('Y-m-d 23:59:59');
        $label = "Today (" . date('M d, Y') . ")";
        break;
    case 'yesterday':
        $from = date('Y-m-d 00:00:00', strtotime('-1 day'));
        $to = date('Y-m-d 23:59:59', strtotime('-1 day'));
        $label = "Yesterday (" . date('M d, Y', strtotime('-1 day')) . ")";
        break;
    case '30days':
        $from = date('Y-m-d 00:00:00', strtotime('-30 days'));
        $to = date('Y-m-d 23:59:59');
        $label = "Last 30 Days";
        break;
    case 'custom':
        $from = !empty($start_date) ? date('Y-m-d 00:00:00', strtotime($start_date)) : date('Y-m-d 00:00:00', strtotime('-7 days'));
        $to = !empty($end_date) ? date('Y-m-d 23:59:59', strtotime($end_date)) : date('Y-m-d 23:59:59');
        $label = "Custom: " . date('M d', strtotime($from)) . " - " . date('M d, Y', strtotime($to));
        break;
    case '7days':
    default:
        $from = date('Y-m-d 00:00:00', strtotime('-7 days'));
        $to = date('Y-m-d 23:59:59');
        $label = "Last 7 Days";
        break;
}

// 1. Total Orders & Status Breakdown in period
$stmt_orders = $pdo->prepare("
    SELECT 
        COUNT(*) as total_orders,
        COUNT(CASE WHEN order_status = 'completed' THEN 1 END) as completed_orders,
        COUNT(CASE WHEN order_status = 'pending' THEN 1 END) as pending_orders,
        COUNT(CASE WHEN order_status = 'failed' THEN 1 END) as failed_orders,
        COUNT(CASE WHEN order_status = 'refunded' THEN 1 END) as refunded_orders,
        COALESCE(SUM(CASE WHEN payment_status = 'paid' OR order_status = 'completed' THEN total_amount ELSE 0 END), 0) as gross_revenue,
        COALESCE(SUM(discount_amount), 0) as total_discounts
    FROM orders 
    WHERE created_at BETWEEN ? AND ?
");
$stmt_orders->execute([$from, $to]);
$stats = $stmt_orders->fetch();

$gross_revenue = (float)$stats['gross_revenue'];
$total_orders = (int)$stats['total_orders'];
$completed_orders = (int)$stats['completed_orders'];
$pending_orders = (int)$stats['pending_orders'];
$failed_orders = (int)$stats['failed_orders'];
$refunded_orders = (int)$stats['refunded_orders'];
$total_discounts = (float)$stats['total_discounts'];

// 2. Total Refunds in period
$stmt_ref = $pdo->prepare("
    SELECT COALESCE(SUM(amount), 0) as total_refunds, COUNT(*) as refund_count 
    FROM refunds 
    WHERE (status = 'completed' OR status = 'approved') AND created_at BETWEEN ? AND ?
");
$stmt_ref->execute([$from, $to]);
$ref_stats = $stmt_ref->fetch();
$total_refunds = (float)$ref_stats['total_refunds'];
$refund_count = (int)$ref_stats['refund_count'];

// 3. Net Revenue in ₹
$net_revenue = max(0, $gross_revenue - $total_refunds);

// 4. Wallet Deposits in period
$stmt_dep = $pdo->prepare("
    SELECT COALESCE(SUM(amount), 0) as total_deposits, COUNT(*) as deposit_count 
    FROM wallet_transactions 
    WHERE type = 'credit' AND status = 'completed' AND created_at BETWEEN ? AND ?
");
$stmt_dep->execute([$from, $to]);
$dep_stats = $stmt_dep->fetch();
$total_deposits = (float)$dep_stats['total_deposits'];
$deposit_count = (int)$dep_stats['deposit_count'];

// 5. Coupon usages in period
$stmt_cp = $pdo->prepare("
    SELECT COUNT(*) 
    FROM coupon_usage 
    WHERE used_at BETWEEN ? AND ?
");
$stmt_cp->execute([$from, $to]);
$coupon_usages = (int)$stmt_cp->fetchColumn();

// 6. Top Selling Products in period
$stmt_top = $pdo->prepare("
    SELECT oi.product_name, COUNT(oi.id) as units_sold, SUM(oi.subtotal) as total_sales 
    FROM order_items oi 
    JOIN orders o ON oi.order_id = o.id 
    WHERE (o.payment_status = 'paid' OR o.order_status = 'completed') 
      AND o.created_at BETWEEN ? AND ? 
    GROUP BY oi.product_name 
    ORDER BY total_sales DESC 
    LIMIT 10
");
$stmt_top->execute([$from, $to]);
$top_products = $stmt_top->fetchAll();

// 7. Daily breakdown in period
$stmt_daily = $pdo->prepare("
    SELECT 
        DATE(created_at) as sale_date,
        COUNT(*) as day_orders,
        SUM(CASE WHEN payment_status = 'paid' OR order_status = 'completed' THEN total_amount ELSE 0 END) as day_revenue 
    FROM orders 
    WHERE created_at BETWEEN ? AND ? 
    GROUP BY DATE(created_at) 
    ORDER BY sale_date DESC
");
$stmt_daily->execute([$from, $to]);
$daily_breakdown = $stmt_daily->fetchAll();
?>

<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="font-gaming text-2xl sm:text-3xl font-bold text-white tracking-wide flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-red-500"></span> SALES & REVENUE REPORTS
            </h1>
            <p class="text-xs text-zinc-400 mt-1">Real-time financial analytics, order performance, and net income in INR (₹)</p>
        </div>

        <!-- Print friendly button -->
        <button onclick="window.print()" class="px-4 py-2.5 rounded-xl bg-gaming-800 hover:bg-gaming-750 text-xs font-semibold text-zinc-300 border border-gaming-border inline-flex items-center gap-2">
            <svg class="w-4 h-4 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
            <span>Print Report</span>
        </button>
    </div>

    <!-- Period Filter Bar -->
    <div class="bg-gaming-850 border border-gaming-border rounded-2xl p-4 flex flex-col md:flex-row items-center justify-between gap-4 shadow-xl">
        <div class="flex items-center gap-2 overflow-x-auto w-full md:w-auto">
            <a href="/admin/reports.php?period=today" class="px-3.5 py-2 rounded-xl text-xs font-semibold transition-all <?php echo $period === 'today' ? 'bg-red-600 text-white shadow-red-subtle' : 'bg-gaming-900 text-zinc-400 hover:text-white'; ?>">
                Today
            </a>
            <a href="/admin/reports.php?period=yesterday" class="px-3.5 py-2 rounded-xl text-xs font-semibold transition-all <?php echo $period === 'yesterday' ? 'bg-red-600 text-white shadow-red-subtle' : 'bg-gaming-900 text-zinc-400 hover:text-white'; ?>">
                Yesterday
            </a>
            <a href="/admin/reports.php?period=7days" class="px-3.5 py-2 rounded-xl text-xs font-semibold transition-all <?php echo $period === '7days' ? 'bg-red-600 text-white shadow-red-subtle' : 'bg-gaming-900 text-zinc-400 hover:text-white'; ?>">
                Last 7 Days
            </a>
            <a href="/admin/reports.php?period=30days" class="px-3.5 py-2 rounded-xl text-xs font-semibold transition-all <?php echo $period === '30days' ? 'bg-red-600 text-white shadow-red-subtle' : 'bg-gaming-900 text-zinc-400 hover:text-white'; ?>">
                Last 30 Days
            </a>
        </div>

        <!-- Custom Date Range Form -->
        <form method="GET" action="/admin/reports.php" class="flex items-center gap-2 w-full md:w-auto">
            <input type="hidden" name="period" value="custom">
            <input type="date" name="start_date" value="<?php echo !empty($start_date) ? e($start_date) : date('Y-m-d', strtotime('-7 days')); ?>" 
                   class="px-3 py-1.5 rounded-xl bg-gaming-900 border border-gaming-border text-xs text-white focus:outline-none">
            <span class="text-zinc-500 text-xs">to</span>
            <input type="date" name="end_date" value="<?php echo !empty($end_date) ? e($end_date) : date('Y-m-d'); ?>" 
                   class="px-3 py-1.5 rounded-xl bg-gaming-900 border border-gaming-border text-xs text-white focus:outline-none">
            <button type="submit" class="px-3.5 py-1.5 rounded-xl bg-red-600 hover:bg-red-500 text-xs font-bold text-white transition-colors">Apply</button>
        </form>
    </div>

    <!-- Active Filter Notice -->
    <div class="flex items-center gap-2 text-xs text-zinc-400">
        <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
        <span>Showing financial performance for: <strong class="text-white"><?php echo e($label); ?></strong></span>
    </div>

    <!-- Financial KPI Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Net Revenue -->
        <div class="bg-gradient-to-br from-gaming-850 to-gaming-900 border border-emerald-500/40 rounded-2xl p-5 space-y-1 shadow-2xl">
            <span class="text-xs font-semibold text-emerald-400 uppercase tracking-wider">Net Realized Revenue</span>
            <div class="font-gaming text-3xl font-extrabold text-white"><?php echo format_currency($net_revenue); ?></div>
            <span class="text-[10px] text-zinc-400 block">Gross revenue minus processed refunds</span>
        </div>

        <!-- Gross Sales -->
        <div class="bg-gaming-850 border border-gaming-border rounded-2xl p-5 space-y-1">
            <span class="text-xs font-semibold text-zinc-400 uppercase tracking-wider">Gross Orders Volume</span>
            <div class="font-gaming text-3xl font-bold text-white"><?php echo format_currency($gross_revenue); ?></div>
            <span class="text-[10px] text-zinc-500 block"><?php echo $completed_orders; ?> completed orders</span>
        </div>

        <!-- Refunds Processed -->
        <div class="bg-gaming-850 border border-gaming-border rounded-2xl p-5 space-y-1">
            <span class="text-xs font-semibold text-red-400 uppercase tracking-wider">Total Refunds</span>
            <div class="font-gaming text-3xl font-bold text-red-400"><?php echo format_currency($total_refunds); ?></div>
            <span class="text-[10px] text-zinc-500 block"><?php echo $refund_count; ?> refunds returned to wallets</span>
        </div>

        <!-- Wallet Top-Up Deposits -->
        <div class="bg-gaming-850 border border-gaming-border rounded-2xl p-5 space-y-1">
            <span class="text-xs font-semibold text-amber-400 uppercase tracking-wider">Wallet Top-Ups</span>
            <div class="font-gaming text-3xl font-bold text-amber-300"><?php echo format_currency($total_deposits); ?></div>
            <span class="text-[10px] text-zinc-500 block"><?php echo $deposit_count; ?> completed deposits</span>
        </div>
    </div>

    <!-- Secondary Order Stats -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-gaming-850 border border-gaming-border rounded-xl p-4">
            <span class="text-[11px] text-zinc-400 uppercase font-semibold">Total Orders Placed</span>
            <div class="font-gaming text-2xl font-bold text-white mt-1"><?php echo $total_orders; ?></div>
        </div>
        <div class="bg-gaming-850 border border-gaming-border rounded-xl p-4">
            <span class="text-[11px] text-zinc-400 uppercase font-semibold">Pending / In Review</span>
            <div class="font-gaming text-2xl font-bold text-amber-400 mt-1"><?php echo $pending_orders; ?></div>
        </div>
        <div class="bg-gaming-850 border border-gaming-border rounded-xl p-4">
            <span class="text-[11px] text-zinc-400 uppercase font-semibold">Discounts Given</span>
            <div class="font-gaming text-2xl font-bold text-zinc-300 mt-1"><?php echo format_currency($total_discounts); ?></div>
        </div>
        <div class="bg-gaming-850 border border-gaming-border rounded-xl p-4">
            <span class="text-[11px] text-zinc-400 uppercase font-semibold">Coupons Redeemed</span>
            <div class="font-gaming text-2xl font-bold text-emerald-400 mt-1"><?php echo $coupon_usages; ?></div>
        </div>
    </div>

    <!-- Tables Grid: Top Selling & Daily Breakdown -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Top Products -->
        <div class="bg-gaming-850 border border-gaming-border rounded-2xl overflow-hidden shadow-2xl">
            <div class="p-5 border-b border-gaming-border">
                <h3 class="font-gaming text-lg font-bold text-white">Top Performing Products (by Revenue)</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="text-zinc-400 bg-gaming-900/60 uppercase border-b border-gaming-border">
                            <th class="py-3 px-4">Product</th>
                            <th class="py-3 px-4 text-center">Orders</th>
                            <th class="py-3 px-4 text-right">Revenue (₹)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gaming-border/60">
                        <?php if (empty($top_products)): ?>
                            <tr>
                                <td colspan="3" class="py-8 text-center text-zinc-500">No completed sales recorded during this period.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($top_products as $tp): ?>
                                <tr class="hover:bg-gaming-800/40 transition-colors">
                                    <td class="py-3 px-4 font-bold text-white"><?php echo e($tp['product_name']); ?></td>
                                    <td class="py-3 px-4 text-center font-mono text-zinc-300"><?php echo $tp['units_sold']; ?></td>
                                    <td class="py-3 px-4 text-right font-gaming font-bold text-emerald-400 text-sm"><?php echo format_currency($tp['total_sales']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Daily Sales Breakdown -->
        <div class="bg-gaming-850 border border-gaming-border rounded-2xl overflow-hidden shadow-2xl">
            <div class="p-5 border-b border-gaming-border">
                <h3 class="font-gaming text-lg font-bold text-white">Daily Order & Revenue Timeline</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="text-zinc-400 bg-gaming-900/60 uppercase border-b border-gaming-border">
                            <th class="py-3 px-4">Date</th>
                            <th class="py-3 px-4 text-center">Orders Placed</th>
                            <th class="py-3 px-4 text-right">Day Sales (₹)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gaming-border/60">
                        <?php if (empty($daily_breakdown)): ?>
                            <tr>
                                <td colspan="3" class="py-8 text-center text-zinc-500">No daily order activity during this period.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($daily_breakdown as $db): ?>
                                <tr class="hover:bg-gaming-800/40 transition-colors">
                                    <td class="py-3 px-4 font-semibold text-zinc-300"><?php echo date('D, M d, Y', strtotime($db['sale_date'])); ?></td>
                                    <td class="py-3 px-4 text-center font-mono text-white"><?php echo $db['day_orders']; ?></td>
                                    <td class="py-3 px-4 text-right font-gaming font-bold text-white text-sm"><?php echo format_currency($db['day_revenue']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
