<?php
$page_title = "Manage Order Refunds";
require_once __DIR__ . '/includes/admin_header.php';

$errors = [];

// Handle Approve Refund
if (isset($_GET['action']) && $_GET['action'] === 'approve') {
    $ref_id = (int)($_GET['id'] ?? 0);
    if ($ref_id > 0) {
        $stmt_r = $pdo->prepare("SELECT * FROM refunds WHERE id = ?");
        $stmt_r->execute([$ref_id]);
        $refund = $stmt_r->fetch();

        if (!$refund) {
            set_flash('error', "Refund record not found.");
        } elseif ($refund['status'] !== 'pending') {
            set_flash('error', "This refund has already been processed (Status: {$refund['status']}).");
        } else {
            // Process refund safely via wallet credit
            $user_id = (int)$refund['user_id'];
            $refund_amount = (float)$refund['amount'];
            $order_id = (int)$refund['order_id'];

            $res = adjust_user_wallet(
                $user_id, 
                'credit', 
                $refund_amount, 
                'Store Refund', 
                "Refund for Order #{$order_id} ({$refund['refund_number']})", 
                $refund['refund_number'], 
                $admin['id']
            );

            if ($res['success']) {
                // Update refund status
                $stmt_up_r = $pdo->prepare("UPDATE refunds SET status = 'completed', admin_id = ?, admin_notes = CONCAT(COALESCE(admin_notes, ''), ' [Approved and credited to wallet by ', ?, ' on ', NOW(), ']') WHERE id = ?");
                $stmt_up_r->execute([$admin['id'], $admin['username'], $ref_id]);

                // Update order status if order exists
                if ($order_id > 0) {
                    $pdo->prepare("UPDATE orders SET order_status = 'refunded', payment_status = 'refunded' WHERE id = ?")->execute([$order_id]);
                }

                // Notify user
                create_notification(
                    $user_id, 
                    "Refund Approved: " . format_currency($refund_amount), 
                    "Your refund of " . format_currency($refund_amount) . " for Order #{$order_id} has been approved and credited directly to your Store Wallet.", 
                    'wallet', 
                    '/wallet.php'
                );

                log_admin_activity('approve_refund', "Approved refund {$refund['refund_number']} of " . format_currency($refund_amount) . " for user ID {$user_id}");
                set_flash('success', "Refund of " . format_currency($refund_amount) . " successfully approved and credited to customer's wallet.");
            } else {
                set_flash('error', "Failed to credit customer wallet: " . $res['message']);
            }
        }
        header("Location: /admin/refunds.php");
        exit;
    }
}

// Handle Reject Refund
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reject') {
    csrf_validate();
    $ref_id = (int)($_POST['refund_id'] ?? 0);
    $reject_reason = sanitize($_POST['reject_notes'] ?? 'Refund request declined by store administration.');

    if ($ref_id > 0) {
        $stmt_r = $pdo->prepare("SELECT * FROM refunds WHERE id = ?");
        $stmt_r->execute([$ref_id]);
        $refund = $stmt_r->fetch();

        if ($refund && $refund['status'] === 'pending') {
            $stmt_rej = $pdo->prepare("UPDATE refunds SET status = 'rejected', admin_id = ?, admin_notes = ? WHERE id = ?");
            $stmt_rej->execute([$admin['id'], $reject_reason, $ref_id]);

            create_notification(
                (int)$refund['user_id'], 
                "Refund Request Declined", 
                "Your refund request {$refund['refund_number']} was declined: {$reject_reason}", 
                'order', 
                '/orders.php'
            );

            log_admin_activity('reject_refund', "Rejected refund {$refund['refund_number']}: {$reject_reason}");
            set_flash('success', "Refund request marked as rejected.");
        }
        header("Location: /admin/refunds.php");
        exit;
    }
}

