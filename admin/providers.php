<?php
$page_title = "Automated Provider APIs";
require_once __DIR__ . '/includes/admin_header.php';

$errors = [];

// Handle Delete Provider
if (isset($_GET['action']) && $_GET['action'] === 'delete') {
    $pid = (int)($_GET['id'] ?? 0);
    if ($pid > 0) {
        $stmt_del = $pdo->prepare("DELETE FROM providers WHERE id = ?");
        $stmt_del->execute([$pid]);
        log_admin_activity('delete_provider', "Deleted provider ID: {$pid}");
        set_flash('success', "Provider API removed.");
        header("Location: /admin/providers.php");
        exit;
    }
}

// Handle Status Toggle
if (isset($_GET['action']) && $_GET['action'] === 'toggle') {
    $pid = (int)($_GET['id'] ?? 0);
    if ($pid > 0) {
        $stmt_t = $pdo->prepare("UPDATE providers SET status = IF(status = 'active', 'inactive', 'active') WHERE id = ?");
        $stmt_t->execute([$pid]);
        log_admin_activity('toggle_provider_status', "Toggled status for provider ID: {$pid}");
        set_flash('success', "Provider status updated.");
        header("Location: /admin/providers.php");
        exit;
    }
}

// Handle Add / Edit Provider POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validate();

    $pid = (int)($_POST['provider_id'] ?? 0);
    $name = sanitize($_POST['name'] ?? '');
    $api_url = sanitize($_POST['api_url'] ?? '');
    $api_key = trim($_POST['api_key'] ?? '');
    $api_secret = trim($_POST['api_secret'] ?? '');
    $balance = (float)($_POST['balance'] ?? 0);
    $status = in_array($_POST['status'] ?? '', ['active', 'inactive']) ? $_POST['status'] : 'inactive';
    $notes = sanitize($_POST['notes'] ?? '');

    if (empty($name)) {
        $errors[] = "Provider name is required.";
    }
    if (empty($api_url)) {
        $errors[] = "Provider API endpoint URL is required.";
    }

    if (empty($errors)) {
        if ($pid > 0) {
            $stmt_u = $pdo->prepare("UPDATE providers SET name = ?, api_url = ?, api_key = ?, api_secret = ?, balance = ?, status = ?, notes = ? WHERE id = ?");
            $stmt_u->execute([$name, $api_url, $api_key, $api_secret, $balance, $status, $notes, $pid]);
            log_admin_activity('update_provider', "Updated provider '{$name}'");
            set_flash('success', "Provider '{$name}' updated successfully.");
        } else {
            $stmt_i = $pdo->prepare("INSERT INTO providers (name, api_url, api_key, api_secret, balance, status, notes) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt_i->execute([$name, $api_url, $api_key, $api_secret, $balance, $status, $notes]);
            $new_id = $pdo->lastInsertId();
            log_admin_activity('create_provider', "Created provider '{$name}' (ID: {$new_id})");
            set_flash('success', "New API provider added successfully.");
        }
        header("Location: /admin/providers.php");
        exit;
    }
}

// Fetch all providers
$providers = $pdo->query("SELECT * FROM providers ORDER BY id ASC")->fetchAll();

// Editing provider
$editing_provider = null;
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $stmt_ep = $pdo->prepare("SELECT * FROM providers WHERE id = ?");
    $stmt_ep->execute([$edit_id]);
    $editing_provider = $stmt_ep->fetch();
}
?>

