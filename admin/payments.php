<?php
$page_title = "Manage Payments & Wallet";
require_once __DIR__ . '/includes/admin_header.php';

// Handle Deposit Approval
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'approve_deposit') {
    csrf_validate();
    $txn_id = (int)($_POST['id'] ?? 0);

    if ($txn_id) {
        try {
            $pdo->beginTransaction();

            $stmt_tx = $pdo->prepare("SELECT * FROM wallet_transactions WHERE id = ? FOR UPDATE");
            $stmt_tx->execute([$txn_id]);
            $txn = $stmt_tx->fetch();

            if ($txn && $txn['status'] === 'pending' && $txn['type'] === 'credit') {
                // Update transaction status
                $stmt_upd = $pdo->prepare("UPDATE wallet_transactions SET status = 'completed', admin_id = ? WHERE id = ?");
                $stmt_upd->execute([$admin['id'], $txn_id]);

                // Credit user balance
                $stmt_usr = $pdo->prepare("UPDATE users SET wallet_balance = wallet_balance + ? WHERE id = ?");
                $stmt_usr->execute([$txn['amount'], $txn['user_id']]);

                $pdo->commit();
                set_flash('success', "Deposit of " . format_currency($txn['amount']) . " approved and credited to user's wallet!");
            } else {
                $pdo->rollBack();
                set_flash('error', "Transaction could not be processed or is already completed.");
            }
        } catch (Exception $e) {
            $pdo->rollBack();
            set_flash('error', "Failed to approve deposit: " . $e->getMessage());
        }
        header("Location: /admin/payments.php");
        exit;
    }
}

// Handle Deposit Rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reject_deposit') {
    csrf_validate();
    $txn_id = (int)($_POST['id'] ?? 0);
    $reject_reason = sanitize($_POST['reason'] ?? 'Payment reference could not be verified');

    if ($txn_id) {
        $stmt = $pdo->prepare("UPDATE wallet_transactions SET status = 'rejected', notes = CONCAT(COALESCE(notes,''), ' | Rejected: ', ?), admin_id = ? WHERE id = ?");
        $stmt->execute([$reject_reason, $admin['id'], $txn_id]);
        set_flash('warning', "Deposit transaction #$txn_id marked as rejected.");
        header("Location: /admin/payments.php");
        exit;
    }
}

$status_filter = sanitize($_GET['status'] ?? 'all');
$where = ["1=1"];
$params = [];

if (in_array($status_filter, ['pending', 'completed', 'rejected'])) {
    $where[] = "wt.status = ?";
    $params[] = $status_filter;
}

$sql = "
    SELECT wt.*, u.username, u.name as user_name, u.email as user_email, u.wallet_balance 
    FROM wallet_transactions wt 
    LEFT JOIN users u ON wt.user_id = u.id 
    WHERE " . implode(' AND ', $where) . " 
    ORDER BY wt.id DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$transactions = $stmt->fetchAll();
?>

