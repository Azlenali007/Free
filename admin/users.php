<?php
$page_title = "Manage Users";
require_once __DIR__ . '/includes/admin_header.php';

// Handle user ban/enable toggle
if (isset($_GET['action']) && $_GET['action'] === 'toggle_status') {
    $uid = (int)($_GET['id'] ?? 0);
    if ($uid) {
        $stmt = $pdo->prepare("UPDATE users SET status = IF(status = 'active', 'banned', 'active') WHERE id = ?");
        $stmt->execute([$uid]);
        set_flash('success', "User account status toggled.");
        header("Location: /admin/users.php");
        exit;
    }
}

// Handle Manual Wallet Balance Adjustment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'adjust_wallet') {
    csrf_validate();
    $uid = (int)($_POST['user_id'] ?? 0);
    $adj_amount = (float)($_POST['amount'] ?? 0);
    $type = sanitize($_POST['type'] ?? 'credit');
    $reason = sanitize($_POST['reason'] ?? 'Admin manual adjustment');

    if ($uid && $adj_amount > 0) {
        try {
            $pdo->beginTransaction();

            $stmt_u = $pdo->prepare("SELECT wallet_balance FROM users WHERE id = ?");
            $stmt_u->execute([$uid]);
            $current_bal = (float)$stmt_u->fetchColumn();

            $new_bal = ($type === 'credit') ? ($current_bal + $adj_amount) : max(0, $current_bal - $adj_amount);
            $pdo->prepare("UPDATE users SET wallet_balance = ? WHERE id = ?")->execute([$new_bal, $uid]);

            $txn_id = generate_transaction_id();
            $stmt_tx = $pdo->prepare("
                INSERT INTO wallet_transactions 
                (transaction_id, user_id, type, amount, payment_method, reference_no, status, notes, admin_id) 
                VALUES (?, ?, ?, ?, 'Admin Adjustment', 'MANUAL-ADJ', 'completed', ?, ?)
            ");
            $stmt_tx->execute([$txn_id, $uid, $type, $adj_amount, $reason, $admin['id']]);

            $pdo->commit();

            set_flash('success', "User wallet updated to " . format_currency($new_bal));
            header("Location: /admin/users.php");
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            set_flash('error', "Failed to adjust balance: " . $e->getMessage());
        }
    }
}

$search = sanitize($_GET['q'] ?? '');
$status_filter = sanitize($_GET['status'] ?? '');

$where = ["1=1"];
$params = [];

if (!empty($search)) {
    $where[] = "(u.username LIKE ? OR u.email LIKE ? OR u.name LIKE ? OR u.ff_uid LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($status_filter)) {
    $where[] = "u.status = ?";
    $params[] = $status_filter;
}

$sql = "
    SELECT u.*, 
           (SELECT COUNT(*) FROM orders o WHERE o.user_id = u.id) as order_count 
    FROM users u 
    WHERE " . implode(' AND ', $where) . " 
    ORDER BY u.id DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();
?>