<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="font-gaming text-2xl sm:text-3xl font-bold text-white tracking-wide flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-red-500"></span> TOP-UP PROVIDER API INTEGRATIONS
            </h1>
            <p class="text-xs text-zinc-400 mt-1">Configure automated server-side diamond delivery pipelines via authorized APIs</p>
        </div>
        <div>
            <a href="/admin/api.php" class="px-4 py-2.5 rounded-xl bg-gaming-800 hover:bg-gaming-750 text-xs font-semibold text-zinc-300 border border-gaming-border inline-flex items-center gap-2">
                <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/></svg>
                <span>Store REST API Keys</span>
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
        <!-- Add / Edit Provider Card -->
        <div class="lg:col-span-1">
            <div class="bg-gaming-850 border border-gaming-border rounded-2xl p-6 space-y-5 sticky top-20 shadow-xl">
                <div class="flex items-center justify-between border-b border-gaming-border pb-3">
                    <h2 class="font-gaming text-lg font-bold text-white flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full <?php echo $editing_provider ? 'bg-amber-500' : 'bg-red-500'; ?>"></span>
                        <?php echo $editing_provider ? 'Edit Provider' : 'Add New Provider API'; ?>
                    </h2>
                    <?php if ($editing_provider): ?>
                        <a href="/admin/providers.php" class="text-xs text-zinc-400 hover:text-white">Cancel</a>
                    <?php endif; ?>
                </div>

                <form method="POST" action="/admin/providers.php" class="space-y-4">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="provider_id" value="<?php echo $editing_provider['id'] ?? 0; ?>">

                    <div>
                        <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1">Provider Name *</label>
                        <input type="text" name="name" value="<?php echo e($editing_provider['name'] ?? ''); ?>" required
                               placeholder="e.g. FreeFire Direct API, SmileOne"
                               class="w-full px-3.5 py-2.5 rounded-xl bg-gaming-900 border border-gaming-border focus:border-red-500 text-sm text-white focus:outline-none">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1">API Base URL *</label>
                        <input type="url" name="api_url" value="<?php echo e($editing_provider['api_url'] ?? ''); ?>" required
                               placeholder="https://api.provider.com/v1"
                               class="w-full px-3.5 py-2.5 rounded-xl bg-gaming-900 border border-gaming-border focus:border-red-500 text-xs font-mono text-white focus:outline-none">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1">API Key / Token</label>
                        <input type="password" name="api_key" value="<?php echo e($editing_provider['api_key'] ?? ''); ?>"
                               placeholder="Enter secret API Key..."
                               class="w-full px-3.5 py-2.5 rounded-xl bg-gaming-900 border border-gaming-border focus:border-red-500 text-xs font-mono text-white focus:outline-none">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1">API Secret / Password (If applicable)</label>
                        <input type="password" name="api_secret" value="<?php echo e($editing_provider['api_secret'] ?? ''); ?>"
                               placeholder="Enter API Secret..."
                               class="w-full px-3.5 py-2.5 rounded-xl bg-gaming-900 border border-gaming-border focus:border-red-500 text-xs font-mono text-white focus:outline-none">
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1">Provider Balance (₹)</label>
                            <input type="number" step="0.01" min="0" name="balance" value="<?php echo e($editing_provider['balance'] ?? '0.00'); ?>"
                                   class="w-full px-3.5 py-2.5 rounded-xl bg-gaming-900 border border-gaming-border focus:border-red-500 text-sm text-white focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1">Status</label>
                            <select name="status" class="w-full px-3.5 py-2.5 rounded-xl bg-gaming-900 border border-gaming-border focus:border-red-500 text-sm text-white focus:outline-none">
                                <option value="active" <?php echo ($editing_provider['status'] ?? '') === 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo ($editing_provider['status'] ?? 'inactive') === 'inactive' ? 'selected' : ''; ?>>Inactive (Testing)</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1">Internal Notes</label>
                        <textarea name="notes" rows="2" placeholder="Documentation notes, rate limits, contact info..."
                                  class="w-full px-3.5 py-2.5 rounded-xl bg-gaming-900 border border-gaming-border focus:border-red-500 text-xs text-white focus:outline-none"><?php echo e($editing_provider['notes'] ?? ''); ?></textarea>
                    </div>

                    <div class="p-3 rounded-xl bg-gaming-950 border border-gaming-border text-[11px] text-zinc-400">
                        🔒 Provider credentials are only used by backend PHP cURL routines and are never exposed to customers.
                    </div>

                    <button type="submit" class="w-full py-3 rounded-xl bg-red-600 hover:bg-red-500 text-white font-gaming font-bold text-sm uppercase tracking-wider shadow-red-subtle transition-colors">
                        <?php echo $editing_provider ? 'Update Provider API' : 'Save Provider API'; ?>
                    </button>
                </form>
            </div>
        </div>

        <!-- Providers List Table Card -->
        <div class="lg:col-span-2 space-y-4">
            <div class="bg-gaming-850 border border-gaming-border rounded-2xl overflow-hidden shadow-2xl">
                <div class="p-5 border-b border-gaming-border flex items-center justify-between">
                    <h2 class="font-gaming text-lg font-bold text-white">Registered API Providers (<?php echo count($providers); ?>)</h2>
                </div>

                <div class="divide-y divide-gaming-border/60">
                    <?php if (empty($providers)): ?>
                        <div class="p-8 text-center text-zinc-500 text-xs">No external providers configured. Manual order fulfillment is currently active.</div>
                    <?php else: ?>
                        <?php foreach ($providers as $pr): ?>
                            <div class="p-5 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 hover:bg-gaming-800/30 transition-colors">
                                <div class="space-y-1 max-w-md">
                                    <div class="flex items-center gap-3">
                                        <h3 class="font-bold text-white text-base"><?php echo e($pr['name']); ?></h3>
                                        <span class="text-xs font-gaming font-bold text-emerald-400">
                                            Balance: <?php echo format_currency($pr['balance']); ?>
                                        </span>
                                    </div>
                                    <p class="font-mono text-zinc-400 text-xs break-all"><?php echo e($pr['api_url']); ?></p>
                                    <?php if (!empty($pr['notes'])): ?>
                                        <p class="text-[11px] text-zinc-500 italic"><?php echo e($pr['notes']); ?></p>
                                    <?php endif; ?>
                                </div>

                                <div class="flex items-center gap-3 self-end sm:self-center">
                                    <a href="/admin/providers.php?action=toggle&id=<?php echo $pr['id']; ?>">
                                        <?php if ($pr['status'] === 'active'): ?>
                                            <span class="px-3 py-1 rounded-full text-[10px] font-bold bg-emerald-950 text-emerald-300 border border-emerald-800">
                                                Active
                                            </span>
                                        <?php else: ?>
                                            <span class="px-3 py-1 rounded-full text-[10px] font-bold bg-zinc-800 text-zinc-400 border border-zinc-700">
                                                Inactive
                                            </span>
                                        <?php endif; ?>
                                    </a>
                                    <a href="/admin/providers.php?edit=<?php echo $pr['id']; ?>" class="px-3 py-1.5 rounded-lg bg-gaming-800 hover:bg-gaming-750 text-zinc-200 border border-gaming-border text-xs font-semibold">
                                        Edit
                                    </a>
                                    <a href="/admin/providers.php?action=delete&id=<?php echo $pr['id']; ?>" 
                                       onclick="return confirm('Delete this provider integration?')"
                                       class="px-3 py-1.5 rounded-lg bg-red-950/60 hover:bg-red-900 text-red-300 border border-red-800/40 text-xs font-semibold">
                                        Delete
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
