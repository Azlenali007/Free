<?php
$page_title = "Wallet Audit & Balance Adjustments";
require_once __DIR__ . '/includes/admin_header.php';

$errors = [];

// Handle Admin Wallet Adjustment POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'manual_adjust') {
    csrf_validate();

    $user_id = (int)($_POST['user_id'] ?? 0);
    $type = sanitize($_POST['type'] ?? 'credit');
    $amount = (float)($_POST['amount'] ?? 0);
    $reason = trim(sanitize($_POST['reason'] ?? ''));

    if ($user_id <= 0) {
        $errors[] = "Please select a user to adjust.";
    }
    if ($amount <= 0) {
        $errors[] = "Adjustment amount must be greater than zero.";
    }
    if (empty($reason)) {
        $errors[] = "Adjustment reason is mandatory for the audit log.";
    }
    if (!in_array($type, ['credit', 'debit'])) {
        $errors[] = "Invalid adjustment type.";
    }

    if (empty($errors)) {
        $res = adjust_user_wallet(
            $user_id,
            $type,
            $amount,
            'Admin Manual Adjustment',
            $reason,
            'AUDIT-' . strtoupper(substr(uniqid(), -6)),
            $admin['id']
        );

        if ($res['success']) {
            log_admin_activity(
                'wallet_manual_adjustment', 
                "User ID {$user_id} {$type}ed " . format_currency($amount) . ". Reason: {$reason}. Prev: " . format_currency($res['previous_balance']) . " -> New: " . format_currency($res['new_balance'])
            );

            // Notify user
            $action_word = ($type === 'credit') ? "credited to" : "deducted from";
            create_notification(
                $user_id,
                "Wallet Balance Adjusted: " . format_currency($amount),
                format_currency($amount) . " was {$action_word} your store wallet by administration. Reason: {$reason}. New Balance: " . format_currency($res['new_balance']),
                'wallet',
                '/wallet.php'
            );

            set_flash('success', "Wallet adjusted successfully! Previous: " . format_currency($res['previous_balance']) . " &rarr; New Balance: " . format_currency($res['new_balance']));
            header("Location: /admin/wallet.php");
            exit;
        } else {
            $errors[] = $res['message'];
        }
    }
}

// User filter
$selected_user_id = (int)($_GET['user_id'] ?? 0);
$type_filter = sanitize($_GET['type'] ?? '');
$search = sanitize($_GET['q'] ?? '');

$where = ["1=1"];
$params = [];

