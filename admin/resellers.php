<?php
$page_title = "Reseller Partners & Wholesale Management";
require_once __DIR__ . '/includes/admin_header.php';

$errors = [];

// Handle Toggle Reseller Status
if (isset($_GET['action']) && $_GET['action'] === 'toggle') {
    $uid = (int)($_GET['id'] ?? 0);
    if ($uid > 0) {
        $stmt_t = $pdo->prepare("UPDATE users SET is_reseller = IF(is_reseller = 1, 0, 1) WHERE id = ?");
        $stmt_t->execute([$uid]);
        log_admin_activity('toggle_reseller_status', "Toggled reseller access for User ID: {$uid}");
        set_flash('success', "Reseller partner status updated.");
        header("Location: /admin/resellers.php");
        exit;
    }
}

// Handle Update Global Reseller Settings POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_settings') {
    csrf_validate();
    $enabled = isset($_POST['reseller_system_enabled']) ? '1' : '0';
    $default_discount = (float)($_POST['reseller_default_discount'] ?? 10);

    update_setting('reseller_system_enabled', $enabled);
    update_setting('reseller_default_discount', $default_discount);

    log_admin_activity('update_reseller_settings', "Updated reseller system discount: {$default_discount}%");
    set_flash('success', "Reseller program settings updated successfully.");
    header("Location: /admin/resellers.php");
    exit;
}

// Fetch all resellers
$resellers = $pdo->query("
    SELECT u.*, 
           (SELECT COUNT(*) FROM orders o WHERE o.user_id = u.id) as order_count,
           (SELECT COALESCE(SUM(total_amount), 0) FROM orders o WHERE o.user_id = u.id AND (o.payment_status = 'paid' OR o.order_status = 'completed')) as total_volume 
    FROM users u 
    WHERE u.is_reseller = 1 
    ORDER BY total_volume DESC, u.id DESC
")->fetchAll();

$system_enabled = get_setting('reseller_system_enabled', '1');
$default_discount = get_setting('reseller_default_discount', '10');
?>

<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="font-gaming text-2xl sm:text-3xl font-bold text-white tracking-wide flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-red-500"></span> RESELLER PARTNER PROGRAM (INR)
            </h1>
            <p class="text-xs text-zinc-400 mt-1">Manage approved wholesale gaming partners, bulk pricing, and reseller margins in ₹ INR</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="/admin/users.php" class="px-4 py-2.5 rounded-xl bg-gaming-800 hover:bg-gaming-750 text-xs font-semibold text-zinc-300 border border-gaming-border">
                Manage All Users
            </a>
        </div>
    </div>

    <!-- Reseller Program Settings Card -->
    <div class="bg-gaming-850 border border-gaming-border rounded-2xl p-6 shadow-xl">
        <div class="flex items-center justify-between border-b border-gaming-border pb-3 mb-4">
            <h2 class="font-gaming text-base font-bold text-white">Wholesale Program Configuration</h2>
        </div>

        <form method="POST" action="/admin/resellers.php" class="grid grid-cols-1 sm:grid-cols-3 gap-4 items-end">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="update_settings">

            <div>
                <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1">Default Wholesale Discount (%)</label>
                <div class="relative">
                    <input type="number" step="0.5" min="0" max="100" name="reseller_default_discount" value="<?php echo e($default_discount); ?>" required
                           class="w-full px-3.5 py-2.5 rounded-xl bg-gaming-900 border border-gaming-border focus:border-red-500 text-sm text-white focus:outline-none">
                    <span class="absolute right-3.5 top-2.5 text-zinc-500 text-sm">%</span>
                </div>
            </div>

            <div class="flex items-center gap-3 pb-2">
                <input type="checkbox" name="reseller_system_enabled" id="res_enabled" value="1" <?php echo $system_enabled === '1' ? 'checked' : ''; ?>
                       class="w-4 h-4 rounded text-red-600 bg-gaming-900 border-gaming-border">
                <label for="res_enabled" class="text-xs font-semibold text-zinc-300 cursor-pointer">
                    Enable Reseller Portal & Wholesale Checkout
                </label>
            </div>

            <div>
                <button type="submit" class="w-full py-2.5 rounded-xl bg-red-600 hover:bg-red-500 text-white font-gaming font-bold text-sm uppercase tracking-wider shadow-red-subtle transition-colors">
                    Save Reseller Rules
                </button>
            </div>
        </form>
    </div>

    <!-- Approved Resellers List -->
    <div class="bg-gaming-850 border border-gaming-border rounded-2xl overflow-hidden shadow-2xl">
        <div class="p-5 border-b border-gaming-border flex items-center justify-between">
            <h2 class="font-gaming text-lg font-bold text-white flex items-center gap-2">
                <span>Approved Reseller Accounts</span>
                <span class="text-xs px-2 py-0.5 rounded-full bg-red-500/20 text-red-400 border border-red-500/40 font-mono">
                    <?php echo count($resellers); ?> Active Partners
                </span>
            </h2>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="text-zinc-400 bg-gaming-900/60 uppercase border-b border-gaming-border">
                        <th class="py-3 px-4">Partner</th>
                        <th class="py-3 px-4">Player UID</th>
                        <th class="py-3 px-4">Wallet Balance (₹)</th>
                        <th class="py-3 px-4 text-center">Orders</th>
                        <th class="py-3 px-4 text-right">Lifetime Volume (₹)</th>
                        <th class="py-3 px-4 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gaming-border/60">
                    <?php if (empty($resellers)): ?>
                        <tr>
                            <td colspan="6" class="py-8 text-center text-zinc-500">
                                No reseller accounts designated yet. Visit <a href="/admin/users.php" class="text-red-400 underline">Users Directory</a> to upgrade any user to Reseller status.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($resellers as $res): ?>
                            <tr class="hover:bg-gaming-800/40 transition-colors">
                                <td class="py-3 px-4">
                                    <div class="font-bold text-white text-sm"><?php echo e($res['username']); ?></div>
                                    <div class="text-[11px] text-zinc-400"><?php echo e($res['email']); ?></div>
                                </td>
                                <td class="py-3 px-4 font-mono text-zinc-300">
                                    <?php echo !empty($res['ff_uid']) ? e($res['ff_uid']) : '<span class="text-zinc-600">Not set</span>'; ?>
                                </td>
                                <td class="py-3 px-4 font-gaming font-bold text-emerald-400 text-sm">
                                    <?php echo format_currency($res['wallet_balance']); ?>
                                </td>
                                <td class="py-3 px-4 text-center font-mono text-white">
                                    <?php echo $res['order_count']; ?>
                                </td>
                                <td class="py-3 px-4 text-right font-gaming font-bold text-white text-sm">
                                    <?php echo format_currency($res['total_volume']); ?>
                                </td>
                                <td class="py-3 px-4 text-right space-x-2">
                                    <a href="/admin/wallet.php?user_id=<?php echo $res['id']; ?>" class="px-2.5 py-1 rounded-lg bg-gaming-800 hover:bg-gaming-700 text-zinc-200 border border-gaming-border text-[11px] font-semibold">
                                        Top-Up Wallet
                                    </a>
                                    <a href="/admin/resellers.php?action=toggle&id=<?php echo $res['id']; ?>" 
                                       onclick="return confirm('Revoke reseller status for this user?')"
                                       class="px-2.5 py-1 rounded-lg bg-red-950/60 hover:bg-red-900 text-red-300 border border-red-800/40 text-[11px] font-semibold">
                                        Revoke Partner
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