// Handle Manual Create Refund POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_refund') {
    csrf_validate();
    $order_num = sanitize($_POST['order_number'] ?? '');
    $reason = sanitize($_POST['reason'] ?? '');
    $custom_amount = (float)($_POST['amount'] ?? 0);

    $stmt_o = $pdo->prepare("SELECT * FROM orders WHERE order_number = ? OR id = ?");
    $stmt_o->execute([$order_num, (int)$order_num]);
    $order = $stmt_o->fetch();

    if (!$order) {
        $errors[] = "Order '{$order_num}' was not found.";
    } else {
        $amount = ($custom_amount > 0) ? $custom_amount : (float)$order['total_amount'];
        $ref_number = generate_refund_id();

        $stmt_i = $pdo->prepare("
            INSERT INTO refunds (refund_number, order_id, user_id, amount, reason, status, admin_id, admin_notes) 
            VALUES (?, ?, ?, ?, ?, 'pending', ?, 'Manually created by admin')
        ");
        $stmt_i->execute([$ref_number, $order['id'], $order['user_id'], $amount, $reason, $admin['id']]);
        log_admin_activity('create_refund', "Created refund {$ref_number} for Order #{$order['id']}");
        set_flash('success', "Refund {$ref_number} created with pending status.");
        header("Location: /admin/refunds.php");
        exit;
    }
}

// Filter
$status_filter = sanitize($_GET['status'] ?? '');
$where = ["1=1"];
$params = [];

if (!empty($status_filter)) {
    $where[] = "r.status = ?";
    $params[] = $status_filter;
}

$sql = "
    SELECT r.*, o.order_number, u.username, u.email as user_email, a.username as admin_name 
    FROM refunds r 
    LEFT JOIN orders o ON r.order_id = o.id 
    LEFT JOIN users u ON r.user_id = u.id 
    LEFT JOIN admins a ON r.admin_id = a.id 
    WHERE " . implode(' AND ', $where) . " 
    ORDER BY r.id DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$refunds = $stmt->fetchAll();

// Counts
$pending_count = (int)$pdo->query("SELECT COUNT(*) FROM refunds WHERE status = 'pending'")->fetchColumn();
$completed_count = (int)$pdo->query("SELECT COUNT(*) FROM refunds WHERE status = 'completed' OR status = 'approved'")->fetchColumn();
$total_refunded_amount = (float)$pdo->query("SELECT COALESCE(SUM(amount), 0) FROM refunds WHERE status = 'completed' OR status = 'approved'")->fetchColumn();
?>

