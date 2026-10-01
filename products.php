<?php
$page_title = "Browse Free Fire Diamonds & Packs";
require_once __DIR__ . '/includes/header.php';

// Categories for filter tabs
$categories_stmt = $pdo->query("SELECT * FROM categories WHERE status = 'active' ORDER BY sort_order ASC");
$all_categories = $categories_stmt->fetchAll();

// Filter parameters
$search = sanitize($_GET['q'] ?? '');
$category_filter = sanitize($_GET['category'] ?? '');
$sort = sanitize($_GET['sort'] ?? 'price_asc');

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

$order_by = match($sort) {
    'price_desc' => 'p.price DESC',
    'newest' => 'p.id DESC',
    default => 'p.price ASC'
};

$sql = "
    SELECT p.*, c.name as category_name, c.slug as category_slug 
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
            <button type="submit" class="btn-gaming-red text-white text-xs font-gaming font-bold px-4 py-2.5 rounded-xl">
                Search
            </button>
            <?php if (!empty($search) || !empty($category_filter)): ?>
                <a href="/products.php" class="px-3 py-2.5 rounded-xl bg-gaming-800 text-zinc-400 hover:text-white text-xs border border-gaming-border" title="Reset Filters">
                    ✕
                </a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Category Filter Tabs & Sorting -->
    <div class="flex flex-wrap items-center justify-between gap-4 pb-4 border-b border-gaming-border">
        <!-- Categories horizontal pills -->
        <div class="flex flex-wrap items-center gap-2">
            <a href="/products.php<?php echo !empty($search) ? '?q=' . urlencode($search) : ''; ?>" 
               class="px-4 py-2 rounded-xl text-xs font-semibold transition-all <?php echo empty($category_filter) ? 'bg-red-600 text-white shadow-red-subtle' : 'bg-gaming-850 text-zinc-400 hover:text-white border border-gaming-border'; ?>">
                All Categories
            </a>
            <?php foreach ($all_categories as $c): ?>
                <a href="/products.php?category=<?php echo urlencode($c['slug']); ?><?php echo !empty($search) ? '&q=' . urlencode($search) : ''; ?>" 
                   class="px-4 py-2 rounded-xl text-xs font-semibold transition-all <?php echo ($category_filter === $c['slug']) ? 'bg-red-600 text-white shadow-red-subtle' : 'bg-gaming-850 text-zinc-400 hover:text-white border border-gaming-border'; ?>">
                    <?php echo e($c['name']); ?>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- Sort dropdown -->
        <div class="flex items-center gap-2 text-xs">
            <span class="text-zinc-400">Sort by:</span>
            <select onchange="location.href=this.value" class="px-3 py-1.5 rounded-lg bg-gaming-850 border border-gaming-border text-zinc-300 focus:outline-none">
                <option value="/products.php?<?php echo http_build_query(array_merge($_GET, ['sort' => 'price_asc'])); ?>" <?php echo $sort === 'price_asc' ? 'selected' : ''; ?>>Price: Low to High</option>
                <option value="/products.php?<?php echo http_build_query(array_merge($_GET, ['sort' => 'price_desc'])); ?>" <?php echo $sort === 'price_desc' ? 'selected' : ''; ?>>Price: High to Low</option>
                <option value="/products.php?<?php echo http_build_query(array_merge($_GET, ['sort' => 'newest'])); ?>" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Newest First</option>
            </select>
        </div>
    </div>

    <!-- Product Cards Grid -->
    <?php if (!empty($products)): ?>
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
            <?php foreach ($products as $prod): ?>
                <div class="gaming-card rounded-2xl overflow-hidden flex flex-col justify-between group">
                    <div class="p-6">
                        <!-- Product Image & Badge -->
                        <div class="relative w-full h-44 rounded-xl bg-gaming-950 border border-gaming-border/80 flex items-center justify-center p-4 mb-4 overflow-hidden group-hover:border-red-600/40 transition-colors">
                            <?php if (!empty($prod['badge'])): ?>
                                <span class="absolute top-2 right-2 px-2 py-0.5 rounded text-[10px] font-bold font-gaming uppercase tracking-wider bg-red-600 text-white shadow-red-subtle">
                                    <?php echo e($prod['badge']); ?>
                                </span>
                            <?php endif; ?>
                            
                            <img src="<?php echo e($prod['image'] ?: '/assets/images/diamonds.svg'); ?>" 
                                 alt="<?php echo e($prod['name']); ?>" 
                                 class="max-h-32 max-w-full object-contain group-hover:scale-105 transition-transform duration-300"
                                 onerror="this.src='/assets/images/diamonds.svg'">
                        </div>

                        <!-- Info -->
                        <div class="space-y-1 mb-2">
                            <span class="text-[11px] font-semibold text-red-400 uppercase tracking-wider">
                                <?php echo e($prod['category_name'] ?: 'Free Fire Top-Up'); ?>
                            </span>
                            <h3 class="font-gaming text-lg font-bold text-white group-hover:text-red-400 transition-colors">
                                <?php echo e($prod['name']); ?>
                            </h3>
                            <p class="text-xs text-zinc-400 line-clamp-2 leading-relaxed">
                                <?php echo e($prod['description']); ?>
                            </p>
                        </div>
                    </div>

                    <!-- Price and Buy Action -->
                    <div class="p-6 pt-0 mt-auto border-t border-gaming-border/60">
                        <div class="flex items-center justify-between my-3">
                            <div>
                                <div class="font-gaming text-xl font-extrabold text-white">
                                    <?php echo format_currency($prod['price']); ?>
                                </div>
                                <?php if ($prod['original_price'] && $prod['original_price'] > $prod['price']): ?>
                                    <span class="text-xs text-zinc-400 line-through">
                                        <?php echo format_currency($prod['original_price']); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <?php if ($prod['bonus_diamonds'] > 0): ?>
                                <span class="text-[11px] font-bold text-emerald-400 bg-emerald-950/80 border border-emerald-800/40 px-2 py-0.5 rounded">
                                    +<?php echo e($prod['bonus_diamonds']); ?> Bonus
                                </span>
                            <?php endif; ?>
                        </div>

                        <a href="/checkout.php?product_id=<?php echo $prod['id']; ?>" 
                           class="w-full btn-gaming-red text-white font-gaming font-bold text-sm py-2.5 px-4 rounded-xl flex items-center justify-center gap-2 shadow-red-subtle">
                            <span>BUY NOW</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                            </svg>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="p-16 text-center rounded-2xl bg-gaming-850 border border-gaming-border space-y-3">
            <svg class="w-12 h-12 text-zinc-600 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <h3 class="font-gaming text-lg font-bold text-white">No products found</h3>
            <p class="text-xs text-zinc-400">No products match your search or selected category filter.</p>
            <a href="/products.php" class="inline-block mt-2 px-4 py-2 rounded-xl bg-gaming-800 text-xs text-white border border-gaming-border hover:bg-gaming-750">
                Clear Filters
            </a>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
