<?php
$page_title = "Admin Activity Logs";
require_once __DIR__ . '/includes/admin_header.php';

// Handle Clear Old Logs
if (isset($_POST['action']) && $_POST['action'] === 'clear_old') {
    csrf_validate();
    $days = 30;
    $stmt_c = $pdo->prepare("DELETE FROM admin_activity_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)");
    $stmt_c->execute([$days]);
    log_admin_activity('clear_logs', "Cleared logs older than {$days} days");
    set_flash('success', "Activity logs older than {$days} days have been cleaned.");
    header("Location: /admin/activity-logs.php");
    exit;
}

$search = sanitize($_GET['q'] ?? '');
$admin_filter = (int)($_GET['admin_id'] ?? 0);
$action_filter = sanitize($_GET['act'] ?? '');

$where = ["1=1"];
$params = [];

if (!empty($search)) {
    $where[] = "(action LIKE ? OR details LIKE ? OR ip_address LIKE ? OR admin_username LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($admin_filter > 0) {
    $where[] = "admin_id = ?";
    $params[] = $admin_filter;
}

if (!empty($action_filter)) {
    $where[] = "action = ?";
    $params[] = $action_filter;
}

$sql = "
    SELECT * FROM admin_activity_logs 
    WHERE " . implode(' AND ', $where) . " 
    ORDER BY id DESC 
    LIMIT 150
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll();

// Distinct actions for dropdown
$actions = $pdo->query("SELECT DISTINCT action FROM admin_activity_logs ORDER BY action ASC")->fetchAll(PDO::FETCH_COLUMN);
$admins = $pdo->query("SELECT id, username, name FROM admins ORDER BY username ASC")->fetchAll();
?>

<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="font-gaming text-2xl sm:text-3xl font-bold text-white tracking-wide flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-red-500"></span> ADMIN AUDIT TRAIL & ACTIVITY LOGS
            </h1>
            <p class="text-xs text-zinc-400 mt-1">Complete immutable chronological audit log of all administrative actions and balance adjustments</p>
        </div>
        <div class="flex items-center gap-2">
            <form method="POST" action="/admin/activity-logs.php" onsubmit="return confirm('Clear activity logs older than 30 days?')">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="action" value="clear_old">
                <button type="submit" class="px-4 py-2.5 rounded-xl bg-gaming-800 hover:bg-gaming-750 text-xs font-semibold text-zinc-400 hover:text-white border border-gaming-border">
                    Clear >30 Days
                </button>
            </form>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="bg-gaming-850 border border-gaming-border rounded-xl p-4 flex flex-col sm:flex-row items-center justify-between gap-4 shadow-xl">
        <form method="GET" action="/admin/activity-logs.php" class="flex flex-wrap items-center gap-3 w-full sm:w-auto flex-1">
            <input type="text" name="q" value="<?php echo e($search); ?>" placeholder="Search keyword, IP, details..."
                   class="px-3.5 py-2 rounded-xl bg-gaming-900 border border-gaming-border text-xs text-white placeholder-zinc-500 focus:outline-none focus:border-red-500 flex-1 sm:w-60">

            <select name="act" class="px-3 py-2 rounded-xl bg-gaming-900 border border-gaming-border text-xs text-white focus:outline-none">
                <option value="">All Actions (<?php echo count($actions); ?>)</option>
                <?php foreach ($actions as $act): ?>
                    <option value="<?php echo e($act); ?>" <?php echo $action_filter === $act ? 'selected' : ''; ?>><?php echo e($act); ?></option>
                <?php endforeach; ?>
            </select>

            <select name="admin_id" class="px-3 py-2 rounded-xl bg-gaming-900 border border-gaming-border text-xs text-white focus:outline-none">
                <option value="0">All Staff</option>
                <?php foreach ($admins as $ad): ?>
                    <option value="<?php echo $ad['id']; ?>" <?php echo $admin_filter == $ad['id'] ? 'selected' : ''; ?>><?php echo e($ad['username']); ?></option>
                <?php endforeach; ?>
            </select>

            <button type="submit" class="px-4 py-2 bg-gaming-800 hover:bg-gaming-750 text-xs font-semibold text-white rounded-xl border border-gaming-border">Filter</button>
            <?php if (!empty($search) || !empty($action_filter) || $admin_filter > 0): ?>
                <a href="/admin/activity-logs.php" class="text-xs text-red-400 hover:underline">Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Activity Logs Table -->
    <div class="bg-gaming-850 border border-gaming-border rounded-2xl overflow-hidden shadow-2xl">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="text-zinc-400 bg-gaming-900/60 uppercase border-b border-gaming-border">
                        <th class="py-3 px-4">Timestamp</th>
                        <th class="py-3 px-4">Admin</th>
                        <th class="py-3 px-4">Action Event</th>
                        <th class="py-3 px-4">Details</th>
                        <th class="py-3 px-4 text-right">IP Address</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gaming-border/60">
                    <?php if (empty($logs)): ?>
                        <tr>
                            <td colspan="5" class="py-8 text-center text-zinc-500">No activity log entries found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($logs as $l): ?>
                            <tr class="hover:bg-gaming-800/40 transition-colors">
                                <td class="py-3 px-4 text-zinc-400 whitespace-nowrap font-mono text-[11px]">
                                    <?php echo date('M d, Y H:i:s', strtotime($l['created_at'])); ?>
                                </td>
                                <td class="py-3 px-4">
                                    <span class="font-bold text-white"><?php echo e($l['admin_username']); ?></span>
                                </td>
                                <td class="py-3 px-4">
                                    <span class="font-mono text-red-400 font-semibold px-2 py-0.5 rounded bg-gaming-900 border border-gaming-border text-[11px]">
                                        <?php echo e($l['action']); ?>
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-zinc-300 max-w-md break-words">
                                    <?php echo e($l['details']); ?>
                                </td>
                                <td class="py-3 px-4 text-right font-mono text-zinc-500 text-[11px]">
                                    <?php echo e($l['ip_address']); ?>
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
