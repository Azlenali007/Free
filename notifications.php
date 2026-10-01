<?php
$page_title = "My Notifications";
require_once __DIR__ . '/includes/functions.php';
require_login();

$user = current_user();

// Mark all as read if requested
if (isset($_GET['mark_all_read'])) {
    $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? OR user_id IS NULL")->execute([$user['id']]);
    set_flash('success', 'All notifications marked as read.');
    header("Location: /notifications.php");
    exit;
}

// Fetch notifications
$stmt = $pdo->prepare("
    SELECT * FROM notifications 
    WHERE user_id = ? OR user_id IS NULL 
    ORDER BY id DESC 
    LIMIT 50
");
$stmt->execute([$user['id']]);
$notifications = $stmt->fetchAll();

// Mark individual as read if clicked
if (isset($_GET['read_id'])) {
    $read_id = (int)$_GET['read_id'];
    $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND (user_id = ? OR user_id IS NULL)")->execute([$read_id, $user['id']]);
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-8">
    <div class="flex items-center justify-between pb-4 border-b border-gaming-border">
        <div>
            <h1 class="font-gaming text-3xl font-extrabold text-white tracking-wide flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-red-500"></span> NOTIFICATIONS
            </h1>
            <p class="text-xs text-zinc-400 mt-1">Real-time alerts regarding your orders, deposits, and rewards</p>
        </div>
        <?php if (!empty($notifications)): ?>
            <a href="/notifications.php?mark_all_read=1" class="px-3 py-1.5 rounded-lg bg-gaming-850 hover:bg-gaming-800 text-xs font-semibold text-zinc-300 border border-gaming-border">
                ✓ Mark All Read
            </a>
        <?php endif; ?>
    </div>

    <?php if (empty($notifications)): ?>
        <div class="text-center py-20 rounded-2xl bg-gaming-900 border border-gaming-border p-8">
            <div class="w-16 h-16 mx-auto rounded-full bg-red-950/60 border border-red-800/40 flex items-center justify-center text-red-500 mb-4">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
            </div>
            <h3 class="font-gaming text-xl font-bold text-white mb-2">No Notifications</h3>
            <p class="text-xs text-zinc-400 max-w-sm mx-auto">You're all caught up! Updates about your orders and wallet will appear here.</p>
        </div>
    <?php else: ?>
        <div class="space-y-3">
            <?php foreach ($notifications as $n): 
                $is_unread = ($n['is_read'] == 0);
            ?>
                <div class="p-4 rounded-xl border transition-all flex items-start gap-4 <?php echo $is_unread ? 'bg-gaming-850 border-red-800/60 shadow-red-subtle' : 'bg-gaming-900 border-gaming-border opacity-85'; ?>">
                    <div class="w-10 h-10 rounded-lg flex items-center justify-center text-white shrink-0 <?php echo $is_unread ? 'bg-red-600 shadow-red-subtle' : 'bg-gaming-800 text-zinc-400'; ?>">
                        <?php if ($n['type'] === 'wallet'): ?>
                            💰
                        <?php elseif ($n['type'] === 'referral'): ?>
                            🎁
                        <?php else: ?>
                            💎
                        <?php endif; ?>
                    </div>

                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between gap-2">
                            <h4 class="font-gaming font-bold text-sm text-white"><?php echo e($n['title']); ?></h4>
                            <span class="text-[10px] text-zinc-500 font-mono"><?php echo date('M d, h:i A', strtotime($n['created_at'])); ?></span>
                        </div>
                        <p class="text-xs text-zinc-300 mt-1 leading-relaxed"><?php echo e($n['message']); ?></p>

                        <?php if (!empty($n['link'])): ?>
                            <div class="mt-2.5">
                                <a href="<?php echo e($n['link']); ?>" class="inline-flex items-center gap-1 text-xs font-semibold text-red-400 hover:text-red-300 underline underline-offset-4">
                                    <span>View Details</span> &rarr;
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if ($is_unread): ?>
                        <span class="w-2 h-2 rounded-full bg-red-500 shrink-0 mt-2"></span>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
