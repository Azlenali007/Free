<?php
$page_title = "Product Reviews & Ratings";
require_once __DIR__ . '/includes/admin_header.php';

// Handle Toggle Review Visibility
if (isset($_GET['action']) && $_GET['action'] === 'toggle') {
    $rid = (int)($_GET['id'] ?? 0);
    if ($rid > 0) {
        $stmt_t = $pdo->prepare("UPDATE reviews SET status = IF(status = 'visible', 'hidden', 'visible') WHERE id = ?");
        $stmt_t->execute([$rid]);
        log_admin_activity('toggle_review_visibility', "Toggled review ID: {$rid}");
        set_flash('success', "Review visibility updated.");
        header("Location: /admin/reviews.php");
        exit;
    }
}

// Handle Delete Review
if (isset($_GET['action']) && $_GET['action'] === 'delete') {
    $rid = (int)($_GET['id'] ?? 0);
    if ($rid > 0) {
        $stmt_del = $pdo->prepare("DELETE FROM reviews WHERE id = ?");
        $stmt_del->execute([$rid]);
        log_admin_activity('delete_review', "Deleted review ID: {$rid}");
        set_flash('success', "Review deleted permanently.");
        header("Location: /admin/reviews.php");
        exit;
    }
}

// Filter reviews
$rating_filter = (int)($_GET['rating'] ?? 0);
$status_filter = sanitize($_GET['status'] ?? '');

$where = ["1=1"];
$params = [];

if ($rating_filter > 0) {
    $where[] = "r.rating = ?";
    $params[] = $rating_filter;
}

if (!empty($status_filter)) {
    $where[] = "r.status = ?";
    $params[] = $status_filter;
}

$sql = "
    SELECT r.*, p.name as product_name, p.price as product_price, u.username, u.name as user_fullname 
    FROM reviews r 
    JOIN products p ON r.product_id = p.id 
    JOIN users u ON r.user_id = u.id 
    WHERE " . implode(' AND ', $where) . " 
    ORDER BY r.id DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$reviews = $stmt->fetchAll();

// Overall stats
$avg_rating = (float)$pdo->query("SELECT COALESCE(AVG(rating), 0) FROM reviews WHERE status = 'visible'")->fetchColumn();
$total_reviews = (int)$pdo->query("SELECT COUNT(*) FROM reviews")->fetchColumn();
$visible_reviews = (int)$pdo->query("SELECT COUNT(*) FROM reviews WHERE status = 'visible'")->fetchColumn();
?>

