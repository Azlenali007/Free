<?php
$page_title = "User Notification Center";
require_once __DIR__ . '/includes/admin_header.php';

$errors = [];

// Handle Send Notification POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_notification') {
    csrf_validate();

    $target = sanitize($_POST['target'] ?? 'all');
    $user_id = ($target === 'single') ? (int)($_POST['user_id'] ?? 0) : null;
    $title = sanitize($_POST['title'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $type = in_array($_POST['type'] ?? '', ['order', 'promo', 'wallet', 'system']) ? $_POST['type'] : 'system';
    $link = sanitize($_POST['link'] ?? '');

    if (empty($title)) {
        $errors[] = "Notification title is required.";
    }
    if (empty($message)) {
        $errors[] = "Notification message cannot be empty.";
    }
    if ($target === 'single' && $user_id <= 0) {
        $errors[] = "Please select a target user.";
    }

    if (empty($errors)) {
        if ($target === 'all') {
            // Broadcast to all registered users
            $users = $pdo->query("SELECT id FROM users")->fetchAll(PDO::FETCH_COLUMN);
            $stmt_i = $pdo->prepare("INSERT INTO notifications (user_id, title, message, type, link) VALUES (?, ?, ?, ?, ?)");
            foreach ($users as $uid) {
                $stmt_i->execute([$uid, $title, $message, $type, $link]);
            }
            log_admin_activity('broadcast_notification', "Broadcasted notification to " . count($users) . " users: '{$title}'");
            set_flash('success', "Notification broadcasted to all " . count($users) . " registered users.");
        } else {
            create_notification($user_id, $title, $message, $type, $link);
            log_admin_activity('send_notification', "Sent notification to user ID {$user_id}: '{$title}'");
            set_flash('success', "Notification sent successfully.");
        }
        header("Location: /admin/notifications.php");
        exit;
    }
}

// Fetch users for dropdown
$users_list = $pdo->query("SELECT id, username, email FROM users ORDER BY username ASC")->fetchAll();

// Fetch recent notifications sent
$notifications = $pdo->query("
    SELECT n.*, u.username, u.email as user_email 
    FROM notifications n 
    LEFT JOIN users u ON n.user_id = u.id 
    ORDER BY n.id DESC 
    LIMIT 100
")->fetchAll();
?>

<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="font-gaming text-2xl sm:text-3xl font-bold text-white tracking-wide flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-red-500"></span> IN-APP NOTIFICATION CENTER
            </h1>
            <p class="text-xs text-zinc-400 mt-1">Send in-site announcements, diamond top-up alerts, and order updates to customers</p>
        </div>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="p-4 rounded-xl bg-red-950/70 border border-red-500/60 text-red-200 text-xs space-y-1">
            <?php foreach ($errors as $err): ?>
                <p>• <?php echo e($err); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Send Notification Form Card -->
        <div class="lg:col-span-1">
            <div class="bg-gaming-850 border border-gaming-border rounded-2xl p-6 space-y-5 sticky top-20 shadow-xl">
                <div class="border-b border-gaming-border pb-3">
                    <h2 class="font-gaming text-lg font-bold text-white flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-red-500"></span>
                        Send Notification
                    </h2>
                </div>

                <form method="POST" action="/admin/notifications.php" class="space-y-4">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="action" value="send_notification">

                    <div>
                        <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1">Target Audience</label>
                        <select name="target" id="targetSelect" onchange="toggleUserSelect(this.value)" class="w-full px-3.5 py-2.5 rounded-xl bg-gaming-900 border border-gaming-border focus:border-red-500 text-sm text-white focus:outline-none">
                            <option value="all">📢 All Registered Gamers (Broadcast)</option>
                            <option value="single">👤 Specific Gamer</option>
                        </select>
                    </div>

                    <div id="userSelectBox" class="hidden">
                        <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1">Select Gamer</label>
                        <select name="user_id" class="w-full px-3.5 py-2.5 rounded-xl bg-gaming-900 border border-gaming-border focus:border-red-500 text-sm text-white focus:outline-none">
                            <option value="">-- Choose User --</option>
                            <?php foreach ($users_list as $u): ?>
                                <option value="<?php echo $u['id']; ?>"><?php echo e($u['username']); ?> (<?php echo e($u['email']); ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1">Alert Type</label>
                        <select name="type" class="w-full px-3.5 py-2.5 rounded-xl bg-gaming-900 border border-gaming-border focus:border-red-500 text-sm text-white focus:outline-none">
                            <option value="promo">🔥 Promotional / Deal</option>
                            <option value="order">📦 Order Update</option>
                            <option value="wallet">💰 Wallet Alert</option>
                            <option value="system">⚙️ System Announcement</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1">Title *</label>
                        <input type="text" name="title" required placeholder="e.g. Flash Event: Extra Diamonds Active!"
                               class="w-full px-3.5 py-2.5 rounded-xl bg-gaming-900 border border-gaming-border focus:border-red-500 text-sm text-white focus:outline-none">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1">Message Content *</label>
                        <textarea name="message" rows="3" required placeholder="Type the notification text..."
                                  class="w-full px-3.5 py-2.5 rounded-xl bg-gaming-900 border border-gaming-border focus:border-red-500 text-sm text-white focus:outline-none"></textarea>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1">Action Link (Optional)</label>
                        <input type="text" name="link" placeholder="/products.php or /wallet.php"
                               class="w-full px-3.5 py-2.5 rounded-xl bg-gaming-900 border border-gaming-border focus:border-red-500 text-sm text-white focus:outline-none">
                    </div>

                    <button type="submit" class="w-full py-3 rounded-xl bg-red-600 hover:bg-red-500 text-white font-gaming font-bold text-sm uppercase tracking-wider shadow-red-subtle transition-colors">
                        Dispatch Notification
                    </button>
                </form>
            </div>
        </div>

        <!-- Recent Notifications Table Card -->
        <div class="lg:col-span-2 space-y-4">
            <div class="bg-gaming-850 border border-gaming-border rounded-2xl overflow-hidden shadow-2xl">
                <div class="p-5 border-b border-gaming-border flex items-center justify-between">
                    <h2 class="font-gaming text-lg font-bold text-white">Dispatched Notifications (<?php echo count($notifications); ?>)</h2>
                </div>

                <div class="divide-y divide-gaming-border/60">
                    <?php if (empty($notifications)): ?>
                        <div class="p-8 text-center text-zinc-500 text-xs">No notifications recorded yet.</div>
                    <?php else: ?>
                        <?php foreach ($notifications as $n): ?>
                            <div class="p-4 flex items-start justify-between gap-4 hover:bg-gaming-800/30 transition-colors">
                                <div class="space-y-1">
                                    <div class="flex items-center gap-2">
                                        <span class="font-mono text-[10px] font-bold uppercase px-2 py-0.5 rounded bg-gaming-900 border border-gaming-border text-red-400">
                                            <?php echo e($n['type']); ?>
                                        </span>
                                        <h3 class="font-bold text-white text-sm"><?php echo e($n['title']); ?></h3>
                                    </div>
                                    <p class="text-xs text-zinc-300"><?php echo e($n['message']); ?></p>
                                    <div class="flex items-center gap-3 text-[11px] text-zinc-500 mt-1">
                                        <span>User: <strong class="text-zinc-400"><?php echo e($n['username'] ?? 'All Users (Broadcast)'); ?></strong></span>
                                        <span>&bull;</span>
                                        <span>Status: <?php echo $n['is_read'] ? '<span class="text-emerald-400">Read</span>' : '<span class="text-zinc-500">Unread</span>'; ?></span>
                                        <span>&bull;</span>
                                        <span><?php echo date('M d, H:i', strtotime($n['created_at'])); ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function toggleUserSelect(val) {
    const box = document.getElementById('userSelectBox');
    if (val === 'single') {
        box.classList.remove('hidden');
    } else {
        box.classList.add('hidden');
    }
}
</script>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