<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="font-gaming text-2xl sm:text-3xl font-bold text-white tracking-wide flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-red-500"></span> WALLET DEPOSITS & PAYMENTS
            </h1>
            <p class="text-xs text-zinc-400 mt-1">Verify payment reference codes and approve gamer wallet top-ups</p>
        </div>
    </div>

    <!-- Status Tabs -->
    <div class="flex flex-wrap items-center gap-2 border-b border-gaming-border pb-3">
        <a href="/admin/payments.php" 
           class="px-4 py-2 rounded-xl text-xs font-semibold <?php echo $status_filter === 'all' ? 'bg-red-600 text-white shadow-red-subtle' : 'bg-gaming-850 text-zinc-400 hover:text-white border border-gaming-border'; ?>">
            All Transactions
        </a>
        <a href="/admin/payments.php?status=pending" 
           class="px-4 py-2 rounded-xl text-xs font-semibold <?php echo $status_filter === 'pending' ? 'bg-amber-600 text-white shadow-red-subtle' : 'bg-gaming-850 text-zinc-400 hover:text-white border border-gaming-border'; ?>">
            Pending Approvals
        </a>
        <a href="/admin/payments.php?status=completed" 
           class="px-4 py-2 rounded-xl text-xs font-semibold <?php echo $status_filter === 'completed' ? 'bg-emerald-600 text-white shadow-red-subtle' : 'bg-gaming-850 text-zinc-400 hover:text-white border border-gaming-border'; ?>">
            Completed
        </a>
        <a href="/admin/payments.php?status=rejected" 
           class="px-4 py-2 rounded-xl text-xs font-semibold <?php echo $status_filter === 'rejected' ? 'bg-zinc-700 text-white shadow-red-subtle' : 'bg-gaming-850 text-zinc-400 hover:text-white border border-gaming-border'; ?>">
            Rejected
        </a>
    </div>

    <!-- Transactions Table -->
    <div class="bg-gaming-850 border border-gaming-border rounded-2xl overflow-hidden shadow-2xl">
        <?php if (!empty($transactions)): ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-gaming-900 border-b border-gaming-border text-zinc-400 uppercase font-gaming">
                            <th class="py-3.5 px-4">Txn ID</th>
                            <th class="py-3.5 px-4">Customer</th>
                            <th class="py-3.5 px-4">Type</th>
                            <th class="py-3.5 px-4">Amount</th>
                            <th class="py-3.5 px-4">Method / Reference Code</th>
                            <th class="py-3.5 px-4">Date</th>
                            <th class="py-3.5 px-4">Status</th>
                            <th class="py-3.5 px-4 text-right">Approval Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gaming-border/60">
                        <?php foreach ($transactions as $tx): 
                            $is_credit = $tx['type'] === 'credit';
                            $status_class = match($tx['status']) {
                                'completed' => 'bg-emerald-950/80 text-emerald-400 border-emerald-800/40',
                                'rejected' => 'bg-red-950/80 text-red-400 border-red-800/40',
                                default => 'bg-amber-950/80 text-amber-400 border-amber-800/40'
                            };
                        ?>
                            <tr class="hover:bg-gaming-800/40 transition-colors">
                                <td class="py-3.5 px-4 font-mono font-bold text-white"><?php echo e($tx['transaction_id']); ?></td>
                                <td class="py-3.5 px-4">
                                    <span class="font-bold text-white block"><?php echo e($tx['user_name'] ?: $tx['username']); ?></span>
                                    <span class="text-[10px] text-zinc-500 font-mono">@<?php echo e($tx['username']); ?></span>
                                </td>
                                <td class="py-3.5 px-4">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase <?php echo $is_credit ? 'bg-emerald-950 text-emerald-400 border border-emerald-800/40' : 'bg-red-950 text-red-400 border border-red-800/40'; ?>">
                                        <?php echo $is_credit ? '+ Deposit' : '- Debit'; ?>
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 font-gaming font-bold text-sm <?php echo $is_credit ? 'text-emerald-400' : 'text-zinc-300'; ?>">
                                    <?php echo ($is_credit ? '+' : '-') . format_currency($tx['amount']); ?>
                                </td>
                                <td class="py-3.5 px-4">
                                    <div class="text-zinc-200 font-medium"><?php echo e($tx['payment_method']); ?></div>
                                    <?php if (!empty($tx['reference_no'])): ?>
                                        <div class="font-mono text-zinc-400 font-bold text-[11px]"><?php echo e($tx['reference_no']); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3.5 px-4 text-zinc-400"><?php echo date('M d, H:i', strtotime($tx['created_at'])); ?></td>
                                <td class="py-3.5 px-4">
                                    <span class="inline-block px-2.5 py-0.5 rounded text-[10px] font-bold uppercase border <?php echo $status_class; ?>">
                                        <?php echo e($tx['status']); ?>
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-right">
                                    <?php if ($tx['status'] === 'pending' && $is_credit): ?>
                                        <div class="flex items-center justify-end gap-2">
                                            <form method="POST" action="/admin/payments.php" class="inline">
                                                <?php echo csrf_field(); ?>
                                                <input type="hidden" name="action" value="approve_deposit">
                                                <input type="hidden" name="id" value="<?php echo $tx['id']; ?>">
                                                <button type="submit" onclick="return confirm('Approve deposit of <?php echo format_currency($tx['amount']); ?> and credit to user?');"
                                                        class="px-2.5 py-1 rounded bg-emerald-950 text-emerald-300 border border-emerald-700/60 hover:bg-emerald-900 font-bold">
                                                    ✓ Approve
                                                </button>
                                            </form>

                                            <form method="POST" action="/admin/payments.php" class="inline">
                                                <?php echo csrf_field(); ?>
                                                <input type="hidden" name="action" value="reject_deposit">
                                                <input type="hidden" name="id" value="<?php echo $tx['id']; ?>">
                                                <button type="submit" onclick="return confirm('Reject this deposit request?');"
                                                        class="px-2.5 py-1 rounded bg-red-950/80 text-red-300 border border-red-800/40 hover:bg-red-900 font-bold">
                                                    ✕ Reject
                                                </button>
                                            </form>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-zinc-500 text-[11px]">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-12 text-zinc-500 text-xs">
                No transactions found under this filter.
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
