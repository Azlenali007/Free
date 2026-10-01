<?php
$page_title = "Payment Gateways Configuration";
require_once __DIR__ . '/includes/admin_header.php';

$errors = [];

// Handle Toggle Gateway Status
if (isset($_GET['action']) && $_GET['action'] === 'toggle') {
    $gid = (int)($_GET['id'] ?? 0);
    if ($gid > 0) {
        $stmt_t = $pdo->prepare("UPDATE payment_gateways SET status = IF(status = 'active', 'inactive', 'active') WHERE id = ?");
        $stmt_t->execute([$gid]);
        log_admin_activity('toggle_gateway_status', "Toggled status for payment gateway ID: {$gid}");
        set_flash('success', "Payment gateway status updated.");
        header("Location: /admin/gateways.php");
        exit;
    }
}

// Handle Update Gateway POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validate();

    $gid = (int)($_POST['gateway_id'] ?? 0);
    $name = sanitize($_POST['name'] ?? '');
    $title = sanitize($_POST['title'] ?? '');
    $instructions = trim($_POST['instructions'] ?? '');
    $fee_percent = (float)($_POST['fee_percent'] ?? 0);
    $status = in_array($_POST['status'] ?? '', ['active', 'inactive']) ? $_POST['status'] : 'active';
    $credentials = trim($_POST['credentials'] ?? '');

    if (empty($name)) {
        $errors[] = "Gateway name is required.";
    }

    if (empty($errors) && $gid > 0) {
        // Enforce 100% INR currency
        $stmt_u = $pdo->prepare("
            UPDATE payment_gateways 
            SET name = ?, title = ?, instructions = ?, fee_percent = ?, credentials = ?, status = ?, currency = 'INR' 
            WHERE id = ?
        ");
        $stmt_u->execute([$name, $title, $instructions, $fee_percent, $credentials, $status, $gid]);
        log_admin_activity('update_gateway', "Updated payment gateway: {$name} (ID: {$gid})");
        set_flash('success', "Gateway '{$name}' configuration updated successfully.");
        header("Location: /admin/gateways.php");
        exit;
    }
}

// Editing Gateway
$editing_gw = null;
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $stmt_eg = $pdo->prepare("SELECT * FROM payment_gateways WHERE id = ?");
    $stmt_eg->execute([$edit_id]);
    $editing_gw = $stmt_eg->fetch();
}

// Fetch all payment gateways
$gateways = $pdo->query("SELECT * FROM payment_gateways ORDER BY sort_order ASC, id ASC")->fetchAll();
?>

