<?php
$page_title = "Manage Support Tickets";
require_once __DIR__ . '/includes/admin_header.php';

$status_filter = sanitize($_GET['status'] ?? 'all');
$where = ["1=1"];
$params = [];

if (in_array($status_filter, ['open', 'pending', 'closed'])) {
    $where[] = "st.status = ?";
    $params[] = $status_filter;
}

$sql = "
    SELECT st.*, u.username, u.name as customer_name, u.email as customer_email,
           (SELECT COUNT(*) FROM ticket_messages tm WHERE tm.ticket_id = st.id) as message_count 
    FROM support_tickets st 
    LEFT JOIN users u ON st.user_id = u.id 
    WHERE " . implode(' AND ', $where) . " 
    ORDER BY st.id DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$tickets = $stmt->fetchAll();
?>

<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="font-gaming text-2xl sm:text-3xl font-bold text-white tracking-wide flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-red-500"></span> SUPPORT DESK
            </h1>
            <p class="text-xs text-zinc-400 mt-1">Review inquiries, answer gamer tickets, and resolve order disputes</p>
        </div>
    </div>

    <!-- Status Tabs -->
    <div class="flex flex-wrap items-center gap-2 border-b border-gaming-border pb-3">
        <a href="/admin/tickets.php" 
           class="px-4 py-2 rounded-xl text-xs font-semibold <?php echo $status_filter === 'all' ? 'bg-red-600 text-white shadow-red-subtle' : 'bg-gaming-850 text-zinc-400 hover:text-white border border-gaming-border'; ?>">
            All Tickets
        </a>
        <a href="/admin/tickets.php?status=open" 
           class="px-4 py-2 rounded-xl text-xs font-semibold <?php echo $status_filter === 'open' ? 'bg-amber-600 text-white shadow-red-subtle' : 'bg-gaming-850 text-zinc-400 hover:text-white border border-gaming-border'; ?>">
            Open
        </a>
        <a href="/admin/tickets.php?status=pending" 
           class="px-4 py-2 rounded-xl text-xs font-semibold <?php echo $status_filter === 'pending' ? 'bg-blue-600 text-white shadow-red-subtle' : 'bg-gaming-850 text-zinc-400 hover:text-white border border-gaming-border'; ?>">
            Pending
        </a>
        <a href="/admin/tickets.php?status=closed" 
           class="px-4 py-2 rounded-xl text-xs font-semibold <?php echo $status_filter === 'closed' ? 'bg-zinc-700 text-white shadow-red-subtle' : 'bg-gaming-850 text-zinc-400 hover:text-white border border-gaming-border'; ?>">
            Closed
        </a>
    </div>

    <!-- Tickets Table -->
    <div class="bg-gaming-850 border border-gaming-border rounded-2xl overflow-hidden shadow-2xl">
        <?php if (!empty($tickets)): ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-gaming-900 border-b border-gaming-border text-zinc-400 uppercase font-gaming">
                            <th class="py-3.5 px-4">Ticket ID</th>
                            <th class="py-3.5 px-4">Subject</th>
                            <th class="py-3.5 px-4">Customer</th>
                            <th class="py-3.5 px-4">Priority</th>
                            <th class="py-3.5 px-4">Status</th>
                            <th class="py-3.5 px-4">Messages</th>
                            <th class="py-3.5 px-4">Updated</th>
                            <th class="py-3.5 px-4 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gaming-border/60">
                        <?php foreach ($tickets as $t): 
                            $status_class = match($t['status']) {
                                'open' => 'bg-emerald-950/80 text-emerald-400 border-emerald-800/40',
                                'closed' => 'bg-zinc-800 text-zinc-400 border-zinc-700',
                                default => 'bg-amber-950/80 text-amber-400 border-amber-800/40'
                            };
                            $priority_class = match($t['priority']) {
                                'high' => 'text-red-400 font-bold',
                                'low' => 'text-zinc-400',
                                default => 'text-amber-400'
                            };
                        ?>
                            <tr class="hover:bg-gaming-800/40 transition-colors">
                                <td class="py-3.5 px-4 font-mono font-bold text-white"><?php echo e($t['ticket_number']); ?></td>
                                <td class="py-3.5 px-4 font-bold text-white max-w-xs truncate"><?php echo e($t['subject']); ?></td>
                                <td class="py-3.5 px-4">
                                    <span class="text-zinc-200 block"><?php echo e($t['customer_name'] ?: $t['username']); ?></span>
                                    <span class="text-[10px] text-zinc-500 font-mono">@<?php echo e($t['username']); ?></span>
                                </td>
                                <td class="py-3.5 px-4 uppercase text-[10px] <?php echo $priority_class; ?>">
                                    <?php echo e($t['priority']); ?>
                                </td>
                                <td class="py-3.5 px-4">
                                    <span class="inline-block px-2.5 py-0.5 rounded text-[10px] font-bold uppercase border <?php echo $status_class; ?>">
                                        <?php echo e($t['status']); ?>
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-zinc-400"><?php echo $t['message_count']; ?> msgs</td>
                                <td class="py-3.5 px-4 text-zinc-400"><?php echo date('M d, H:i', strtotime($t['updated_at'])); ?></td>
                                <td class="py-3.5 px-4 text-right">
                                    <a href="/admin/ticket-view.php?id=<?php echo $t['id']; ?>" 
                                       class="px-2.5 py-1 rounded bg-gaming-800 hover:bg-gaming-750 text-red-400 hover:text-red-300 font-semibold border border-gaming-border">
                                        Open Thread &rarr;
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-12 text-zinc-500 text-xs">
                No support tickets found under this view.
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