<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="font-gaming text-2xl sm:text-3xl font-bold text-white tracking-wide flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-amber-400"></span> PRODUCT REVIEWS & CUSTOMER RATINGS
            </h1>
            <p class="text-xs text-zinc-400 mt-1">Moderate genuine customer feedback submitted by verified purchasers</p>
        </div>
    </div>

    <!-- Rating Summary Metrics -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-gaming-850 border border-gaming-border rounded-xl p-4 flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-amber-500/20 text-amber-400 flex items-center justify-center font-bold text-xl border border-amber-500/40">
                ★
            </div>
            <div>
                <span class="text-xs text-zinc-400">Average Rating</span>
                <div class="font-gaming text-2xl font-bold text-white"><?php echo number_format($avg_rating, 1); ?> / 5.0</div>
            </div>
        </div>

        <div class="bg-gaming-850 border border-gaming-border rounded-xl p-4 flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-emerald-500/20 text-emerald-400 flex items-center justify-center font-bold text-xl border border-emerald-500/40">
                ✓
            </div>
            <div>
                <span class="text-xs text-zinc-400">Approved & Visible</span>
                <div class="font-gaming text-2xl font-bold text-emerald-400"><?php echo $visible_reviews; ?></div>
            </div>
        </div>

        <div class="bg-gaming-850 border border-gaming-border rounded-xl p-4 flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-gaming-800 text-zinc-300 flex items-center justify-center font-bold text-xl border border-gaming-border">
                💬
            </div>
            <div>
                <span class="text-xs text-zinc-400">Total Customer Reviews</span>
                <div class="font-gaming text-2xl font-bold text-white"><?php echo $total_reviews; ?></div>
            </div>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="bg-gaming-850 border border-gaming-border rounded-xl p-4 flex flex-col sm:flex-row items-center justify-between gap-4">
        <form method="GET" action="/admin/reviews.php" class="flex flex-wrap items-center gap-3 w-full sm:w-auto">
            <label class="text-xs font-semibold text-zinc-400">Filter by Rating:</label>
            <select name="rating" class="px-3 py-1.5 rounded-lg bg-gaming-900 border border-gaming-border text-xs text-white focus:outline-none">
                <option value="0">All Ratings</option>
                <option value="5" <?php echo $rating_filter === 5 ? 'selected' : ''; ?>>★★★★★ (5 Stars)</option>
                <option value="4" <?php echo $rating_filter === 4 ? 'selected' : ''; ?>>★★★★☆ (4 Stars)</option>
                <option value="3" <?php echo $rating_filter === 3 ? 'selected' : ''; ?>>★★★☆☆ (3 Stars)</option>
                <option value="2" <?php echo $rating_filter === 2 ? 'selected' : ''; ?>>★★☆☆☆ (2 Stars)</option>
                <option value="1" <?php echo $rating_filter === 1 ? 'selected' : ''; ?>>★☆☆☆☆ (1 Star)</option>
            </select>

            <select name="status" class="px-3 py-1.5 rounded-lg bg-gaming-900 border border-gaming-border text-xs text-white focus:outline-none">
                <option value="">All Statuses</option>
                <option value="visible" <?php echo $status_filter === 'visible' ? 'selected' : ''; ?>>Visible</option>
                <option value="hidden" <?php echo $status_filter === 'hidden' ? 'selected' : ''; ?>>Hidden</option>
            </select>

            <button type="submit" class="px-3.5 py-1.5 bg-gaming-800 hover:bg-gaming-750 text-xs font-semibold text-white rounded-lg border border-gaming-border">Filter</button>
            <?php if ($rating_filter > 0 || !empty($status_filter)): ?>
                <a href="/admin/reviews.php" class="text-xs text-red-400 hover:underline">Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Reviews List -->
    <div class="bg-gaming-850 border border-gaming-border rounded-2xl overflow-hidden shadow-2xl">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="text-zinc-400 bg-gaming-900/60 uppercase border-b border-gaming-border">
                        <th class="py-3 px-4">User</th>
                        <th class="py-3 px-4">Product</th>
                        <th class="py-3 px-4">Rating</th>
                        <th class="py-3 px-4">Review Text</th>
                        <th class="py-3 px-4">Date</th>
                        <th class="py-3 px-4 text-center">Status</th>
                        <th class="py-3 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gaming-border/60">
                    <?php if (empty($reviews)): ?>
                        <tr>
                            <td colspan="7" class="py-8 text-center text-zinc-500">No customer reviews match your query.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($reviews as $rev): ?>
                            <tr class="hover:bg-gaming-800/40 transition-colors">
                                <td class="py-3 px-4">
                                    <div class="font-bold text-white"><?php echo e($rev['username']); ?></div>
                                    <?php if (!empty($rev['order_id'])): ?>
                                        <div class="text-[10px] text-zinc-500">Order #<?php echo $rev['order_id']; ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3 px-4">
                                    <span class="font-semibold text-zinc-200"><?php echo e($rev['product_name']); ?></span>
                                </td>
                                <td class="py-3 px-4">
                                    <div class="text-amber-400 text-sm tracking-widest font-bold">
                                        <?php echo str_repeat('★', (int)$rev['rating']) . str_repeat('☆', 5 - (int)$rev['rating']); ?>
                                    </div>
                                    <span class="text-[10px] text-zinc-400"><?php echo $rev['rating']; ?> / 5</span>
                                </td>
                                <td class="py-3 px-4 max-w-md">
                                    <p class="text-zinc-300 leading-relaxed"><?php echo nl2br(e($rev['review_text'])); ?></p>
                                </td>
                                <td class="py-3 px-4 text-zinc-400 whitespace-nowrap">
                                    <?php echo date('M d, Y', strtotime($rev['created_at'])); ?>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <a href="/admin/reviews.php?action=toggle&id=<?php echo $rev['id']; ?>">
                                        <?php if ($rev['status'] === 'visible'): ?>
                                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-950 text-emerald-300 border border-emerald-800">Visible</span>
                                        <?php else: ?>
                                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-zinc-800 text-zinc-400 border border-zinc-700">Hidden</span>
                                        <?php endif; ?>
                                    </a>
                                </td>
                                <td class="py-3 px-4 text-right space-x-2 whitespace-nowrap">
                                    <a href="/admin/reviews.php?action=toggle&id=<?php echo $rev['id']; ?>" class="px-2.5 py-1 rounded-lg bg-gaming-800 hover:bg-gaming-700 text-zinc-200 border border-gaming-border text-[11px] font-semibold">
                                        <?php echo $rev['status'] === 'visible' ? 'Hide' : 'Show'; ?>
                                    </a>
                                    <a href="/admin/reviews.php?action=delete&id=<?php echo $rev['id']; ?>" 
                                       onclick="return confirm('Permanently delete this review?')"
                                       class="px-2.5 py-1 rounded-lg bg-red-950/60 hover:bg-red-900 text-red-300 border border-red-800/40 text-[11px] font-semibold">
                                        Delete
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
