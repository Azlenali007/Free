<?php
$page_title = "Store REST API & Client Keys";
require_once __DIR__ . '/includes/admin_header.php';

$errors = [];

// Handle Generate API Key POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'generate_key') {
    csrf_validate();

    $name = sanitize($_POST['client_name'] ?? '');
    $user_id = !empty($_POST['user_id']) ? (int)$_POST['user_id'] : null;
    $rate_limit = (int)($_POST['rate_limit'] ?? 60);

    if (empty($name)) {
        $errors[] = "Client application name is required.";
    }

    if (empty($errors)) {
        $api_key = 'fz_live_' . bin2hex(random_bytes(16));
        $api_secret = 'fz_sec_' . bin2hex(random_bytes(24));

        $stmt_i = $pdo->prepare("INSERT INTO api_keys (user_id, api_key, api_secret, name, status, rate_limit_per_minute) VALUES (?, ?, ?, ?, 'active', ?)");
        $stmt_i->execute([$user_id, $api_key, $api_secret, $name, $rate_limit]);

        log_admin_activity('generate_api_key', "Generated API Key for client: {$name}");
        set_flash('success', "New API Key generated successfully! Key: {$api_key}");
        header("Location: /admin/api.php");
        exit;
    }
}

// Handle Revoke / Toggle API Key
if (isset($_GET['action']) && $_GET['action'] === 'toggle') {
    $kid = (int)($_GET['id'] ?? 0);
    if ($kid > 0) {
        $stmt_t = $pdo->prepare("UPDATE api_keys SET status = IF(status = 'active', 'inactive', 'active') WHERE id = ?");
        $stmt_t->execute([$kid]);
        log_admin_activity('toggle_api_key_status', "Toggled status for API Key ID: {$kid}");
        set_flash('success', "API Key status updated.");
        header("Location: /admin/api.php");
        exit;
    }
}

// Handle Delete API Key
if (isset($_GET['action']) && $_GET['action'] === 'delete') {
    $kid = (int)($_GET['id'] ?? 0);
    if ($kid > 0) {
        $stmt_del = $pdo->prepare("DELETE FROM api_keys WHERE id = ?");
        $stmt_del->execute([$kid]);
        log_admin_activity('delete_api_key', "Deleted API Key ID: {$kid}");
        set_flash('success', "API Key revoked permanently.");
        header("Location: /admin/api.php");
        exit;
    }
}