<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="font-gaming text-2xl sm:text-3xl font-bold text-white tracking-wide flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-red-500"></span> USER & PLAYER DIRECTORY
            </h1>
            <p class="text-xs text-zinc-400 mt-1">Manage registered gamers, wallet balances, and account access</p>
        </div>
    </div>

    <!-- Search Toolbar -->
    <div class="bg-gaming-850 border border-gaming-border rounded-xl p-4 flex flex-col sm:flex-row items-center justify-between gap-4">
        <form method="GET" action="/admin/users.php" class="flex flex-wrap items-center gap-3 w-full sm:w-auto flex-1">
            <input type="text" name="q" value="<?php echo e($search); ?>" 
                   placeholder="Search username, email, or Free Fire UID..." 
                   class="px-3.5 py-2 rounded-lg bg-gaming-900 border border-gaming-border text-xs text-white focus:outline-none w-full sm:w-72">
            
            <select name="status" onchange="this.form.submit()"
                    class="px-3 py-2 rounded-lg bg-gaming-900 border border-gaming-border text-xs text-white focus:outline-none">
                <option value="">All Statuses</option>
                <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active Only</option>
                <option value="banned" <?php echo $status_filter === 'banned' ? 'selected' : ''; ?>>Banned Only</option>
            </select>

            <button type="submit" class="px-4 py-2 rounded-lg bg-gaming-800 hover:bg-gaming-750 text-xs font-semibold text-zinc-200 border border-gaming-border">
                Filter
            </button>
            <?php if (!empty($search) || !empty($status_filter)): ?>
                <a href="/admin/users.php" class="text-xs text-red-400 hover:underline">Reset</a>
            <?php endif; ?>
        </form>

        <span class="text-xs text-zinc-400 font-mono"><?php echo count($users); ?> Gamers Registered</span>
    </div>

    <!-- Users Table -->
    <div class="bg-gaming-850 border border-gaming-border rounded-2xl overflow-hidden shadow-2xl">
        <?php if (!empty($users)): ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-gaming-900 border-b border-gaming-border text-zinc-400 uppercase font-gaming">
                            <th class="py-3.5 px-4">Gamer Profile</th>
                            <th class="py-3.5 px-4">Free Fire UID</th>
                            <th class="py-3.5 px-4">Wallet Balance</th>
                            <th class="py-3.5 px-4">Orders</th>
                            <th class="py-3.5 px-4">Registered</th>
                            <th class="py-3.5 px-4">Status</th>
                            <th class="py-3.5 px-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gaming-border/60">
                        <?php foreach ($users as $u): 
                            $is_active = $u['status'] === 'active';
                        ?>
                            <tr class="hover:bg-gaming-800/40 transition-colors">
                                <td class="py-3.5 px-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-lg bg-gradient-to-tr from-red-600 to-amber-500 p-0.5 shrink-0">
                                            <div class="w-full h-full bg-gaming-950 rounded-[7px] flex items-center justify-center font-bold text-white text-xs">
                                                <?php echo strtoupper(substr($u['username'], 0, 1)); ?>
                                            </div>
                                        </div>
                                        <div>
                                            <span class="font-bold text-white block"><?php echo e($u['name'] ?: $u['username']); ?></span>
                                            <div class="text-[10px] text-zinc-400 font-mono">
                                                @<?php echo e($u['username']); ?> • <?php echo e($u['email']); ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3.5 px-4">
                                    <?php if (!empty($u['ff_uid'])): ?>
                                        <span class="font-mono font-bold text-red-400 block"><?php echo e($u['ff_uid']); ?></span>
                                        <span class="text-[10px] text-zinc-500"><?php echo e($u['ff_nickname'] ?: $u['ff_region']); ?></span>
                                    <?php else: ?>
                                        <span class="text-zinc-500 italic">Not set</span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3.5 px-4 font-gaming font-bold text-emerald-400 text-sm">
                                    <?php echo format_currency($u['wallet_balance']); ?>
                                </td>
                                <td class="py-3.5 px-4">
                                    <a href="/admin/orders.php?q=<?php echo urlencode($u['username']); ?>" class="text-white hover:text-red-400 font-bold underline">
                                        <?php echo $u['order_count']; ?> orders
                                    </a>
                                </td>
                                <td class="py-3.5 px-4 text-zinc-400"><?php echo date('M d, Y', strtotime($u['created_at'])); ?></td>
                                <td class="py-3.5 px-4">
                                    <span class="inline-block px-2.5 py-0.5 rounded text-[10px] font-bold uppercase border <?php echo $is_active ? 'bg-emerald-950 text-emerald-400 border-emerald-800' : 'bg-red-950 text-red-400 border-red-800'; ?>">
                                        <?php echo e($u['status']); ?>
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-right space-x-2">
                                    <!-- Adjust Wallet Trigger -->
                                    <button type="button" onclick="openWalletModal(<?php echo $u['id']; ?>, '<?php echo e($u['username']); ?>', '<?php echo $u['wallet_balance']; ?>')"
                                            class="px-2.5 py-1 rounded bg-gaming-800 hover:bg-gaming-750 text-zinc-200 border border-gaming-border font-semibold">
                                        ± Wallet
                                    </button>

                                    <!-- Status Toggle -->
                                    <a href="/admin/users.php?action=toggle_status&id=<?php echo $u['id']; ?>" 
                                       onclick="return confirm('Change status for <?php echo e($u['username']); ?>?');"
                                       class="px-2.5 py-1 rounded font-semibold border <?php echo $is_active ? 'bg-red-950/80 hover:bg-red-900 text-red-300 border-red-800/40' : 'bg-emerald-950/80 hover:bg-emerald-900 text-emerald-300 border-emerald-800/40'; ?>">
                                        <?php echo $is_active ? 'Ban' : 'Unban'; ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-12 text-zinc-500 text-xs">No users found.</div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal: Adjust Wallet Balance -->
