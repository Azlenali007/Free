<?php
$page_title = "Support Tickets";
require_once __DIR__ . '/includes/functions.php';
require_login();

$user = current_user();

$stmt = $pdo->prepare("
    SELECT * FROM support_tickets 
    WHERE user_id = ? 
    ORDER BY id DESC
");
$stmt->execute([$user['id']]);
$tickets = $stmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-8">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="font-gaming text-3xl font-extrabold text-white tracking-wide flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-red-500"></span> GAMER SUPPORT TICKETS
            </h1>
            <p class="text-xs text-zinc-400 mt-1">Need help with an order, UID issue, or payment? We're available 24/7.</p>
        </div>
        <a href="/ticket-create.php" class="btn-gaming-red text-white text-xs font-gaming font-bold px-5 py-2.5 rounded-xl shadow-red-subtle self-start sm:self-auto flex items-center gap-1.5">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            <span>OPEN NEW TICKET</span>
        </a>
    </div>

    <!-- Tickets List Table / Mobile Cards -->
    <div class="bg-gaming-850 border border-gaming-border rounded-2xl p-6 shadow-2xl space-y-4">
        <div class="flex items-center justify-between pb-3 border-b border-gaming-border">
            <h2 class="font-gaming text-xl font-bold text-white tracking-wide">YOUR SUPPORT TICKETS</h2>
            <span class="text-xs text-zinc-400 font-mono"><?php echo count($tickets); ?> Total</span>
        </div>

        <?php if (!empty($tickets)): ?>
            <!-- Desktop Table -->
            <div class="hidden sm:block overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-gaming-border text-xs text-zinc-400 uppercase font-gaming">
                            <th class="py-3 px-3">Ticket ID</th>
                            <th class="py-3 px-3">Subject</th>
                            <th class="py-3 px-3">Priority</th>
                            <th class="py-3 px-3">Status</th>
                            <th class="py-3 px-3">Last Updated</th>
                            <th class="py-3 px-3 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gaming-border/60 text-xs">
                        <?php foreach ($tickets as $t): 
                            $status_class = match($t['status']) {
                                'open' => 'bg-emerald-950/80 text-emerald-400 border-emerald-800/40',
                                'closed' => 'bg-zinc-800 text-zinc-400 border-zinc-700',
                                default => 'bg-amber-950/80 text-amber-400 border-amber-800/40'
                            };
                            $priority_class = match($t['priority']) {
                                'high' => 'text-red-400',
                                'low' => 'text-zinc-400',
                                default => 'text-amber-400'
                            };
                        ?>
                            <tr class="hover:bg-gaming-800/40 transition-colors">
                                <td class="py-3 px-3 font-mono font-bold text-white"><?php echo e($t['ticket_number']); ?></td>
                                <td class="py-3 px-3 font-semibold text-white max-w-md truncate"><?php echo e($t['subject']); ?></td>
                                <td class="py-3 px-3 uppercase font-bold text-[10px] <?php echo $priority_class; ?>"><?php echo e($t['priority']); ?></td>
                                <td class="py-3 px-3">
                                    <span class="px-2.5 py-0.5 rounded text-[10px] font-bold uppercase border <?php echo $status_class; ?>">
                                        <?php echo e($t['status']); ?>
                                    </span>
                                </td>
                                <td class="py-3 px-3 text-zinc-400"><?php echo date('M d, Y H:i', strtotime($t['updated_at'])); ?></td>
                                <td class="py-3 px-3 text-right">
                                    <a href="/ticket-view.php?id=<?php echo $t['id']; ?>" 
                                       class="px-3 py-1.5 rounded-lg bg-gaming-800 hover:bg-gaming-750 text-red-400 hover:text-red-300 font-semibold border border-gaming-border">
                                        View Thread &rarr;
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Mobile View -->
            <div class="sm:hidden space-y-3">
                <?php foreach ($tickets as $t): 
                    $status_class = match($t['status']) {
                        'open' => 'bg-emerald-950/80 text-emerald-400 border-emerald-800/40',
                        'closed' => 'bg-zinc-800 text-zinc-400 border-zinc-700',
                        default => 'bg-amber-950/80 text-amber-400 border-amber-800/40'
                    };
                ?>
                    <div class="p-4 rounded-xl bg-gaming-900 border border-gaming-border space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="font-mono text-xs font-bold text-white"><?php echo e($t['ticket_number']); ?></span>
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase border <?php echo $status_class; ?>">
                                <?php echo e($t['status']); ?>
                            </span>
                        </div>
                        <h4 class="font-gaming font-bold text-sm text-white"><?php echo e($t['subject']); ?></h4>
                        <div class="flex justify-between text-xs text-zinc-400 pt-1">
                            <span>Priority: <strong class="uppercase text-zinc-200"><?php echo e($t['priority']); ?></strong></span>
                            <span><?php echo date('M d, Y', strtotime($t['updated_at'])); ?></span>
                        </div>
                        <div class="pt-2 border-t border-gaming-border/60">
                            <a href="/ticket-view.php?id=<?php echo $t['id']; ?>" class="block text-center py-2 rounded-lg bg-gaming-800 text-xs font-semibold text-red-400">
                                Open Conversation &rarr;
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-12 text-zinc-500 text-xs space-y-2">
                <p>You do not have any open support tickets.</p>
                <a href="/ticket-create.php" class="text-red-400 underline font-semibold">Need assistance? Open a support ticket here.</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
