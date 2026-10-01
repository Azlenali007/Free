<?php
$page_title = "Security & Access Controls";
require_once __DIR__ . '/includes/admin_header.php';

// Handle Unlock Locked IP
if (isset($_GET['action']) && $_GET['action'] === 'unlock') {
    $lid = (int)($_GET['id'] ?? 0);
    if ($lid > 0) {
        $stmt_del = $pdo->prepare("DELETE FROM login_attempts WHERE id = ?");
        $stmt_del->execute([$lid]);
        log_admin_activity('unlock_login_attempt', "Removed login lock record ID: {$lid}");
        set_flash('success', "Target IP/Identifier lock cleared.");
        header("Location: /admin/security-logs.php");
        exit;
    }
}

// Handle 2FA / Brute Force Settings POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'security_settings') {
    csrf_validate();

    $two_fa = isset($_POST['admin_2fa_enabled']) ? '1' : '0';
    $max_attempts = (int)($_POST['max_login_attempts'] ?? 5);
    $lockout_mins = (int)($_POST['lockout_duration_minutes'] ?? 15);

    update_setting('admin_2fa_enabled', $two_fa);
    update_setting('max_login_attempts', $max_attempts);
    update_setting('lockout_duration_minutes', $lockout_mins);

    log_admin_activity('update_security_settings', "Updated security settings: 2FA={$two_fa}, MaxAttempts={$max_attempts}, LockMins={$lockout_mins}");
    set_flash('success', "Security parameters updated.");
    header("Location: /admin/security-logs.php");
    exit;
}

