<?php
$page_title = "Refer & Earn Free Diamonds";
require_once __DIR__ . '/includes/functions.php';
require_login();

$user = current_user();

// Ensure user has a referral code
if (empty($user['referral_code'])) {
    $my_ref = 'FZ' . $user['id'] . strtoupper(substr(md5(uniqid($user['username'], true)), 0, 4));
    $pdo->prepare("UPDATE users SET referral_code = ? WHERE id = ?")->execute([$my_ref, $user['id']]);
    $user['referral_code'] = $my_ref;
}

$site_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . ($_SERVER['HTTP_HOST'] ?? 'localhost:3000');
$referral_url = $site_url . '/register.php?ref=' . urlencode($user['referral_code']);

// Fetch stats
$stmt_cnt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE referred_by = ?");
$stmt_cnt->execute([$user['id']]);
$total_friends = (int)$stmt_cnt->fetchColumn();

$stmt_rew = $pdo->prepare("SELECT COALESCE(SUM(reward_amount), 0) FROM referral_records WHERE referrer_id = ? AND status = 'rewarded'");
$stmt_rew->execute([$user['id']]);
$total_earned = (float)$stmt_rew->fetchColumn();

// Fetch referral log
$stmt_log = $pdo->prepare("
    SELECT r.*, u.username as referred_username, u.created_at as join_date, o.order_number
    FROM referral_records r
    JOIN users u ON r.referred_user_id = u.id
    LEFT JOIN orders o ON r.order_id = o.id
    WHERE r.referrer_id = ?
    ORDER BY r.id DESC
");
$stmt_log->execute([$user['id']]);
$referral_history = $stmt_log->fetchAll();

$reward_amt = get_setting('referral_reward_amount', '0.25');
$min_order = get_setting('referral_min_order', '1.00');

require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-8">
    <div class="flex items-center justify-between pb-4 border-b border-gaming-border">
        <div>
            <h1 class="font-gaming text-3xl font-extrabold text-white tracking-wide flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-red-500"></span> REFER & EARN PROGRAM
            </h1>
            <p class="text-xs text-zinc-400 mt-1">Invite friends to FireZone Store and earn free gaming wallet balance</p>
        </div>
        <a href="/wallet.php" class="px-3 py-1.5 rounded-lg bg-gaming-850 hover:bg-gaming-800 text-xs font-semibold text-emerald-400 border border-emerald-900/40">
            Wallet Balance: <?php echo format_currency($user['wallet_balance']); ?>
        </a>
    </div>

    <!-- Referral Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
        <div class="p-6 rounded-2xl bg-gaming-850 border border-gaming-border shadow-xl flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-red-950/80 border border-red-800/40 flex items-center justify-center text-red-500 shrink-0 shadow-red-subtle">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
            </div>
            <div>
                <span class="text-xs text-zinc-400 uppercase tracking-wider block">Friends Invited</span>
                <span class="font-gaming text-2xl font-bold text-white"><?php echo $total_friends; ?></span>
            </div>
        </div>

        <div class="p-6 rounded-2xl bg-gaming-850 border border-gaming-border shadow-xl flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-emerald-950/80 border border-emerald-800/40 flex items-center justify-center text-emerald-400 shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div>
                <span class="text-xs text-zinc-400 uppercase tracking-wider block">Rewards Earned</span>
                <span class="font-gaming text-2xl font-bold text-emerald-400"><?php echo format_currency($total_earned); ?></span>
            </div>
        </div>

        <div class="p-6 rounded-2xl bg-gaming-850 border border-gaming-border shadow-xl flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-amber-950/80 border border-amber-800/40 flex items-center justify-center text-amber-400 shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
            </div>
            <div>
                <span class="text-xs text-zinc-400 uppercase tracking-wider block">Reward Per Friend</span>
                <span class="font-gaming text-2xl font-bold text-amber-400">+<?php echo format_currency($reward_amt); ?></span>
            </div>
        </div>
    </div>

    <!-- Referral Link Box -->
    <div class="p-6 sm:p-8 rounded-2xl bg-gaming-900 border border-red-800/40 shadow-2xl relative overflow-hidden space-y-4">
        <div class="flex items-center gap-2">
            <span class="w-2.5 h-2.5 rounded-full bg-red-500 animate-pulse"></span>
            <h2 class="font-gaming text-lg font-bold text-white tracking-wide">YOUR EXCLUSIVE REFERRAL LINK</h2>
        </div>
        <p class="text-xs text-zinc-400">Share this link across WhatsApp, Discord, Telegram, or in gaming lobbies. When friends sign up and make a top-up of at least <?php echo format_currency($min_order); ?>, you receive <?php echo format_currency($reward_amt); ?> directly in your wallet.</p>

        <div class="flex flex-col sm:flex-row items-center gap-3 pt-2">
            <input type="text" id="refLinkInput" readonly value="<?php echo e($referral_url); ?>" class="w-full px-4 py-3 rounded-xl bg-gaming-850 border border-gaming-border text-white font-mono text-xs focus:outline-none">
            <button type="button" onclick="copyRefLink()" id="copyBtn" class="w-full sm:w-auto btn-gaming-red text-white font-gaming text-xs font-bold px-6 py-3 rounded-xl shadow-red-subtle shrink-0">
                COPY LINK
            </button>
        </div>
        <div class="text-[11px] text-zinc-500">Your Referral Code: <strong class="font-mono text-red-400"><?php echo e($user['referral_code']); ?></strong></div>
    </div>

    <!-- History -->
    <div class="space-y-4">
        <h2 class="font-gaming text-lg font-bold text-white tracking-wide">REFERRAL REWARDS LOG</h2>

        <?php if (empty($referral_history)): ?>
            <div class="text-center py-10 rounded-2xl bg-gaming-900 border border-gaming-border text-xs text-zinc-400">
                You haven't earned any referral bonuses yet. Copy your link above and invite your squad!
            </div>
        <?php else: ?>
            <div class="overflow-hidden rounded-2xl bg-gaming-850 border border-gaming-border">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-gaming-border bg-gaming-900 text-zinc-400 uppercase font-mono text-[10px]">
                            <th class="py-3 px-6">Referred Gamer</th>
                            <th class="py-3 px-4">Date</th>
                            <th class="py-3 px-4">Order Ref</th>
                            <th class="py-3 px-4">Reward</th>
                            <th class="py-3 px-6 text-right">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gaming-border/60">
                        <?php foreach ($referral_history as $rh): ?>
                            <tr>
                                <td class="py-3.5 px-6 font-bold text-white font-mono">
                                    <?php echo e($rh['referred_username']); ?>
                                </td>
                                <td class="py-3.5 px-4 text-zinc-400 text-[11px]">
                                    <?php echo date('M d, Y', strtotime($rh['created_at'])); ?>
                                </td>
                                <td class="py-3.5 px-4 font-mono text-zinc-300">
                                    <?php echo e($rh['order_number'] ?: 'First Top-Up'); ?>
                                </td>
                                <td class="py-3.5 px-4 font-mono font-bold text-emerald-400">
                                    +<?php echo format_currency($rh['reward_amount']); ?>
                                </td>
                                <td class="py-3.5 px-6 text-right">
                                    <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-emerald-950 text-emerald-400 border border-emerald-800">
                                        <?php echo e($rh['status']); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function copyRefLink() {
    const input = document.getElementById('refLinkInput');
    input.select();
    input.setSelectionRange(0, 99999);
    navigator.clipboard.writeText(input.value);
    
    const btn = document.getElementById('copyBtn');
    btn.textContent = 'COPIED!';
    setTimeout(() => {
        btn.textContent = 'COPY LINK';
    }, 2000);
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
