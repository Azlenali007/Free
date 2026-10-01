<?php
$page_title = "Support Ticket Conversation";
require_once __DIR__ . '/includes/functions.php';
require_login();

$user = current_user();
$ticket_id = (int)($_GET['id'] ?? 0);

if (!$ticket_id) {
    header("Location: /tickets.php");
    exit;
}

// Fetch ticket for logged-in user
$stmt = $pdo->prepare("SELECT * FROM support_tickets WHERE id = ? AND user_id = ?");
$stmt->execute([$ticket_id, $user['id']]);
$ticket = $stmt->fetch();

if (!$ticket) {
    set_flash('error', 'Support ticket not found or access denied.');
    header("Location: /tickets.php");
    exit;
}

$errors = [];

// Handle User Reply
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validate();

    $reply_msg = trim($_POST['message'] ?? '');
    if (empty($reply_msg)) {
        $errors[] = "Reply message cannot be empty.";
    } elseif ($ticket['status'] === 'closed') {
        $errors[] = "This ticket is closed. Please open a new ticket if you need further help.";
    } else {
        $stmt_msg = $pdo->prepare("
            INSERT INTO ticket_messages (ticket_id, user_id, is_admin, message) 
            VALUES (?, ?, 0, ?)
        ");
        $stmt_msg->execute([$ticket['id'], $user['id'], $reply_msg]);

        // Re-open if status was pending
        $pdo->prepare("UPDATE support_tickets SET status = 'open', updated_at = NOW() WHERE id = ?")->execute([$ticket['id']]);

        set_flash('success', "Your reply has been sent.");
        header("Location: /ticket-view.php?id=" . $ticket['id']);
        exit;
    }
}

// Fetch all messages for this ticket
$msg_stmt = $pdo->prepare("
    SELECT tm.*, u.name as user_name, u.username 
    FROM ticket_messages tm 
    LEFT JOIN users u ON tm.user_id = u.id 
    WHERE tm.ticket_id = ? 
    ORDER BY tm.id ASC
");
$msg_stmt->execute([$ticket['id']]);
$messages = $msg_stmt->fetchAll();

require_once __DIR__ . '/includes/header.php';

$status_class = match($ticket['status']) {
    'open' => 'bg-emerald-950 text-emerald-400 border-emerald-800',
    'closed' => 'bg-zinc-800 text-zinc-400 border-zinc-700',
    default => 'bg-amber-950 text-amber-400 border-amber-800'
};
?>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-4 border-b border-gaming-border gap-3">
        <div>
            <div class="flex items-center gap-3">
                <span class="font-mono text-sm font-bold text-red-500"><?php echo e($ticket['ticket_number']); ?></span>
                <span class="px-2.5 py-0.5 rounded text-[10px] font-bold uppercase border <?php echo $status_class; ?>">
                    <?php echo e($ticket['status']); ?>
                </span>
            </div>
            <h1 class="font-gaming text-xl sm:text-2xl font-bold text-white mt-1">
                <?php echo e($ticket['subject']); ?>
            </h1>
        </div>
        <a href="/tickets.php" class="text-xs font-semibold text-zinc-400 hover:text-white flex items-center gap-1 self-start sm:self-auto">
            &larr; Back to Tickets
        </a>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="p-4 rounded-xl bg-red-950/70 border border-red-500/60 text-red-200 text-xs space-y-1">
            <?php foreach ($errors as $err): ?>
                <p>• <?php echo e($err); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Conversation Thread -->
    <div class="space-y-4">
        <?php foreach ($messages as $msg): 
            $is_admin = (bool)$msg['is_admin'];
        ?>
            <div class="flex gap-3.5 <?php echo $is_admin ? 'flex-row' : 'flex-row-reverse'; ?>">
                <!-- Avatar -->
                <div class="w-9 h-9 rounded-xl flex items-center justify-center font-bold text-xs shrink-0 <?php echo $is_admin ? 'bg-red-600 text-white shadow-red-subtle' : 'bg-gaming-800 text-zinc-300 border border-gaming-border'; ?>">
                    <?php echo $is_admin ? 'AD' : strtoupper(substr($user['username'], 0, 1)); ?>
                </div>

                <!-- Message Bubble -->
                <div class="max-w-2xl rounded-2xl p-4 space-y-1.5 <?php echo $is_admin ? 'bg-gaming-850 border border-red-900/40 text-zinc-200' : 'bg-gaming-800 border border-gaming-border text-white'; ?>">
                    <div class="flex items-center justify-between gap-4 text-[11px] pb-1 border-b border-gaming-border/50">
                        <span class="font-semibold <?php echo $is_admin ? 'text-red-400 font-gaming' : 'text-zinc-400'; ?>">
                            <?php echo $is_admin ? '⚡ FireZone Support Agent' : e($user['username']) . ' (You)'; ?>
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

    <!-- Reply Box -->
    <?php if ($ticket['status'] !== 'closed'): ?>
        <div class="bg-gaming-850 border border-gaming-border rounded-2xl p-6 shadow-2xl space-y-4 mt-8">
            <h3 class="font-gaming text-sm font-bold text-white uppercase tracking-wider flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-red-500"></span> Send a Reply
            </h3>
            <form method="POST" action="/ticket-view.php?id=<?php echo $ticket['id']; ?>" class="space-y-4">
                <?php echo csrf_field(); ?>
                <textarea name="message" rows="4" required
                          class="w-full px-3.5 py-2.5 rounded-xl bg-gaming-900 border border-gaming-border focus:border-red-500 text-sm text-white focus:outline-none leading-relaxed"
                          placeholder="Type your response or additional information here..."></textarea>
                <div class="flex justify-end">
                    <button type="submit" class="btn-gaming-red text-white font-gaming text-xs font-bold px-6 py-2.5 rounded-xl shadow-red-glow">
                        SEND REPLY &rarr;
                    </button>
                </div>
            </form>
        </div>
    <?php else: ?>
        <div class="p-4 rounded-xl bg-zinc-900 border border-zinc-800 text-center text-xs text-zinc-400">
            This ticket has been marked as <strong>Closed</strong>. If you still have questions, please 
            <a href="/ticket-create.php" class="text-red-400 underline font-semibold">open a new support ticket</a>.
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
