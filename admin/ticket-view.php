<?php
$page_title = "Admin - Ticket Conversation";
require_once __DIR__ . '/includes/admin_header.php';

$ticket_id = (int)($_GET['id'] ?? 0);
if (!$ticket_id) {
    header("Location: /admin/tickets.php");
    exit;
}

$stmt = $pdo->prepare("
    SELECT st.*, u.name as customer_name, u.username, u.email as customer_email, u.phone as customer_phone, u.ff_uid 
    FROM support_tickets st 
    LEFT JOIN users u ON st.user_id = u.id 
    WHERE st.id = ?
");
$stmt->execute([$ticket_id]);
$ticket = $stmt->fetch();

if (!$ticket) {
    set_flash('error', "Ticket not found.");
    header("Location: /admin/tickets.php");
    exit;
}

// Handle Admin Reply or Status Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validate();

    $new_status = sanitize($_POST['status'] ?? $ticket['status']);
    $reply_msg = trim($_POST['message'] ?? '');

    // Status change
    if ($new_status !== $ticket['status']) {
        $pdo->prepare("UPDATE support_tickets SET status = ?, updated_at = NOW() WHERE id = ?")->execute([$new_status, $ticket_id]);
    }

    // Message insertion
    if (!empty($reply_msg)) {
        $stmt_msg = $pdo->prepare("
            INSERT INTO ticket_messages (ticket_id, user_id, is_admin, message) 
            VALUES (?, NULL, 1, ?)
        ");
        $stmt_msg->execute([$ticket_id, $reply_msg]);
        $pdo->prepare("UPDATE support_tickets SET updated_at = NOW() WHERE id = ?")->execute([$ticket_id]);
    }

    set_flash('success', "Ticket updated successfully.");
    header("Location: /admin/ticket-view.php?id=" . $ticket_id);
    exit;
}

// Fetch messages
$msg_stmt = $pdo->prepare("
    SELECT tm.*, u.name as user_name, u.username 
    FROM ticket_messages tm 
    LEFT JOIN users u ON tm.user_id = u.id 
    WHERE tm.ticket_id = ? 
    ORDER BY tm.id ASC
");
$msg_stmt->execute([$ticket['id']]);
$messages = $msg_stmt->fetchAll();

$status_class = match($ticket['status']) {
    'open' => 'bg-emerald-950 text-emerald-400 border-emerald-800',
    'closed' => 'bg-zinc-800 text-zinc-400 border-zinc-700',
    default => 'bg-amber-950 text-amber-400 border-amber-800'
};
?>

<div class="max-w-4xl mx-auto space-y-6">
    <div class="flex items-center justify-between pb-4 border-b border-gaming-border">
        <div>
            <div class="flex items-center gap-3">
                <span class="font-mono text-sm font-bold text-red-500"><?php echo e($ticket['ticket_number']); ?></span>
                <span class="px-2.5 py-0.5 rounded text-[10px] font-bold uppercase border <?php echo $status_class; ?>">
                    <?php echo e($ticket['status']); ?>
                </span>
                <span class="text-xs text-zinc-400">Customer: <strong class="text-white"><?php echo e($ticket['customer_name'] ?: $ticket['username']); ?></strong> (UID: <?php echo e($ticket['ff_uid'] ?: 'N/A'); ?>)</span>
            </div>
            <h1 class="font-gaming text-xl sm:text-2xl font-bold text-white mt-1">
                <?php echo e($ticket['subject']); ?>
            </h1>
        </div>
        <a href="/admin/tickets.php" class="text-xs font-semibold text-zinc-400 hover:text-white">
            &larr; Back to Tickets
        </a>
    </div>

    <!-- Messages List -->
    <div class="space-y-4">
        <?php foreach ($messages as $msg): 
            $is_admin = (bool)$msg['is_admin'];
        ?>
            <div class="flex gap-3.5 <?php echo $is_admin ? 'flex-row-reverse' : 'flex-row'; ?>">
                <div class="w-9 h-9 rounded-xl flex items-center justify-center font-bold text-xs shrink-0 <?php echo $is_admin ? 'bg-red-600 text-white shadow-red-subtle' : 'bg-gaming-800 text-zinc-300 border border-gaming-border'; ?>">
                    <?php echo $is_admin ? 'AD' : 'GM'; ?>
                </div>

                <div class="max-w-2xl rounded-2xl p-4 space-y-1.5 <?php echo $is_admin ? 'bg-gaming-800 border border-red-900/40 text-white' : 'bg-gaming-850 border border-gaming-border text-zinc-200'; ?>">
                    <div class="flex items-center justify-between gap-4 text-[11px] pb-1 border-b border-gaming-border/50">
                        <span class="font-semibold <?php echo $is_admin ? 'text-red-400 font-gaming' : 'text-zinc-400'; ?>">
                            <?php echo $is_admin ? '⚡ Admin Response (' . e($admin['username']) . ')' : e($ticket['customer_name'] ?: $ticket['username']); ?>
                        </span>
                        <span class="text-zinc-500 text-[10px]">
                            <?php echo date('M d, Y h:i A', strtotime($msg['created_at'])); ?>
                        </span>
                    </div>
                    <div class="text-xs leading-relaxed whitespace-pre-wrap font-sans">
                        <?php echo nl2br(e($msg['message'])); ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Admin Reply Form -->
    <div class="bg-gaming-850 border border-gaming-border rounded-2xl p-6 shadow-2xl space-y-4 mt-8">
        <h3 class="font-gaming text-sm font-bold text-white uppercase tracking-wider flex items-center gap-2">
            <span class="w-2 h-2 rounded-full bg-red-500"></span> Reply to Gamer & Update Status
        </h3>

        <form method="POST" action="/admin/ticket-view.php?id=<?php echo $ticket['id']; ?>" class="space-y-4">
            <?php echo csrf_field(); ?>

            <div class="flex items-center gap-4">
                <label class="text-xs font-semibold text-zinc-300 uppercase">Set Status:</label>
                <select name="status" class="px-3 py-1.5 rounded-lg bg-gaming-900 border border-gaming-border text-xs text-white focus:outline-none">
                    <option value="open" <?php echo $ticket['status'] === 'open' ? 'selected' : ''; ?>>Open</option>
                    <option value="pending" <?php echo $ticket['status'] === 'pending' ? 'selected' : ''; ?>>Pending (Waiting on User)</option>
                    <option value="closed" <?php echo $ticket['status'] === 'closed' ? 'selected' : ''; ?>>Closed (Resolved)</option>
                </select>
            </div>

            <textarea name="message" rows="4"
                      class="w-full px-3.5 py-2.5 rounded-xl bg-gaming-900 border border-gaming-border focus:border-red-500 text-sm text-white focus:outline-none leading-relaxed"
                      placeholder="Type official response to customer..."></textarea>

            <div class="flex justify-end">
                <button type="submit" class="btn-gaming-red text-white font-gaming text-xs font-bold px-6 py-2.5 rounded-xl shadow-red-glow">
                    POST RESPONSE & SAVE &rarr;
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
