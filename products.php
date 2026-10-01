<?php
$page_title = "Browse Free Fire Diamonds & Packs";
require_once __DIR__ . '/includes/header.php';

// Categories for filter
$categories_stmt = $pdo->query("SELECT * FROM categories WHERE status = 'active' ORDER BY sort_order ASC");
$all_categories = $categories_stmt->fetchAll();

// Filter parameters
$search = sanitize($_GET['q'] ?? '');
$category_filter = sanitize($_GET['category'] ?? ($_GET['cat'] ?? ''));
$sort = sanitize($_GET['sort'] ?? 'price_asc');
$featured_only = !empty($_GET['featured']);
$flash_only = !empty($_GET['flash']);
$min_price = isset($_GET['min_price']) && $_GET['min_price'] !== '' ? (float)$_GET['min_price'] : null;
$max_price = isset($_GET['max_price']) && $_GET['max_price'] !== '' ? (float)$_GET['max_price'] : null;

// Build SQL query dynamically with prepared statements
$where_clauses = ["p.status = 'active'"];
$params = [];

if (!empty($search)) {
    $where_clauses[] = "(p.name LIKE ? OR p.description LIKE ?)";
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}

if (!empty($category_filter)) {
    $where_clauses[] = "c.slug = ?";
    $params[] = $category_filter;
}

if ($featured_only) {
    $where_clauses[] = "p.is_featured = 1";
}

if ($flash_only) {
    $where_clauses[] = "p.flash_sale_enabled = 1 AND (p.flash_sale_start IS NULL OR p.flash_sale_start <= NOW()) AND (p.flash_sale_end IS NULL OR p.flash_sale_end >= NOW())";
}

if ($min_price !== null) {
    $where_clauses[] = "p.price >= ?";
    $params[] = $min_price;
}

if ($max_price !== null) {
    $where_clauses[] = "p.price <= ?";
    $params[] = $max_price;
}

$order_by = match($sort) {
    'price_desc' => 'p.price DESC',
    'diamonds_desc' => 'p.diamonds_amount DESC',
    'newest' => 'p.id DESC',
    default => 'p.price ASC'
};

