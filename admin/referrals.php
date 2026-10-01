<?php
$page_title = "Referral Program Management";
require_once __DIR__ . '/includes/admin_header.php';

$errors = [];

// Handle Update Referral Settings POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'referral_settings') {
    csrf_validate();

    $enabled = isset($_POST['referral_enabled']) ? '1' : '0';
    $reward_amt = (float)($_POST['referral_reward_amount'] ?? 25.00);
    $min_order = (float)($_POST['referral_min_order'] ?? 100.00);

    update_setting('referral_enabled', $enabled);
    update_setting('referral_reward_amount', $reward_amt);
    update_setting('referral_min_order', $min_order);

    log_admin_activity('update_referral_settings', "Updated referral rules: Enabled={$enabled}, Reward=" . format_currency($reward_amt) . ", MinOrder=" . format_currency($min_order));
    set_flash('success', "Referral program rules saved.");
    header("Location: /admin/referrals.php");
    exit;
}

// Fetch settings
$ref_enabled = get_setting('referral_enabled', '1');
$ref_reward = get_setting('referral_reward_amount', '25.00');
$ref_min_order = get_setting('referral_min_order', '100.00');

// Fetch referral records
$referral_records = $pdo->query("
    SELECT rr.*, 
           u1.username as referrer_name, u1.email as referrer_email, 
           u2.username as referred_name, u2.email as referred_email, 
           o.order_number 
    FROM referral_records rr 
    LEFT JOIN users u1 ON rr.referrer_id = u1.id 
    LEFT JOIN users u2 ON rr.referred_user_id = u2.id 
    LEFT JOIN orders o ON rr.order_id = o.id 
    ORDER BY rr.id DESC 
    LIMIT 100
")->fetchAll();

// Metrics
$total_referrals = (int)$pdo->query("SELECT COUNT(*) FROM referral_records")->fetchColumn();
$rewarded_count = (int)$pdo->query("SELECT COUNT(*) FROM referral_records WHERE status = 'rewarded'")->fetchColumn();
$total_rewards_paid = (float)$pdo->query("SELECT COALESCE(SUM(reward_amount), 0) FROM referral_records WHERE status = 'rewarded'")->fetchColumn();
?>

<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="font-gaming text-2xl sm:text-3xl font-bold text-white tracking-wide flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-red-500"></span> REFERRAL PROGRAM & REWARDS (₹ INR)
            </h1>
            <p class="text-xs text-zinc-400 mt-1">Configure gamer referral bonuses, commission payouts, and audit referral tracking</p>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-gaming-850 border border-gaming-border rounded-xl p-4">
            <span class="text-xs text-zinc-400 uppercase font-semibold">Total Invitations</span>
            <div class="font-gaming text-2xl font-bold text-white mt-1"><?php echo $total_referrals; ?> Gamers</div>
            <span class="text-[10px] text-zinc-500">Referral links tracked</span>
        </div>

        <div class="bg-gaming-850 border border-gaming-border rounded-xl p-4">
            <span class="text-xs text-zinc-400 uppercase font-semibold">Rewards Claimed</span>
            <div class="font-gaming text-2xl font-bold text-emerald-400 mt-1"><?php echo $rewarded_count; ?> Orders</div>
            <span class="text-[10px] text-zinc-500">Met qualifying purchase threshold</span>
        </div>

        <div class="bg-gaming-850 border border-gaming-border rounded-xl p-4">
            <span class="text-xs text-zinc-400 uppercase font-semibold">Total Rewards Credited</span>
            <div class="font-gaming text-2xl font-bold text-white mt-1"><?php echo format_currency($total_rewards_paid); ?></div>
            <span class="text-[10px] text-zinc-500">Credited to referrer wallets</span>
        </div>
    </div>

    <!-- Referral Rules Configuration Form Card -->
    <div class="bg-gaming-850 border border-gaming-border rounded-2xl p-6 shadow-xl">
        <div class="border-b border-gaming-border pb-3 mb-4">
            <h2 class="font-gaming text-base font-bold text-white">Referral Program Rules & Conditions</h2>
        </div>

        <form method="POST" action="/admin/referrals.php" class="grid grid-cols-1 sm:grid-cols-4 gap-4 items-end">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="referral_settings">

            <div>
                <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1">Reward Per Referral (₹ INR)</label>
                <input type="number" step="0.5" min="0" name="referral_reward_amount" value="<?php echo e($ref_reward); ?>" required
                       class="w-full px-3.5 py-2.5 rounded-xl bg-gaming-900 border border-gaming-border focus:border-red-500 text-sm text-white focus:outline-none">
            </div>

            <div>
                <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1">Min Qualifying Order (₹ INR)</label>
                <input type="number" step="1" min="0" name="referral_min_order" value="<?php echo e($ref_min_order); ?>" required
                       class="w-full px-3.5 py-2.5 rounded-xl bg-gaming-900 border border-gaming-border focus:border-red-500 text-sm text-white focus:outline-none">
            </div>

            <div class="flex items-center gap-3 pb-2">
                <input type="checkbox" name="referral_enabled" id="ref_en" value="1" <?php echo $ref_enabled === '1' ? 'checked' : ''; ?>
                       class="w-4 h-4 rounded text-red-600 bg-gaming-900 border-gaming-border">
                <label for="ref_en" class="text-xs font-semibold text-zinc-300 cursor-pointer">
                    Enable Referral System
                </label>
            </div>

            <div>
                <button type="submit" class="w-full py-2.5 rounded-xl bg-red-600 hover:bg-red-500 text-white font-gaming font-bold text-sm uppercase tracking-wider shadow-red-subtle transition-colors">
                    Save Rules
                </button>
            </div>
        </form>
    </div>

    <!-- Referral Records Table -->
    <div class="bg-gaming-850 border border-gaming-border rounded-2xl overflow-hidden shadow-2xl">
        <div class="p-5 border-b border-gaming-border flex items-center justify-between">
            <h2 class="font-gaming text-lg font-bold text-white">Recent Referral Tracking Records</h2>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="text-zinc-400 bg-gaming-900/60 uppercase border-b border-gaming-border">
                        <th class="py-3 px-4">Referrer</th>
                        <th class="py-3 px-4">Referred Gamer</th>
                        <th class="py-3 px-4">Qualifying Order</th>
                        <th class="py-3 px-4">Reward Amount</th>
                        <th class="py-3 px-4 text-center">Status</th>
                        <th class="py-3 px-4 text-right">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gaming-border/60">
                    <?php if (empty($referral_records)): ?>
                        <tr>
                            <td colspan="6" class="py-8 text-center text-zinc-500">No referral records logged yet.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($referral_records as $rr): ?>
                            <tr class="hover:bg-gaming-800/40 transition-colors">
                                <td class="py-3 px-4">
                                    <div class="font-bold text-white"><?php echo e($rr['referrer_name'] ?? 'User #' . $rr['referrer_id']); ?></div>
                                    <div class="text-[10px] text-zinc-500"><?php echo e($rr['referrer_email']); ?></div>
                                </td>
                                <td class="py-3 px-4">
                                    <div class="font-bold text-white"><?php echo e($rr['referred_name'] ?? 'User #' . $rr['referred_user_id']); ?></div>
                                    <div class="text-[10px] text-zinc-500"><?php echo e($rr['referred_email']); ?></div>
                                </td>
                                <td class="py-3 px-4">
                                    <?php if (!empty($rr['order_number'])): ?>
                                        <a href="/admin/order-details.php?id=<?php echo $rr['order_id']; ?>" class="text-red-400 font-mono hover:underline">
                                            <?php echo e($rr['order_number']); ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-zinc-500 text-[11px]">No order yet</span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3 px-4 font-gaming font-bold text-emerald-400 text-sm">
                                    <?php echo format_currency($rr['reward_amount']); ?>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <?php if ($rr['status'] === 'rewarded'): ?>
                                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-950 text-emerald-300 border border-emerald-800">Rewarded</span>
                                    <?php elseif ($rr['status'] === 'pending'): ?>
                                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-950 text-amber-300 border border-amber-800">Pending Order</span>
                                    <?php else: ?>
                                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-zinc-800 text-zinc-400 border border-zinc-700">Cancelled</span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3 px-4 text-right text-zinc-400 font-mono text-[11px]">
                                    <?php echo date('M d, Y', strtotime($rr['created_at'])); ?>
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