// Fetch active locked IPs
$locked_attempts = $pdo->query("
    SELECT * FROM login_attempts 
    WHERE locked_until IS NOT NULL AND locked_until > NOW() 
    ORDER BY locked_until DESC
")->fetchAll();

// Fetch security logs
$security_logs = $pdo->query("
    SELECT sl.*, u.username 
    FROM security_logs sl 
    LEFT JOIN users u ON sl.user_id = u.id 
    ORDER BY sl.id DESC 
    LIMIT 100
")->fetchAll();

$admin_2fa = get_setting('admin_2fa_enabled', '0');
$max_attempts = get_setting('max_login_attempts', '5');
$lockout_mins = get_setting('lockout_duration_minutes', '15');
?>

<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="font-gaming text-2xl sm:text-3xl font-bold text-white tracking-wide flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-red-500 animate-pulse"></span> SYSTEM SECURITY & BRUTE-FORCE LOCKOUTS
            </h1>
            <p class="text-xs text-zinc-400 mt-1">Real-time threat monitoring, failed login tracking, and IP lockout management</p>
        </div>
    </div>

    <!-- Security Policy Form Card -->
    <div class="bg-gaming-850 border border-gaming-border rounded-2xl p-6 shadow-xl">
        <div class="border-b border-gaming-border pb-3 mb-4">
            <h2 class="font-gaming text-base font-bold text-white">Brute Force Protection & Admin Policy</h2>
        </div>

        <form method="POST" action="/admin/security-logs.php" class="grid grid-cols-1 sm:grid-cols-4 gap-4 items-end">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="security_settings">

            <div>
                <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1">Max Failed Attempts</label>
                <input type="number" name="max_login_attempts" value="<?php echo e($max_attempts); ?>" min="3" max="20"
                       class="w-full px-3.5 py-2.5 rounded-xl bg-gaming-900 border border-gaming-border focus:border-red-500 text-sm text-white focus:outline-none">
            </div>

            <div>
                <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1">Lockout Duration (Mins)</label>
                <input type="number" name="lockout_duration_minutes" value="<?php echo e($lockout_mins); ?>" min="5" max="1440"
                       class="w-full px-3.5 py-2.5 rounded-xl bg-gaming-900 border border-gaming-border focus:border-red-500 text-sm text-white focus:outline-none">
            </div>

            <div class="flex items-center gap-3 pb-2">
                <input type="checkbox" name="admin_2fa_enabled" id="two_fa" value="1" <?php echo $admin_2fa === '1' ? 'checked' : ''; ?>
                       class="w-4 h-4 rounded text-red-600 bg-gaming-900 border-gaming-border">
                <label for="two_fa" class="text-xs font-semibold text-zinc-300 cursor-pointer">
                    Enforce 2FA for Staff
                </label>
            </div>

            <div>
                <button type="submit" class="w-full py-2.5 rounded-xl bg-red-600 hover:bg-red-500 text-white font-gaming font-bold text-sm uppercase tracking-wider shadow-red-subtle transition-colors">
                    Update Policy
                </button>
            </div>
        </form>
    </div>

    <!-- Active Lockouts Table -->
    <?php if (!empty($locked_attempts)): ?>
        <div class="bg-gaming-850 border border-red-500/60 rounded-2xl p-5 space-y-4 shadow-red-subtle">
            <div class="flex items-center justify-between border-b border-gaming-border pb-3">
                <h2 class="font-gaming text-base font-bold text-red-400 flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-red-500 animate-ping"></span>
                    Currently Locked-Out IPs & Accounts (<?php echo count($locked_attempts); ?>)
                </h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="text-zinc-400 uppercase border-b border-gaming-border">
                            <th class="py-2 px-3">Target Identifier</th>
                            <th class="py-2 px-3">IP Address</th>
                            <th class="py-2 px-3">Failed Attempts</th>
                            <th class="py-2 px-3">Locked Until</th>
                            <th class="py-2 px-3 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gaming-border/60">
                        <?php foreach ($locked_attempts as $la): ?>
                            <tr>
                                <td class="py-2.5 px-3 font-bold text-white"><?php echo e($la['identifier']); ?></td>
                                <td class="py-2.5 px-3 font-mono text-zinc-300"><?php echo e($la['ip_address']); ?></td>
                                <td class="py-2.5 px-3 text-red-400 font-bold"><?php echo $la['attempts']; ?></td>
                                <td class="py-2.5 px-3 text-amber-400"><?php echo date('H:i:s (M d)', strtotime($la['locked_until'])); ?></td>
                                <td class="py-2.5 px-3 text-right">
                                    <a href="/admin/security-logs.php?action=unlock&id=<?php echo $la['id']; ?>" 
                                       class="px-3 py-1 rounded-lg bg-emerald-950 text-emerald-300 border border-emerald-800 font-semibold text-[11px]">
                                        Unlock Now
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

    <!-- Security Logs List Table -->
    <div class="bg-gaming-850 border border-gaming-border rounded-2xl overflow-hidden shadow-2xl">
        <div class="p-5 border-b border-gaming-border">
            <h2 class="font-gaming text-lg font-bold text-white">Security Event Logs</h2>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="text-zinc-400 bg-gaming-900/60 uppercase border-b border-gaming-border">
                        <th class="py-3 px-4">Timestamp</th>
                        <th class="py-3 px-4">Event</th>
                        <th class="py-3 px-4">Severity</th>
                        <th class="py-3 px-4">Details</th>
                        <th class="py-3 px-4">User</th>
                        <th class="py-3 px-4 text-right">IP Address</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gaming-border/60">
                    <?php if (empty($security_logs)): ?>
                        <tr>
                            <td colspan="6" class="py-8 text-center text-zinc-500">No security incidents logged.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($security_logs as $sl): ?>
                            <tr class="hover:bg-gaming-800/40 transition-colors">
                                <td class="py-3 px-4 font-mono text-[11px] text-zinc-400 whitespace-nowrap">
                                    <?php echo date('M d, H:i:s', strtotime($sl['created_at'])); ?>
                                </td>
                                <td class="py-3 px-4 font-mono font-bold text-white"><?php echo e($sl['event_type']); ?></td>
                                <td class="py-3 px-4">
                                    <?php if ($sl['severity'] === 'critical' || $sl['severity'] === 'danger'): ?>
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-red-950 text-red-300 border border-red-800 uppercase">Critical</span>
                                    <?php elseif ($sl['severity'] === 'warning'): ?>
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-950 text-amber-300 border border-amber-800 uppercase">Warning</span>
                                    <?php else: ?>
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-gaming-900 text-zinc-400 border border-gaming-border uppercase">Info</span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3 px-4 text-zinc-300 max-w-sm break-words"><?php echo e($sl['details']); ?></td>
                                <td class="py-3 px-4 text-zinc-400"><?php echo e($sl['username'] ?? 'Anonymous'); ?></td>
                                <td class="py-3 px-4 text-right font-mono text-zinc-500 text-[11px]"><?php echo e($sl['ip_address']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