<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="font-gaming text-2xl sm:text-3xl font-bold text-white tracking-wide flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-red-500 animate-pulse"></span> REFUND & RETURN MANAGEMENT
            </h1>
            <p class="text-xs text-zinc-400 mt-1">Review failed top-ups, issue wallet balance credits, and track complete transaction history</p>
        </div>
        <div class="flex items-center gap-2">
            <button onclick="document.getElementById('manualRefundModal').classList.remove('hidden')" class="btn-gaming-red text-white text-xs font-gaming font-bold px-4 py-2.5 rounded-xl shadow-red-subtle flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span>Create Manual Refund</span>
            </button>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-gaming-850 border <?php echo $pending_count > 0 ? 'border-red-500/60 shadow-red-subtle' : 'border-gaming-border'; ?> rounded-xl p-4">
            <span class="text-xs text-zinc-400 uppercase font-semibold">Pending Action</span>
            <div class="font-gaming text-2xl font-bold <?php echo $pending_count > 0 ? 'text-red-400' : 'text-white'; ?> mt-1">
                <?php echo $pending_count; ?>
            </div>
            <span class="text-[10px] text-zinc-500">Awaiting admin review</span>
        </div>

        <div class="bg-gaming-850 border border-gaming-border rounded-xl p-4">
            <span class="text-xs text-zinc-400 uppercase font-semibold">Completed Refunds</span>
            <div class="font-gaming text-2xl font-bold text-emerald-400 mt-1"><?php echo $completed_count; ?></div>
            <span class="text-[10px] text-zinc-500">Credited to customer wallets</span>
        </div>

        <div class="bg-gaming-850 border border-gaming-border rounded-xl p-4">
            <span class="text-xs text-zinc-400 uppercase font-semibold">Total Refunded Amount</span>
            <div class="font-gaming text-2xl font-bold text-white mt-1"><?php echo format_currency($total_refunded_amount); ?></div>
            <span class="text-[10px] text-zinc-500">Gross INR refunded</span>
        </div>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="p-4 rounded-xl bg-red-950/70 border border-red-500/60 text-red-200 text-xs space-y-1">
            <?php foreach ($errors as $err): ?>
                <p>• <?php echo e($err); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Filter Bar -->
    <div class="bg-gaming-850 border border-gaming-border rounded-xl p-4 flex flex-col sm:flex-row items-center justify-between gap-4">
        <div class="flex items-center gap-2 overflow-x-auto w-full sm:w-auto">
            <a href="/admin/refunds.php" class="px-3 py-1.5 rounded-lg text-xs font-semibold <?php echo empty($status_filter) ? 'bg-red-600 text-white shadow-red-subtle' : 'bg-gaming-900 text-zinc-400 hover:text-white'; ?>">
                All (<?php echo count($refunds); ?>)
            </a>
            <a href="/admin/refunds.php?status=pending" class="px-3 py-1.5 rounded-lg text-xs font-semibold <?php echo $status_filter === 'pending' ? 'bg-amber-600 text-white' : 'bg-gaming-900 text-amber-400 hover:text-white'; ?>">
                Pending (<?php echo $pending_count; ?>)
            </a>
            <a href="/admin/refunds.php?status=completed" class="px-3 py-1.5 rounded-lg text-xs font-semibold <?php echo $status_filter === 'completed' ? 'bg-emerald-600 text-white' : 'bg-gaming-900 text-emerald-400 hover:text-white'; ?>">
                Completed
            </a>
            <a href="/admin/refunds.php?status=rejected" class="px-3 py-1.5 rounded-lg text-xs font-semibold <?php echo $status_filter === 'rejected' ? 'bg-zinc-700 text-white' : 'bg-gaming-900 text-zinc-400 hover:text-white'; ?>">
                Rejected
            </a>
        </div>
    </div>

    <!-- Refunds List -->
    <div class="bg-gaming-850 border border-gaming-border rounded-2xl overflow-hidden shadow-2xl">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="text-zinc-400 bg-gaming-900/60 uppercase border-b border-gaming-border">
                        <th class="py-3 px-4">Refund ID</th>
                        <th class="py-3 px-4">Order ID</th>
                        <th class="py-3 px-4">User</th>
                        <th class="py-3 px-4">Amount</th>
                        <th class="py-3 px-4">Reason</th>
                        <th class="py-3 px-4 text-center">Status</th>
                        <th class="py-3 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gaming-border/60">
                    <?php if (empty($refunds)): ?>
                        <tr>
                            <td colspan="7" class="py-8 text-center text-zinc-500">No refunds found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($refunds as $rf): ?>
                            <tr class="hover:bg-gaming-800/40 transition-colors">
                                <td class="py-3 px-4 font-mono font-bold text-white"><?php echo e($rf['refund_number']); ?></td>
                                <td class="py-3 px-4">
                                    <a href="/admin/order-details.php?id=<?php echo $rf['order_id']; ?>" class="text-red-400 font-mono hover:underline">
                                        <?php echo e($rf['order_number'] ?? 'Order #' . $rf['order_id']); ?>
                                    </a>
                                </td>
                                <td class="py-3 px-4">
                                    <div class="font-bold text-white"><?php echo e($rf['username']); ?></div>
                                    <div class="text-[10px] text-zinc-500"><?php echo e($rf['user_email']); ?></div>
                                </td>
                                <td class="py-3 px-4 font-gaming font-bold text-emerald-400 text-sm">
                                    <?php echo format_currency($rf['amount']); ?>
                                </td>
                                <td class="py-3 px-4 max-w-xs">
                                    <p class="text-zinc-300"><?php echo e($rf['reason']); ?></p>
                                    <?php if (!empty($rf['admin_notes'])): ?>
                                        <p class="text-[10px] text-zinc-500 mt-1 italic"><?php echo e($rf['admin_notes']); ?></p>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <?php if ($rf['status'] === 'pending'): ?>
                                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-950 text-amber-300 border border-amber-800 animate-pulse">Pending Review</span>
                                    <?php elseif ($rf['status'] === 'completed' || $rf['status'] === 'approved'): ?>
                                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-950 text-emerald-300 border border-emerald-800">Completed (Credited)</span>
                                    <?php else: ?>
                                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-zinc-800 text-zinc-400 border border-zinc-700">Rejected</span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3 px-4 text-right space-x-2 whitespace-nowrap">
                                    <?php if ($rf['status'] === 'pending'): ?>
                                        <a href="/admin/refunds.php?action=approve&id=<?php echo $rf['id']; ?>" 
                                           onclick="return confirm('Approve this refund? This will safely credit <?php echo format_currency($rf['amount']); ?> to the user wallet immediately.')"
                                           class="px-3 py-1.5 rounded-lg bg-emerald-950 hover:bg-emerald-900 text-emerald-300 border border-emerald-800 font-bold text-[11px]">
                                            Approve & Credit Wallet
                                        </a>
                                        <button onclick="openRejectModal(<?php echo $rf['id']; ?>, '<?php echo e($rf['refund_number']); ?>')" class="px-2.5 py-1.5 rounded-lg bg-red-950/60 hover:bg-red-900 text-red-300 border border-red-800/40 font-bold text-[11px]">
                                            Reject
                                        </button>
                                    <?php else: ?>
                                        <span class="text-zinc-500 text-[11px]">Handled by <?php echo e($rf['admin_name'] ?? 'Admin'); ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal: Create Manual Refund -->