if ($selected_user_id > 0) {
    $where[] = "wt.user_id = ?";
    $params[] = $selected_user_id;
}
if (!empty($type_filter)) {
    $where[] = "wt.type = ?";
    $params[] = $type_filter;
}
if (!empty($search)) {
    $where[] = "(wt.transaction_id LIKE ? OR wt.reference_no LIKE ? OR u.username LIKE ? OR u.email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Fetch transactions with full audit details
$sql = "
    SELECT wt.*, u.username, u.name as user_name, u.email as user_email, a.username as admin_name 
    FROM wallet_transactions wt 
    JOIN users u ON wt.user_id = u.id 
    LEFT JOIN admins a ON wt.admin_id = a.id 
    WHERE " . implode(' AND ', $where) . " 
    ORDER BY wt.id DESC 
    LIMIT 100
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$transactions = $stmt->fetchAll();

// Users list for dropdown
$users_list = $pdo->query("SELECT id, username, name, email, wallet_balance FROM users ORDER BY username ASC")->fetchAll();

// Aggregated wallet stats
$total_wallet_pool = (float)$pdo->query("SELECT COALESCE(SUM(wallet_balance), 0) FROM users")->fetchColumn();
$total_deposits_all = (float)$pdo->query("SELECT COALESCE(SUM(amount), 0) FROM wallet_transactions WHERE type = 'credit' AND status = 'completed'")->fetchColumn();
$total_spent_all = (float)$pdo->query("SELECT COALESCE(SUM(amount), 0) FROM wallet_transactions WHERE type = 'debit' AND status = 'completed'")->fetchColumn();
?>

<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="font-gaming text-2xl sm:text-3xl font-bold text-white tracking-wide flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span> WALLET AUDIT & BALANCE CONTROL
            </h1>
            <p class="text-xs text-zinc-400 mt-1">Audit customer wallet balances, view full debit/credit history, and perform auditable manual adjustments</p>
        </div>
        <div>
            <a href="/admin/payments.php" class="px-4 py-2.5 rounded-xl bg-gaming-800 hover:bg-gaming-750 text-xs font-semibold text-zinc-300 border border-gaming-border inline-flex items-center gap-2">
                <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                <span>View Gateway Transactions</span>
            </a>
        </div>
    </div>

    <!-- Metrics Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-gaming-850 border border-gaming-border rounded-xl p-4">
            <span class="text-xs text-zinc-400 uppercase font-semibold">Total User Wallet Liability</span>
            <div class="font-gaming text-2xl font-bold text-emerald-400 mt-1"><?php echo format_currency($total_wallet_pool); ?></div>
            <span class="text-[10px] text-zinc-500">Current balances in customer accounts</span>
        </div>

        <div class="bg-gaming-850 border border-gaming-border rounded-xl p-4">
            <span class="text-xs text-zinc-400 uppercase font-semibold">Total Credits / Deposits</span>
            <div class="font-gaming text-2xl font-bold text-white mt-1"><?php echo format_currency($total_deposits_all); ?></div>
            <span class="text-[10px] text-zinc-500">All-time wallet top-ups</span>
        </div>

        <div class="bg-gaming-850 border border-gaming-border rounded-xl p-4">
            <span class="text-xs text-zinc-400 uppercase font-semibold">Total Store Purchases (Debit)</span>
            <div class="font-gaming text-2xl font-bold text-red-400 mt-1"><?php echo format_currency($total_spent_all); ?></div>
            <span class="text-[10px] text-zinc-500">All-time order wallet debits</span>
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
        <!-- Manual Balance Adjustment Card -->
        <div class="lg:col-span-1">
            <div class="bg-gaming-850 border border-gaming-border rounded-2xl p-6 space-y-5 sticky top-20 shadow-xl">
                <div class="border-b border-gaming-border pb-3">
                    <h2 class="font-gaming text-lg font-bold text-white flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-red-500"></span>
                        Manual Balance Adjustment
                    </h2>
                    <p class="text-[11px] text-zinc-400 mt-1">Every adjustment is permanently logged with before/after audit records.</p>
                </div>

                <form method="POST" action="/admin/wallet.php" class="space-y-4">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="action" value="manual_adjust">

                    <div>
                        <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1">Target Gamer / User *</label>
                        <select name="user_id" required class="w-full px-3.5 py-2.5 rounded-xl bg-gaming-900 border border-gaming-border focus:border-red-500 text-sm text-white focus:outline-none">
                            <option value="">-- Choose User --</option>
                            <?php foreach ($users_list as $u): ?>
                                <option value="<?php echo $u['id']; ?>" <?php echo $selected_user_id === $u['id'] ? 'selected' : ''; ?>>
                                    <?php echo e($u['username']); ?> (Bal: <?php echo format_currency($u['wallet_balance']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1">Adjustment Type *</label>
                        <div class="grid grid-cols-2 gap-3">
                            <label class="flex items-center gap-2 p-2.5 rounded-xl bg-gaming-900 border border-emerald-500/40 cursor-pointer">
                                <input type="radio" name="type" value="credit" checked class="text-emerald-500">
                                <span class="text-xs font-bold text-emerald-400">+ Credit (Add)</span>
                            </label>
                            <label class="flex items-center gap-2 p-2.5 rounded-xl bg-gaming-900 border border-red-500/40 cursor-pointer">
                                <input type="radio" name="type" value="debit" class="text-red-500">
                                <span class="text-xs font-bold text-red-400">- Debit (Deduct)</span>
                            </label>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1">Amount (₹ INR) *</label>
                        <input type="number" step="0.01" min="0.01" name="amount" required placeholder="e.g. 100.00"
                               class="w-full px-3.5 py-2.5 rounded-xl bg-gaming-900 border border-gaming-border focus:border-red-500 text-sm text-white focus:outline-none">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1">Audit Reason *</label>
                        <textarea name="reason" rows="2" required placeholder="Mandatory reason for audit log..."
                                  class="w-full px-3.5 py-2.5 rounded-xl bg-gaming-900 border border-gaming-border focus:border-red-500 text-sm text-white focus:outline-none"></textarea>
                    </div>

                    <button type="submit" onclick="return confirm('Confirm balance adjustment? A permanent audit log and user notification will be created.')" 
                            class="w-full py-3 rounded-xl bg-red-600 hover:bg-red-500 text-white font-gaming font-bold text-sm uppercase tracking-wider shadow-red-subtle transition-colors">
                        Apply Balance Adjustment
                    </button>
                </form>
            </div>
        </div>

        <!-- Wallet Audit History Table Card -->
        <div class="lg:col-span-2 space-y-4">
            <!-- Filter Bar -->
            <div class="bg-gaming-850 border border-gaming-border rounded-xl p-4 flex flex-col sm:flex-row items-center justify-between gap-4">
                <form method="GET" action="/admin/wallet.php" class="flex flex-wrap items-center gap-3 w-full sm:w-auto flex-1">
                    <input type="text" name="q" value="<?php echo e($search); ?>" placeholder="Search Txn ID, reference, user..."
                           class="px-3.5 py-2 rounded-xl bg-gaming-900 border border-gaming-border text-xs text-white placeholder-zinc-500 focus:outline-none focus:border-red-500 flex-1 sm:w-60">
                    <select name="type" class="px-3 py-2 rounded-xl bg-gaming-900 border border-gaming-border text-xs text-white focus:outline-none">
                        <option value="">All Types</option>
                        <option value="credit" <?php echo $type_filter === 'credit' ? 'selected' : ''; ?>>Credits (+)</option>
                        <option value="debit" <?php echo $type_filter === 'debit' ? 'selected' : ''; ?>>Debits (-)</option>
                    </select>
                    <button type="submit" class="px-4 py-2 bg-gaming-800 hover:bg-gaming-750 text-xs font-semibold text-white rounded-xl border border-gaming-border">Filter</button>
                    <?php if (!empty($search) || !empty($type_filter) || $selected_user_id > 0): ?>
                        <a href="/admin/wallet.php" class="text-xs text-red-400 hover:underline">Clear</a>
                    <?php endif; ?>
                </form>
            </div>

            <div class="bg-gaming-850 border border-gaming-border rounded-2xl overflow-hidden shadow-2xl">
                <div class="p-4 border-b border-gaming-border flex items-center justify-between">
                    <h2 class="font-gaming text-lg font-bold text-white">Wallet Audit Logs (<?php echo count($transactions); ?>)</h2>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="text-zinc-400 bg-gaming-900/60 uppercase border-b border-gaming-border">
                                <th class="py-3 px-4">Txn ID</th>
                                <th class="py-3 px-4">User</th>
                                <th class="py-3 px-4">Type / Amount</th>
                                <th class="py-3 px-4">Balance Audit</th>
                                <th class="py-3 px-4">Method / Reason</th>
                                <th class="py-3 px-4">Admin / Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gaming-border/60">
                            <?php if (empty($transactions)): ?>
                                <tr>
                                    <td colspan="6" class="py-8 text-center text-zinc-500">No wallet audit records found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($transactions as $tx): ?>
                                    <tr class="hover:bg-gaming-800/40 transition-colors">
                                        <td class="py-3 px-4">
                                            <span class="font-mono font-bold text-white text-[11px]"><?php echo e($tx['transaction_id']); ?></span>
                                            <?php if (!empty($tx['reference_no'])): ?>
                                                <div class="text-[10px] text-zinc-500 font-mono"><?php echo e($tx['reference_no']); ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-3 px-4">
                                            <div class="font-bold text-white"><?php echo e($tx['username']); ?></div>
                                            <div class="text-[10px] text-zinc-500"><?php echo e($tx['user_email']); ?></div>
                                        </td>
                                        <td class="py-3 px-4">
                                            <?php if ($tx['type'] === 'credit'): ?>
                                                <span class="font-gaming font-bold text-emerald-400 text-sm">
                                                    +<?php echo format_currency($tx['amount']); ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="font-gaming font-bold text-red-400 text-sm">
                                                    -<?php echo format_currency($tx['amount']); ?>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-3 px-4 text-zinc-400 text-[11px]">
                                            <?php if ($tx['previous_balance'] !== null && $tx['new_balance'] !== null): ?>
                                                <div><?php echo format_currency($tx['previous_balance']); ?> &rarr; <span class="text-white font-bold"><?php echo format_currency($tx['new_balance']); ?></span></div>
                                            <?php else: ?>
                                                <span class="text-zinc-600">N/A</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-3 px-4 max-w-xs">
                                            <div class="font-semibold text-zinc-300"><?php echo e($tx['payment_method']); ?></div>
                                            <?php if (!empty($tx['notes'])): ?>
                                                <div class="text-[11px] text-zinc-400 truncate"><?php echo e($tx['notes']); ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-3 px-4 text-zinc-400 text-[11px] whitespace-nowrap">
                                            <?php if (!empty($tx['admin_name'])): ?>
                                                <span class="text-red-400 font-semibold"><?php echo e($tx['admin_name']); ?></span><br>
                                            <?php endif; ?>
                                            <span><?php echo date('M d, H:i', strtotime($tx['created_at'])); ?></span>
                                        </td>
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