$sql = "
    SELECT p.*, c.name as category_name, c.slug as category_slug,
           (SELECT COUNT(*) FROM product_variants pv WHERE pv.product_id = p.id AND pv.status = 'active') as variant_count,
           (SELECT AVG(r.rating) FROM reviews r WHERE r.product_id = p.id AND r.status = 'visible') as avg_rating,
           (SELECT COUNT(*) FROM reviews r WHERE r.product_id = p.id AND r.status = 'visible') as review_count
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    WHERE " . implode(' AND ', $where_clauses) . " 
    ORDER BY " . $order_by;

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-8">
    <!-- Header & Search Toolbar -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="font-gaming text-3xl font-extrabold text-white tracking-wide flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-red-500"></span> FREE FIRE STORE CATALOG
            </h1>
            <p class="text-xs text-zinc-400 mt-1">Select your desired diamond package or pass for instant UID top-up</p>
        </div>

        <!-- Search Bar -->
        <form method="GET" action="/products.php" class="flex items-center gap-2 w-full md:w-auto">
            <?php if (!empty($category_filter)): ?>
                <input type="hidden" name="category" value="<?php echo e($category_filter); ?>">
            <?php endif; ?>
            <div class="relative flex-1 md:w-72">
                <input type="text" name="q" value="<?php echo e($search); ?>" 
                       placeholder="Search diamonds, passes..." 
                       class="w-full pl-9 pr-4 py-2.5 rounded-xl bg-gaming-850 border border-gaming-border focus:border-red-500 text-xs text-white focus:outline-none">
                <svg class="w-4 h-4 text-zinc-400 absolute left-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>
            <button type="submit" class="btn-gaming-red text-white text-xs font-gaming font-bold px-4 py-2.5 rounded-xl shadow-red-subtle">
                Search
            </button>
            <?php if (!empty($search) || !empty($category_filter) || $featured_only || $flash_only || $min_price !== null || $max_price !== null): ?>
                <a href="/products.php" class="px-3 py-2.5 rounded-xl bg-gaming-800 text-zinc-400 hover:text-white text-xs border border-gaming-border" title="Reset All Filters">
                    ✕ Clear
                </a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Category Filter Tabs & Filter Controls -->
    <div class="p-4 rounded-2xl bg-gaming-900 border border-gaming-border space-y-4">
        <!-- Categories horizontal pills -->
        <div class="flex flex-wrap items-center gap-2">
            <a href="/products.php<?php echo !empty($search) ? '?q=' . urlencode($search) : ''; ?>" 
               class="px-3.5 py-1.5 rounded-xl text-xs font-semibold transition-all <?php echo empty($category_filter) && !$featured_only && !$flash_only ? 'bg-red-600 text-white shadow-red-subtle' : 'bg-gaming-850 text-zinc-400 hover:text-white border border-gaming-border'; ?>">
                All Categories
            </a>
            <?php foreach ($all_categories as $c): ?>
                <a href="/products.php?category=<?php echo urlencode($c['slug']); ?><?php echo !empty($search) ? '&q=' . urlencode($search) : ''; ?>" 
                   class="px-3.5 py-1.5 rounded-xl text-xs font-semibold transition-all <?php echo ($category_filter === $c['slug']) ? 'bg-red-600 text-white shadow-red-subtle' : 'bg-gaming-850 text-zinc-400 hover:text-white border border-gaming-border'; ?>">
                    <?php echo e($c['name']); ?>
                </a>
            <?php endforeach; ?>
            <a href="/products.php?featured=1" 
               class="px-3.5 py-1.5 rounded-xl text-xs font-semibold transition-all <?php echo $featured_only ? 'bg-amber-600 text-white shadow-red-subtle' : 'bg-gaming-850 text-amber-400 hover:text-white border border-amber-800/40'; ?>">
                ⭐ Featured Only
            </a>
            <a href="/products.php?flash=1" 
               class="px-3.5 py-1.5 rounded-xl text-xs font-semibold transition-all <?php echo $flash_only ? 'bg-red-600 text-white shadow-red-subtle' : 'bg-gaming-850 text-red-400 hover:text-white border border-red-800/40'; ?>">
                ⚡ Flash Deals
            </a>
        </div>

        <!-- Secondary Filters Row (Price Range + Sorting) -->
        <form method="GET" action="/products.php" class="flex flex-wrap items-center justify-between gap-4 pt-3 border-t border-gaming-border text-xs">
            <?php if (!empty($category_filter)): ?>
                <input type="hidden" name="category" value="<?php echo e($category_filter); ?>">
            <?php endif; ?>
            <?php if (!empty($search)): ?>
                <input type="hidden" name="q" value="<?php echo e($search); ?>">
            <?php endif; ?>

            <div class="flex flex-wrap items-center gap-3">
                <span class="text-zinc-400 font-medium">Price Range:</span>
                <input type="number" step="0.5" name="min_price" value="<?php echo $min_price !== null ? e($min_price) : ''; ?>" placeholder="Min $" class="w-20 px-2.5 py-1.5 rounded-lg bg-gaming-850 border border-gaming-border text-white text-xs">
                <span class="text-zinc-400">-</span>
                <input type="number" step="0.5" name="max_price" value="<?php echo $max_price !== null ? e($max_price) : ''; ?>" placeholder="Max $" class="w-20 px-2.5 py-1.5 rounded-lg bg-gaming-850 border border-gaming-border text-white text-xs">
                <button type="submit" class="px-3 py-1.5 rounded-lg bg-gaming-800 hover:bg-gaming-750 text-white font-medium border border-gaming-border">
                    Filter
                </button>
            </div>

            <div class="flex items-center gap-2">
                <span class="text-zinc-400">Sort by:</span>
                <select name="sort" onchange="this.form.submit()" class="px-3 py-1.5 rounded-lg bg-gaming-850 border border-gaming-border text-zinc-300 focus:outline-none">
                    <option value="price_asc" <?php echo $sort === 'price_asc' ? 'selected' : ''; ?>>Price: Low to High</option>
                    <option value="price_desc" <?php echo $sort === 'price_desc' ? 'selected' : ''; ?>>Price: High to Low</option>
                    <option value="diamonds_desc" <?php echo $sort === 'diamonds_desc' ? 'selected' : ''; ?>>Most Diamonds</option>
                    <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Newest Arrivals</option>
                </select>
            </div>
        </form>
    </div>

    <!-- Products Grid -->
    <?php if (empty($products)): ?>
        <div class="text-center py-20 rounded-2xl bg-gaming-900 border border-gaming-border p-8">
            <div class="w-16 h-16 mx-auto rounded-full bg-red-950/60 border border-red-800/40 flex items-center justify-center text-red-500 mb-4">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <h3 class="font-gaming text-xl font-bold text-white mb-2">No Products Available</h3>
            <p class="text-xs text-zinc-400 max-w-sm mx-auto mb-6">No diamond packages matched your selected search criteria.</p>
            <a href="/products.php" class="btn-gaming-red text-white text-xs font-gaming font-bold px-6 py-2.5 rounded-xl shadow-red-subtle">
                Reset All Filters
            </a>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            <?php foreach ($products as $p): 
                $price_info = get_effective_product_price($p);
            ?>
                <div class="card-gaming rounded-2xl bg-gaming-850 border border-gaming-border hover:border-red-600/50 p-5 flex flex-col justify-between group transition-all relative">
                    <!-- Badges -->
                    <div class="absolute top-4 right-4 z-10 flex flex-col items-end gap-1">
                        <?php if ($price_info['is_sale']): ?>
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-red-600 text-white font-gaming shadow-red-subtle">
                                -<?php echo $price_info['discount_percent']; ?>% FLASH
                            </span>
                        <?php endif; ?>
                        <?php if (!empty($p['is_featured'])): ?>
                            <span class="px-2 py-0.5 rounded text-[9px] font-bold bg-amber-500/20 text-amber-400 border border-amber-600/40 uppercase">
                                ⭐ Featured
                            </span>
                        <?php endif; ?>
                    </div>

                    <div>
                        <!-- Diamond Visual -->
                        <div class="h-36 rounded-xl bg-gaming-900 border border-gaming-border p-4 flex flex-col items-center justify-center relative overflow-hidden mb-4 group-hover:border-red-900/40 transition-colors">
                            <div class="w-14 h-14 rounded-full bg-red-950/80 border border-red-700/60 flex items-center justify-center text-red-500 shadow-red-glow mb-2">
                                <svg class="w-7 h-7" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2L1 21h22L12 2zm0 3.84L19.46 19H4.54L12 5.84zM11 10h2v4h-2zm0 6h2v2h-2z"/></svg>
                            </div>
                            <span class="font-gaming font-extrabold text-white text-xl tracking-wider">
                                <?php echo e($p['diamonds_amount']); ?> Diamonds
                            </span>
                            <?php if ($p['bonus_diamonds'] > 0): ?>
                                <span class="text-[10px] text-amber-400 font-bold mt-0.5">+<?php echo e($p['bonus_diamonds']); ?> Bonus</span>
                            <?php endif; ?>
                        </div>

                        <!-- Product Info -->
                        <div class="flex items-center justify-between text-[11px] text-zinc-400 mb-1">
                            <span class="uppercase tracking-wider font-semibold"><?php echo e($p['category_name'] ?? 'Top-Up'); ?></span>
                            <?php if (!empty($p['review_count']) && $p['review_count'] > 0): ?>
                                <span class="text-amber-400 flex items-center gap-0.5 font-bold">
                                    ★ <?php echo number_format((float)$p['avg_rating'], 1); ?> (<?php echo $p['review_count']; ?>)
                                </span>
                            <?php endif; ?>
                        </div>

                        <h3 class="font-gaming font-bold text-white text-base group-hover:text-red-400 transition-colors">
                            <?php echo e($p['name']); ?>
                        </h3>
                        <p class="text-xs text-zinc-400 mt-1 line-clamp-2 leading-relaxed">
                            <?php echo e($p['description']); ?>
                        </p>

                        <?php if ($p['variant_count'] > 0): ?>
                            <div class="mt-2 inline-flex items-center gap-1 text-[10px] text-zinc-400 bg-gaming-800 px-2 py-0.5 rounded border border-gaming-border">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                                <span><?php echo $p['variant_count'] + 1; ?> Package Options</span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Price & CTA -->
                    <div class="pt-4 mt-4 border-t border-gaming-border">
                        <div class="flex items-baseline gap-2 mb-3">
                            <span class="font-gaming text-2xl font-extrabold text-white">
                                <?php echo format_currency($price_info['price']); ?>
                            </span>
                            <?php if ($price_info['is_sale']): ?>
                                <span class="text-xs text-zinc-400 line-through">
                                    <?php echo format_currency($price_info['original_price']); ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <a href="/checkout.php?product_id=<?php echo $p['id']; ?>" class="w-full btn-gaming-red text-white font-gaming text-xs font-bold py-2.5 rounded-xl shadow-red-subtle text-center block">
                            SELECT & BUY NOW &rarr;
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