// Fetch all keys
$api_keys = $pdo->query("
    SELECT ak.*, u.username 
    FROM api_keys ak 
    LEFT JOIN users u ON ak.user_id = u.id 
    ORDER BY ak.id DESC
")->fetchAll();

// Fetch recent API request logs
$api_logs = $pdo->query("
    SELECT al.*, ak.name as client_name 
    FROM api_logs al 
    LEFT JOIN api_keys ak ON al.api_key_id = ak.id 
    ORDER BY al.id DESC 
    LIMIT 25
")->fetchAll();

// Users for dropdown
$users_list = $pdo->query("SELECT id, username, email FROM users ORDER BY username ASC")->fetchAll();
?>

<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="font-gaming text-2xl sm:text-3xl font-bold text-white tracking-wide flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span> STORE REST API & DEVELOPER ACCESS
            </h1>
            <p class="text-xs text-zinc-400 mt-1">Manage API credentials for external reseller automation and diamond top-up webhooks</p>
        </div>
        <div>
            <a href="/api-docs.php" target="_blank" class="px-4 py-2.5 rounded-xl bg-gaming-800 hover:bg-gaming-750 text-xs font-semibold text-zinc-300 border border-gaming-border inline-flex items-center gap-2">
                <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                <span>View API Documentation</span>
            </a>
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
        <!-- Generate API Key Form Card -->
        <div class="lg:col-span-1">
            <div class="bg-gaming-850 border border-gaming-border rounded-2xl p-6 space-y-5 sticky top-20 shadow-xl">
                <div class="border-b border-gaming-border pb-3">
                    <h2 class="font-gaming text-lg font-bold text-white flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        Generate API Credentials
                    </h2>
                    <p class="text-[11px] text-zinc-400 mt-1">Create secret keys for automated reseller software.</p>
                </div>

                <form method="POST" action="/admin/api.php" class="space-y-4">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="action" value="generate_key">

                    <div>
                        <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1">Application / Client Name *</label>
                        <input type="text" name="client_name" required placeholder="e.g. VIP Bot, External Reseller Portal"
                               class="w-full px-3.5 py-2.5 rounded-xl bg-gaming-900 border border-gaming-border focus:border-red-500 text-sm text-white focus:outline-none">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1">Link to Store User (Optional)</label>
                        <select name="user_id" class="w-full px-3.5 py-2.5 rounded-xl bg-gaming-900 border border-gaming-border focus:border-red-500 text-sm text-white focus:outline-none">
                            <option value="">-- No User Link (Master Admin Key) --</option>
                            <?php foreach ($users_list as $u): ?>
                                <option value="<?php echo $u['id']; ?>"><?php echo e($u['username']); ?> (<?php echo e($u['email']); ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1">Rate Limit (Requests / Min)</label>
                        <input type="number" name="rate_limit" value="60" min="10" max="600"
                               class="w-full px-3.5 py-2.5 rounded-xl bg-gaming-900 border border-gaming-border focus:border-red-500 text-sm text-white focus:outline-none">
                    </div>

                    <button type="submit" class="w-full py-3 rounded-xl bg-red-600 hover:bg-red-500 text-white font-gaming font-bold text-sm uppercase tracking-wider shadow-red-subtle transition-colors">
                        Generate API Key & Secret
                    </button>
                </form>
            </div>
        </div>

        <!-- API Keys List Table Card -->
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-gaming-850 border border-gaming-border rounded-2xl overflow-hidden shadow-2xl">
                <div class="p-5 border-b border-gaming-border flex items-center justify-between">
                    <h2 class="font-gaming text-lg font-bold text-white">Active API Clients (<?php echo count($api_keys); ?>)</h2>
                </div>

                <div class="divide-y divide-gaming-border/60">
                    <?php if (empty($api_keys)): ?>
                        <div class="p-8 text-center text-zinc-500 text-xs">No API keys created yet. Generate one on the left.</div>
                    <?php else: ?>
                        <?php foreach ($api_keys as $k): ?>
                            <div class="p-5 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 hover:bg-gaming-800/30 transition-colors">
                                <div class="space-y-1.5 max-w-md">
                                    <div class="flex items-center gap-3">
                                        <h3 class="font-bold text-white text-base"><?php echo e($k['name']); ?></h3>
                                        <?php if (!empty($k['username'])): ?>
                                            <span class="text-xs text-red-400">User: <?php echo e($k['username']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="space-y-1 font-mono text-xs">
                                        <div class="text-zinc-300">
                                            <span class="text-zinc-500">Key:</span> <?php echo e($k['api_key']); ?>
                                        </div>
                                        <div class="text-zinc-400 text-[11px]">
                                            <span class="text-zinc-500">Secret:</span> <?php echo substr($k['api_secret'], 0, 10); ?>••••••••••••••••
                                        </div>
                                    </div>
                                    <div class="text-[11px] text-zinc-500">
                                        Limit: <?php echo $k['rate_limit_per_minute']; ?> req/min &bull; Created: <?php echo date('M d, Y', strtotime($k['created_at'])); ?>
                                    </div>
                                </div>

                                <div class="flex items-center gap-2 self-end sm:self-center">
                                    <a href="/admin/api.php?action=toggle&id=<?php echo $k['id']; ?>">
                                        <?php if ($k['status'] === 'active'): ?>
                                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-950 text-emerald-300 border border-emerald-800">Active</span>
                                        <?php else: ?>
                                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-zinc-800 text-zinc-400 border border-zinc-700">Revoked</span>
                                        <?php endif; ?>
                                    </a>
                                    <a href="/admin/api.php?action=delete&id=<?php echo $k['id']; ?>" 
                                       onclick="return confirm('Revoke and delete this API key? External systems using it will lose access.')"
                                       class="px-2.5 py-1 rounded-lg bg-red-950/60 hover:bg-red-900 text-red-300 border border-red-800/40 text-[11px] font-semibold">
                                        Revoke
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- API Request Logs -->
            <div class="bg-gaming-850 border border-gaming-border rounded-2xl overflow-hidden shadow-2xl">
                <div class="p-5 border-b border-gaming-border">
                    <h2 class="font-gaming text-lg font-bold text-white">Recent API Requests (Audit Log)</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="text-zinc-400 bg-gaming-900/60 uppercase border-b border-gaming-border">
                                <th class="py-3 px-4">Endpoint</th>
                                <th class="py-3 px-4">Method</th>
                                <th class="py-3 px-4">Client</th>
                                <th class="py-3 px-4">IP Address</th>
                                <th class="py-3 px-4 text-center">Status</th>
                                <th class="py-3 px-4 text-right">Time</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gaming-border/60">
                            <?php if (empty($api_logs)): ?>
                                <tr>
                                    <td colspan="6" class="py-8 text-center text-zinc-500">No external API traffic recorded yet.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($api_logs as $log): ?>
                                    <tr class="hover:bg-gaming-800/40 transition-colors">
                                        <td class="py-3 px-4 font-mono font-bold text-white"><?php echo e($log['endpoint']); ?></td>
                                        <td class="py-3 px-4 font-mono text-zinc-300"><?php echo e($log['request_method']); ?></td>
                                        <td class="py-3 px-4 text-zinc-300"><?php echo e($log['client_name'] ?? 'System'); ?></td>
                                        <td class="py-3 px-4 font-mono text-zinc-500"><?php echo e($log['ip_address']); ?></td>
                                        <td class="py-3 px-4 text-center">
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold <?php echo $log['response_code'] == 200 ? 'bg-emerald-950 text-emerald-300 border border-emerald-800' : 'bg-red-950 text-red-300 border border-red-800'; ?>">
                                                <?php echo $log['response_code']; ?>
                                            </span>
                                        </td>
                                        <td class="py-3 px-4 text-right text-zinc-500 font-mono"><?php echo date('H:i:s', strtotime($log['created_at'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
