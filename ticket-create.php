<?php
$page_title = "Open Support Ticket";
require_once __DIR__ . '/includes/functions.php';
require_login();

$user = current_user();
$errors = [];
$subject = sanitize($_GET['subject'] ?? $_POST['subject'] ?? '');
$priority = sanitize($_POST['priority'] ?? 'medium');
$message = trim($_POST['message'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validate();

    if (empty($subject)) {
        $errors[] = "Please provide a subject for your ticket.";
    }
    if (empty($message)) {
        $errors[] = "Please enter your message or question.";
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            $ticket_number = generate_ticket_id();

            // Create Ticket
            $stmt = $pdo->prepare("
                INSERT INTO support_tickets (ticket_number, user_id, subject, priority, status) 
                VALUES (?, ?, ?, ?, 'open')
            ");
            $stmt->execute([$ticket_number, $user['id'], $subject, $priority]);
            $ticket_id = $pdo->lastInsertId();

            // Create initial message
            $stmt_msg = $pdo->prepare("
                INSERT INTO ticket_messages (ticket_id, user_id, is_admin, message) 
                VALUES (?, ?, 0, ?)
            ");
            $stmt_msg->execute([$ticket_id, $user['id'], $message]);

            $pdo->commit();

            set_flash('success', "Ticket #" . $ticket_number . " has been created! Our support team will reply soon.");
            header("Location: /ticket-view.php?id=" . $ticket_id);
            exit;

        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = "Failed to create ticket: " . $e->getMessage();
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-8">
    <div class="flex items-center justify-between pb-4 border-b border-gaming-border">
        <div>
            <h1 class="font-gaming text-2xl sm:text-3xl font-bold text-white tracking-wide flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-red-500"></span> NEW SUPPORT TICKET
            </h1>
            <p class="text-xs text-zinc-400 mt-1">Submit your inquiry directly to our Free Fire top-up technicians</p>
        </div>
        <a href="/tickets.php" class="text-xs font-semibold text-zinc-400 hover:text-white">
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

    <div class="bg-gaming-850 border border-gaming-border rounded-2xl p-6 sm:p-8 shadow-2xl relative overflow-hidden">
        <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-red-600 to-amber-500"></div>

        <form method="POST" action="/ticket-create.php" class="space-y-5">
            <?php echo csrf_field(); ?>

            <div>
                <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1">Subject</label>
                <input type="text" name="subject" value="<?php echo e($subject); ?>" required
                       class="w-full px-3.5 py-2.5 rounded-xl bg-gaming-900 border border-gaming-border focus:border-red-500 text-sm text-white focus:outline-none"
                       placeholder="e.g. Free Fire Diamond top-up delayed / UID query">
            </div>

            <div>
                <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1">Priority Level</label>
                <select name="priority" class="w-full px-3.5 py-2.5 rounded-xl bg-gaming-900 border border-gaming-border focus:border-red-500 text-sm text-white focus:outline-none">
                    <option value="low" <?php echo $priority === 'low' ? 'selected' : ''; ?>>Low - General inquiry</option>
                    <option value="medium" <?php echo $priority === 'medium' ? 'selected' : ''; ?>>Medium - Normal order question</option>
                    <option value="high" <?php echo $priority === 'high' ? 'selected' : ''; ?>>High - Urgent payment or wrong UID</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1">Detailed Message</label>
                <textarea name="message" rows="6" required
                          class="w-full px-3.5 py-2.5 rounded-xl bg-gaming-900 border border-gaming-border focus:border-red-500 text-sm text-white focus:outline-none leading-relaxed"
                          placeholder="Describe your issue or provide relevant Order Number and Free Fire UID..."><?php echo e($message); ?></textarea>
            </div>

            <div class="pt-2 flex items-center justify-end gap-3">
                <a href="/tickets.php" class="px-5 py-2.5 rounded-xl bg-gaming-800 text-zinc-400 hover:text-white text-xs font-semibold">
                    Cancel
                </a>
                <button type="submit" class="btn-gaming-red text-white font-gaming text-sm font-bold px-6 py-2.5 rounded-xl shadow-red-glow">
                    SUBMIT TICKET &rarr;
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