<div id="walletModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
    <div class="bg-gaming-900 border border-gaming-border rounded-2xl max-w-md w-full p-6 space-y-4 shadow-2xl relative">
        <button type="button" onclick="document.getElementById('walletModal').classList.add('hidden')" 
                class="absolute top-4 right-4 text-zinc-400 hover:text-white">✕</button>

        <h3 class="font-gaming text-lg font-bold text-white">ADJUST USER WALLET</h3>
        <p class="text-xs text-zinc-400" id="walletModalUserDesc"></p>

        <form method="POST" action="/admin/users.php" class="space-y-4">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="adjust_wallet">
            <input type="hidden" name="user_id" id="modalUserId">

            <div class="grid grid-cols-2 gap-3">
                <label class="p-3 rounded-xl border border-gaming-border bg-gaming-950 cursor-pointer text-center">
                    <input type="radio" name="type" value="credit" checked class="text-red-600">
                    <span class="block text-xs font-bold text-emerald-400 mt-1">+ Credit (Add)</span>
                </label>
                <label class="p-3 rounded-xl border border-gaming-border bg-gaming-950 cursor-pointer text-center">
                    <input type="radio" name="type" value="debit" class="text-red-600">
                    <span class="block text-xs font-bold text-red-400 mt-1">- Debit (Deduct)</span>
                </label>
            </div>

            <div>
                <label class="block text-xs font-semibold text-zinc-300 uppercase mb-1">Adjustment Amount ($)</label>
                <input type="number" step="0.01" min="0.01" name="amount" required
                       class="w-full px-3.5 py-2.5 rounded-xl bg-gaming-950 border border-gaming-border text-sm text-white focus:outline-none">
            </div>

            <div>
                <label class="block text-xs font-semibold text-zinc-300 uppercase mb-1">Reason / Reference Note</label>
                <input type="text" name="reason" required
                       class="w-full px-3.5 py-2.5 rounded-xl bg-gaming-950 border border-gaming-border text-xs text-white focus:outline-none"
                       placeholder="e.g. Compensated for delayed top-up">
            </div>

            <div class="pt-2 flex gap-3">
                <button type="submit" class="flex-1 btn-gaming-red text-white text-xs font-gaming font-bold py-2.5 rounded-xl shadow-red-glow">
                    CONFIRM ADJUSTMENT
                </button>
                <button type="button" onclick="document.getElementById('walletModal').classList.add('hidden')"
                        class="px-4 py-2.5 rounded-xl bg-gaming-800 text-xs text-zinc-400">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openWalletModal(userId, username, currentBalance) {
    document.getElementById('modalUserId').value = userId;
    document.getElementById('walletModalUserDesc').innerText = 'User: ' + username + ' (Current Balance: $' + parseFloat(currentBalance).toFixed(2) + ')';
    document.getElementById('walletModal').classList.remove('hidden');
}
</script>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