<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="font-gaming text-2xl sm:text-3xl font-bold text-white tracking-wide flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span> PAYMENT GATEWAYS ARCHITECTURE
            </h1>
            <p class="text-xs text-zinc-400 mt-1">Configure UPI, Razorpay, Manual Bank Transfer, and Store Wallet in Indian Rupees (₹ INR)</p>
        </div>
        <div>
            <a href="/admin/payments.php" class="px-4 py-2.5 rounded-xl bg-gaming-800 hover:bg-gaming-750 text-xs font-semibold text-zinc-300 border border-gaming-border inline-flex items-center gap-2">
                <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                <span>View Live Transactions</span>
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
        <!-- Edit Gateway Card (or details) -->
        <div class="lg:col-span-1">
            <div class="bg-gaming-850 border border-gaming-border rounded-2xl p-6 space-y-5 sticky top-20 shadow-xl">
                <div class="flex items-center justify-between border-b border-gaming-border pb-3">
                    <h2 class="font-gaming text-lg font-bold text-white flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full <?php echo $editing_gw ? 'bg-amber-500' : 'bg-emerald-500'; ?>"></span>
                        <?php echo $editing_gw ? 'Configure Gateway' : 'Gateway Architecture'; ?>
                    </h2>
                    <?php if ($editing_gw): ?>
                        <a href="/admin/gateways.php" class="text-xs text-zinc-400 hover:text-white">Cancel</a>
                    <?php endif; ?>
                </div>

                <?php if ($editing_gw): ?>
                    <form method="POST" action="/admin/gateways.php" class="space-y-4">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="gateway_id" value="<?php echo $editing_gw['id']; ?>">

                        <div>
                            <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1">Gateway Identifier</label>
                            <input type="text" value="<?php echo e($editing_gw['code']); ?>" disabled
                                   class="w-full px-3.5 py-2.5 rounded-xl bg-gaming-950 border border-gaming-border text-xs text-zinc-400 font-mono">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1">Display Name *</label>
                            <input type="text" name="name" value="<?php echo e($editing_gw['name']); ?>" required
                                   class="w-full px-3.5 py-2.5 rounded-xl bg-gaming-900 border border-gaming-border focus:border-red-500 text-sm text-white focus:outline-none">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1">Checkout Subtitle / Tag</label>
                            <input type="text" name="title" value="<?php echo e($editing_gw['title']); ?>"
                                   class="w-full px-3.5 py-2.5 rounded-xl bg-gaming-900 border border-gaming-border focus:border-red-500 text-sm text-white focus:outline-none">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1">Currency (Locked to INR)</label>
                            <div class="px-3.5 py-2.5 rounded-xl bg-gaming-950 border border-emerald-500/40 text-xs text-emerald-400 font-semibold flex items-center gap-2">
                                <span>₹ INR (Indian Rupee - Global Standard)</span>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1">Gateway Fee (%)</label>
                            <input type="number" step="0.01" min="0" max="100" name="fee_percent" value="<?php echo e($editing_gw['fee_percent']); ?>"
                                   class="w-full px-3.5 py-2.5 rounded-xl bg-gaming-900 border border-gaming-border focus:border-red-500 text-sm text-white focus:outline-none">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1">Payment Instructions (Shown to User)</label>
                            <textarea name="instructions" rows="4"
                                      class="w-full px-3.5 py-2.5 rounded-xl bg-gaming-900 border border-gaming-border focus:border-red-500 text-xs text-white focus:outline-none"><?php echo e($editing_gw['instructions'] ?? ''); ?></textarea>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1">API Credentials / Config JSON</label>
                            <textarea name="credentials" rows="3" placeholder='{"key_id": "...", "key_secret": "..."}'
                                      class="w-full px-3.5 py-2.5 rounded-xl bg-gaming-900 border border-gaming-border focus:border-red-500 font-mono text-xs text-white focus:outline-none"><?php echo e($editing_gw['credentials'] ?? ''); ?></textarea>
                            <span class="text-[10px] text-zinc-500">Stored safely on server-side. Never exposed to customers.</span>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1">Gateway Status</label>
                            <select name="status" class="w-full px-3.5 py-2.5 rounded-xl bg-gaming-900 border border-gaming-border focus:border-red-500 text-sm text-white focus:outline-none">
                                <option value="active" <?php echo $editing_gw['status'] === 'active' ? 'selected' : ''; ?>>Active (Enabled)</option>
                                <option value="inactive" <?php echo $editing_gw['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive (Disabled)</option>
                            </select>
                        </div>

                        <button type="submit" class="w-full py-3 rounded-xl bg-red-600 hover:bg-red-500 text-white font-gaming font-bold text-sm uppercase tracking-wider shadow-red-subtle transition-colors">
                            Save Gateway Settings
                        </button>
                    </form>
                <?php else: ?>
                    <div class="space-y-4 text-xs text-zinc-400">
                        <div class="p-3 rounded-xl bg-gaming-900 border border-gaming-border space-y-2">
                            <p class="font-bold text-white">🔒 Server-Side Payment Verification</p>
                            <p>All wallet balance credits and order completions require authoritative server-side callback validation. Frontend opening of URLs never credits money.</p>
                        </div>
                        <div class="p-3 rounded-xl bg-gaming-900 border border-emerald-500/30 text-emerald-300 space-y-2">
                            <p class="font-bold text-white">🇮🇳 100% INR Native Architecture</p>
                            <p>Every transaction is calculated and verified in Indian Rupees (₹). Select any gateway from the table to edit instructions or credentials.</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Gateways List Table Card -->
        <div class="lg:col-span-2 space-y-4">
            <div class="bg-gaming-850 border border-gaming-border rounded-2xl overflow-hidden shadow-2xl">
                <div class="p-5 border-b border-gaming-border flex items-center justify-between">
                    <h2 class="font-gaming text-lg font-bold text-white">Configured Payment Methods (<?php echo count($gateways); ?>)</h2>
                </div>

                <div class="divide-y divide-gaming-border/60">
                    <?php foreach ($gateways as $gw): ?>
                        <div class="p-5 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 hover:bg-gaming-800/30 transition-colors">
                            <div class="space-y-1 max-w-md">
                                <div class="flex items-center gap-3">
                                    <h3 class="font-bold text-white text-base"><?php echo e($gw['name']); ?></h3>
                                    <span class="font-mono text-[10px] text-zinc-500 uppercase px-2 py-0.5 rounded bg-gaming-900 border border-gaming-border">
                                        <?php echo e($gw['code']); ?>
                                    </span>
                                    <span class="text-[10px] font-bold text-emerald-400 font-mono">₹ INR</span>
                                </div>
                                <p class="text-xs text-zinc-400"><?php echo e($gw['title']); ?></p>
                                <?php if (!empty($gw['instructions'])): ?>
                                    <p class="text-[11px] text-zinc-400 line-clamp-1 italic"><?php echo e($gw['instructions']); ?></p>
                                <?php endif; ?>
                            </div>

                            <div class="flex items-center gap-3 self-end sm:self-center">
                                <a href="/admin/gateways.php?action=toggle&id=<?php echo $gw['id']; ?>">
                                    <?php if ($gw['status'] === 'active'): ?>
                                        <span class="px-3 py-1 rounded-full text-[10px] font-bold bg-emerald-950 text-emerald-300 border border-emerald-800">
                                            Active
                                        </span>
                                    <?php else: ?>
                                        <span class="px-3 py-1 rounded-full text-[10px] font-bold bg-zinc-800 text-zinc-400 border border-zinc-700">
                                            Disabled
                                        </span>
                                    <?php endif; ?>
                                </a>
                                <a href="/admin/gateways.php?edit=<?php echo $gw['id']; ?>" class="px-3.5 py-1.5 rounded-lg bg-gaming-800 hover:bg-gaming-750 text-zinc-200 border border-gaming-border text-xs font-semibold">
                                    Configure
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