<div id="manualRefundModal" class="hidden fixed inset-0 z-50 bg-black/80 flex items-center justify-center p-4">
    <div class="bg-gaming-900 border border-gaming-border rounded-2xl max-w-md w-full p-6 space-y-4">
        <div class="flex items-center justify-between border-b border-gaming-border pb-3">
            <h3 class="font-gaming text-lg font-bold text-white">Create Order Refund</h3>
            <button onclick="document.getElementById('manualRefundModal').classList.add('hidden')" class="text-zinc-400 hover:text-white">✕</button>
        </div>
        <form method="POST" action="/admin/refunds.php" class="space-y-4">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="create_refund">

            <div>
                <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1">Order Number or ID *</label>
                <input type="text" name="order_number" required placeholder="e.g. FF-0CD4C8-153"
                       class="w-full px-3.5 py-2.5 rounded-xl bg-gaming-950 border border-gaming-border text-sm text-white focus:outline-none focus:border-red-500">
            </div>

            <div>
                <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1">Refund Amount in ₹ INR (Optional, leave blank for full amount)</label>
                <input type="number" step="0.01" min="0.01" name="amount" placeholder="Leave blank for full order amount"
                       class="w-full px-3.5 py-2.5 rounded-xl bg-gaming-950 border border-gaming-border text-sm text-white focus:outline-none focus:border-red-500">
            </div>

            <div>
                <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1">Reason for Refund *</label>
                <textarea name="reason" rows="3" required placeholder="Explain why refund is being issued..."
                          class="w-full px-3.5 py-2.5 rounded-xl bg-gaming-950 border border-gaming-border text-sm text-white focus:outline-none focus:border-red-500"></textarea>
            </div>

            <div class="flex items-center justify-end gap-3 pt-2">
                <button type="button" onclick="document.getElementById('manualRefundModal').classList.add('hidden')" class="px-4 py-2 rounded-xl bg-gaming-800 text-xs text-zinc-400">Cancel</button>
                <button type="submit" class="px-4 py-2 rounded-xl bg-red-600 hover:bg-red-500 text-xs font-bold text-white">Create Refund</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Reject Refund -->
<div id="rejectRefundModal" class="hidden fixed inset-0 z-50 bg-black/80 flex items-center justify-center p-4">
    <div class="bg-gaming-900 border border-gaming-border rounded-2xl max-w-md w-full p-6 space-y-4">
        <div class="flex items-center justify-between border-b border-gaming-border pb-3">
            <h3 class="font-gaming text-lg font-bold text-white">Decline Refund Request</h3>
            <button onclick="document.getElementById('rejectRefundModal').classList.add('hidden')" class="text-zinc-400 hover:text-white">✕</button>
        </div>
        <form method="POST" action="/admin/refunds.php" class="space-y-4">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="reject">
            <input type="hidden" name="refund_id" id="rejectRefundId">

            <div>
                <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1">Reason for Declining *</label>
                <textarea name="reject_notes" rows="3" required placeholder="State reason to be shared with the customer..."
                          class="w-full px-3.5 py-2.5 rounded-xl bg-gaming-950 border border-gaming-border text-sm text-white focus:outline-none focus:border-red-500"></textarea>
            </div>

            <div class="flex items-center justify-end gap-3 pt-2">
                <button type="button" onclick="document.getElementById('rejectRefundModal').classList.add('hidden')" class="px-4 py-2 rounded-xl bg-gaming-800 text-xs text-zinc-400">Cancel</button>
                <button type="submit" class="px-4 py-2 rounded-xl bg-red-600 hover:bg-red-500 text-xs font-bold text-white">Decline Request</button>
            </div>
        </form>
    </div>
</div>

<script>
function openRejectModal(id, refNo) {
    document.getElementById('rejectRefundId').value = id;
    document.getElementById('rejectRefundModal').classList.remove('hidden');
}
</script>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
