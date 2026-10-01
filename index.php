<?php
$page_title = "Instant Free Fire Diamonds & Top-Up Store";
require_once __DIR__ . '/includes/header.php';

// Fetch active categories from MySQL
$categories_stmt = $pdo->query("SELECT c.*, (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id AND p.status = 'active') as product_count FROM categories c WHERE c.status = 'active' ORDER BY c.sort_order ASC");
$categories = $categories_stmt->fetchAll();

// Fetch active banners from MySQL for homepage slider
$banners_stmt = $pdo->query("SELECT * FROM banners WHERE status = 'active' ORDER BY sort_order ASC, id DESC");
$banners = $banners_stmt->fetchAll();

// Fetch active flash sale products from MySQL
$flash_sales_stmt = $pdo->query("
    SELECT p.*, c.name as category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    WHERE p.status = 'active' 
      AND p.flash_sale_enabled = 1 
      AND (p.flash_sale_start IS NULL OR p.flash_sale_start <= NOW())
      AND (p.flash_sale_end IS NULL OR p.flash_sale_end >= NOW())
    ORDER BY p.id ASC
    LIMIT 4
");
$flash_sale_products = $flash_sales_stmt->fetchAll();

// Fetch featured active products from MySQL
$featured_products_stmt = $pdo->query("
    SELECT p.*, c.name as category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    WHERE p.status = 'active' AND (p.is_featured = 1 OR p.id <= 8)
    ORDER BY p.is_featured DESC, p.diamonds_amount ASC, p.price ASC 
    LIMIT 8
");
$featured_products = $featured_products_stmt->fetchAll();

// Fetch customer reviews from MySQL
$reviews_stmt = $pdo->query("
    SELECT r.*, u.username, u.name as user_name, p.name as product_name
    FROM reviews r
    JOIN users u ON r.user_id = u.id
    JOIN products p ON r.product_id = p.id
    WHERE r.status = 'visible'
    ORDER BY r.created_at DESC
    LIMIT 6
");
$reviews = $reviews_stmt->fetchAll();

// Dynamic stats from MySQL
$stats_orders = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE order_status = 'completed'")->fetchColumn();
$stats_users = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$stats_products = (int)$pdo->query("SELECT COUNT(*) FROM products WHERE status = 'active'")->fetchColumn();
?>

<!-- Announcement Ribbon -->
<?php if ($announcement = get_setting('announcement')): ?>
    <div class="bg-gradient-to-r from-red-950 via-red-900 to-gaming-900 border-b border-red-800/40 py-2.5 px-4 text-center text-xs font-semibold text-red-200 flex items-center justify-center gap-2">
        <span class="w-2 h-2 rounded-full bg-red-500 animate-ping"></span>
        <span><?php echo e($announcement); ?></span>
    </div>
<?php endif; ?>

<!-- Dynamic Slider / Banner Section -->
<section class="relative bg-gaming-950 border-b border-gaming-border overflow-hidden">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 md:py-12">
        <?php if (!empty($banners)): ?>
            <div class="relative rounded-2xl overflow-hidden border border-gaming-border bg-gaming-900 shadow-2xl group" id="heroSlider">
                <?php foreach ($banners as $idx => $b): ?>
                    <div class="slider-slide <?php echo $idx === 0 ? 'block' : 'hidden'; ?> transition-all duration-500" data-slide="<?php echo $idx; ?>">
                        <div class="grid grid-cols-1 lg:grid-cols-12 min-h-[360px] md:min-h-[420px] items-center p-6 md:p-12 relative overflow-hidden bg-gradient-to-r from-gaming-950 via-gaming-900 to-red-950/30">
                            <!-- Background glow -->
                            <div class="absolute -right-20 -top-20 w-96 h-96 bg-red-600/15 blur-[120px] rounded-full pointer-events-none"></div>
                            
                            <div class="lg:col-span-8 space-y-4 z-10">
                                <?php if (!empty($b['badge'])): ?>
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold font-mono bg-red-950 border border-red-700/60 text-red-400 uppercase tracking-widest">
                                        <span class="w-2 h-2 rounded-full bg-red-500 animate-pulse"></span>
                                        <?php echo e($b['badge']); ?>
                                    </span>
                                <?php endif; ?>

                                <h2 class="font-gaming text-3xl sm:text-4xl lg:text-5xl font-extrabold text-white tracking-tight leading-tight">
                                    <?php echo e($b['title']); ?>
                                </h2>

                                <?php if (!empty($b['subtitle'])): ?>
                                    <p class="text-base sm:text-lg font-semibold text-red-400">
                                        <?php echo e($b['subtitle']); ?>
                                    </p>
                                <?php endif; ?>

                                <p class="text-xs sm:text-sm text-zinc-400 max-w-xl leading-relaxed">
                                    <?php echo e($b['description'] ?: 'Top-up diamonds safely with official direct UID delivery in 0-3 minutes.'); ?>
                                </p>

                                <div class="pt-2 flex flex-wrap items-center gap-3">
                                    <a href="<?php echo e($b['button_url'] ?: '/products.php'); ?>" class="btn-gaming-red text-white font-gaming text-sm font-bold px-7 py-3 rounded-xl shadow-red-glow flex items-center gap-2 group/btn">
                                        <span><?php echo e($b['button_text'] ?: 'TOP UP NOW'); ?></span>
                                        <svg class="w-4 h-4 group-hover/btn:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                                    </a>
                                    <a href="/products.php" class="px-5 py-3 rounded-xl bg-gaming-800 text-zinc-300 hover:text-white text-xs font-bold border border-gaming-border hover:border-red-600/40 transition-colors">
                                        View All Packs
                                    </a>
                                </div>
                            </div>

                            <!-- Right visual preview -->
                            <div class="hidden lg:flex lg:col-span-4 justify-end z-10">
                                <div class="w-64 h-64 rounded-2xl bg-gradient-to-tr from-red-950 via-gaming-800 to-gaming-900 border border-red-800/40 p-6 flex flex-col justify-between shadow-2xl relative">
                                    <div class="flex items-center justify-between text-xs">
                                        <span class="text-red-400 font-mono font-bold">UID INSTANT</span>
                                        <span class="text-emerald-400 font-bold flex items-center gap-1">
                                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                            LIVE
                                        </span>
                                    </div>
                                    <div class="text-center py-4">
                                        <div class="w-20 h-20 mx-auto rounded-full bg-red-950/80 border border-red-700/60 flex items-center justify-center text-red-500 mb-3 shadow-red-glow">
                                            <svg class="w-10 h-10" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2L1 21h22L12 2zm0 3.84L19.46 19H4.54L12 5.84zM11 10h2v4h-2zm0 6h2v2h-2z"/></svg>
                                        </div>
                                        <p class="font-gaming font-bold text-white text-lg tracking-wide">100% SAFE</p>
                                        <p class="text-[11px] text-zinc-400">No passwords or login required</p>
                                    </div>
                                    <div class="pt-2 border-t border-gaming-border text-center text-[10px] text-zinc-400">
                                        Server-verified Free Fire Top-Up
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>

                <!-- Slider Controls -->
                <?php if (count($banners) > 1): ?>
                    <div class="absolute bottom-4 right-6 flex items-center gap-2 z-20">
                        <button type="button" id="prevSlideBtn" class="p-2 rounded-lg bg-gaming-950/80 hover:bg-red-600 text-white border border-gaming-border transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                        </button>
                        <button type="button" id="nextSlideBtn" class="p-2 rounded-lg bg-gaming-950/80 hover:bg-red-600 text-white border border-gaming-border transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- FLASH SALE SECTION (Admin Controlled & Server-Side Validated) -->
<?php if (!empty($flash_sale_products)): ?>
<section class="py-12 bg-gaming-900 border-b border-gaming-border relative overflow-hidden">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 mb-8">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-red-600 flex items-center justify-center text-white shadow-red-glow">
                    <svg class="w-6 h-6 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h2 class="font-gaming text-2xl sm:text-3xl font-extrabold text-white tracking-wide">LIMITED FLASH DEALS</h2>
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-red-950 text-red-400 border border-red-700/50 uppercase font-mono animate-pulse">Live Now</span>
                    </div>
                    <p class="text-xs sm:text-sm text-zinc-400">Exclusive time-limited diamond discounts verified directly from MySQL</p>
                </div>
            </div>
            <a href="/products.php" class="text-xs font-semibold text-red-400 hover:text-red-300 underline underline-offset-4 flex items-center gap-1">
                <span>View All Products</span> &rarr;
            </a>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <?php foreach ($flash_sale_products as $p): 
                $price_info = get_effective_product_price($p);
            ?>
                <div class="card-gaming rounded-2xl bg-gaming-850 border border-red-800/40 p-5 flex flex-col justify-between relative group hover:border-red-600 transition-all shadow-red-subtle">
                    <!-- Discount Badge -->
                    <div class="absolute top-4 right-4 z-10 flex items-center gap-1 px-2.5 py-1 rounded-lg bg-red-600 text-white font-gaming font-bold text-xs shadow-red-subtle">
                        <span>-<?php echo $price_info['discount_percent']; ?>%</span>
                    </div>

                    <div>
                        <div class="h-36 rounded-xl bg-gaming-900 border border-gaming-border p-4 flex flex-col items-center justify-center relative overflow-hidden mb-4 group-hover:border-red-900/40 transition-colors">
                            <div class="w-14 h-14 rounded-full bg-red-950/80 border border-red-700/60 flex items-center justify-center text-red-500 shadow-red-glow mb-2">
                                <svg class="w-7 h-7" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2L1 21h22L12 2zm0 3.84L19.46 19H4.54L12 5.84zM11 10h2v4h-2zm0 6h2v2h-2z"/></svg>
                            </div>
                            <span class="font-gaming font-extrabold text-white text-lg tracking-wide">
                                <?php echo e($p['diamonds_amount']); ?> Diamonds
                            </span>
                            <?php if ($p['bonus_diamonds'] > 0): ?>
                                <span class="text-[10px] text-amber-400 font-bold">+<?php echo e($p['bonus_diamonds']); ?> Bonus</span>
                            <?php endif; ?>
                        </div>

                        <span class="text-[11px] text-zinc-400 uppercase tracking-wider font-semibold"><?php echo e($p['category_name'] ?? 'Free Fire'); ?></span>
                        <h3 class="font-gaming font-bold text-white text-base mt-0.5 group-hover:text-red-400 transition-colors">
                            <?php echo e($p['name']); ?>
                        </h3>
                        <p class="text-xs text-zinc-400 mt-1 line-clamp-2"><?php echo e($p['description']); ?></p>
                    </div>

                    <div class="pt-4 mt-4 border-t border-gaming-border">
                        <div class="flex items-baseline gap-2 mb-3">
                            <span class="font-gaming text-2xl font-extrabold text-white">
                                <?php echo format_currency($price_info['price']); ?>
                            </span>
                            <span class="text-xs text-zinc-400 line-through">
                                <?php echo format_currency($price_info['original_price']); ?>
                            </span>
                        </div>
                        <a href="/checkout.php?product_id=<?php echo $p['id']; ?>" class="w-full btn-gaming-red text-white font-gaming text-xs font-bold py-2.5 rounded-xl shadow-red-subtle text-center block">
                            BUY NOW &rarr;
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Categories Section -->
<section class="py-12 bg-gaming-950 border-b border-gaming-border">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between mb-8">
            <div>
                <h2 class="font-gaming text-2xl sm:text-3xl font-extrabold text-white tracking-wide">BROWSE CATEGORIES</h2>
                <p class="text-xs sm:text-sm text-zinc-400">Explore diamond packs, passes, weapon tokens, and special crates</p>
            </div>
            <a href="/products.php" class="text-xs font-semibold text-red-400 hover:text-red-300">View Catalog &rarr;</a>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-4 gap-4">
            <?php foreach ($categories as $cat): ?>
                <a href="/products.php?cat=<?php echo urlencode($cat['slug']); ?>" class="card-gaming p-5 rounded-2xl bg-gaming-900 border border-gaming-border hover:border-red-600/50 transition-all flex flex-col justify-between group">
                    <div class="flex items-center justify-between mb-3">
                        <div class="w-10 h-10 rounded-xl bg-red-950 border border-red-800/40 flex items-center justify-center text-red-400 group-hover:scale-110 transition-transform">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        </div>
                        <span class="text-[11px] font-mono font-bold text-zinc-400 group-hover:text-red-400 transition-colors">
                            <?php echo $cat['product_count']; ?> Packs
                        </span>
                    </div>
                    <div>
                        <h3 class="font-gaming font-bold text-white text-base group-hover:text-red-400 transition-colors">
                            <?php echo e($cat['name']); ?>
                        </h3>
                        <p class="text-xs text-zinc-400 mt-1 line-clamp-1"><?php echo e($cat['description']); ?></p>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- FEATURED PRODUCTS -->
<section class="py-14 bg-gaming-900 border-b border-gaming-border">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 mb-8">
            <div>
                <span class="text-xs font-bold text-red-400 uppercase tracking-widest font-mono">Popular Top-Ups</span>
                <h2 class="font-gaming text-2xl sm:text-3xl font-extrabold text-white tracking-wide mt-1">FEATURED PACKAGES</h2>
            </div>
            <a href="/products.php" class="btn-gaming-red text-white font-gaming text-xs font-bold px-5 py-2.5 rounded-lg shadow-red-subtle self-start sm:self-auto">
                ALL PRODUCTS (<?php echo $stats_products; ?>)
            </a>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <?php foreach ($featured_products as $p): 
                $price_info = get_effective_product_price($p);
            ?>
                <div class="card-gaming rounded-2xl bg-gaming-850 border border-gaming-border hover:border-red-600/50 p-5 flex flex-col justify-between group transition-all">
                    <div>
                        <div class="h-32 rounded-xl bg-gaming-900 border border-gaming-border p-3 flex flex-col items-center justify-center relative overflow-hidden mb-3 group-hover:border-red-800/40 transition-colors">
                            <span class="font-gaming font-extrabold text-white text-xl tracking-wider">
                                <?php echo e($p['diamonds_amount']); ?> 💎
                            </span>
                            <?php if ($p['bonus_diamonds'] > 0): ?>
                                <span class="text-[10px] text-amber-400 font-bold mt-1">+<?php echo e($p['bonus_diamonds']); ?> Bonus Diamonds</span>
                            <?php endif; ?>
                            <?php if (!empty($p['badge'])): ?>
                                <span class="absolute top-2 left-2 px-2 py-0.5 rounded text-[9px] font-bold bg-red-950 text-red-400 border border-red-800/40 uppercase">
                                    <?php echo e($p['badge']); ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <span class="text-[11px] text-zinc-400 uppercase tracking-wider font-semibold"><?php echo e($p['category_name'] ?? 'Diamonds'); ?></span>
                        <h3 class="font-gaming font-bold text-white text-base mt-0.5 group-hover:text-red-400 transition-colors">
                            <?php echo e($p['name']); ?>
                        </h3>
                        <p class="text-xs text-zinc-400 mt-1 line-clamp-2"><?php echo e($p['description']); ?></p>
                    </div>

                    <div class="pt-4 mt-4 border-t border-gaming-border flex items-center justify-between">
                        <div>
                            <span class="text-[10px] text-zinc-400 block">Price</span>
                            <span class="font-gaming text-xl font-bold text-white"><?php echo format_currency($price_info['price']); ?></span>
                        </div>
                        <a href="/checkout.php?product_id=<?php echo $p['id']; ?>" class="btn-gaming-red text-white font-gaming text-xs font-bold px-4 py-2 rounded-lg shadow-red-subtle">
                            BUY NOW
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- PROMOTIONAL BANNERS: REFER & EARN + RESELLER B2B -->
<section class="py-12 bg-gaming-950 border-b border-gaming-border">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Refer & Earn Card -->
            <div class="p-6 sm:p-8 rounded-2xl bg-gradient-to-r from-red-950/60 via-gaming-900 to-gaming-900 border border-red-800/40 relative overflow-hidden flex flex-col justify-between">
                <div>
                    <span class="px-2.5 py-1 rounded text-[10px] font-bold bg-red-950 text-red-400 border border-red-700/50 uppercase font-mono">
                        Refer & Earn
                    </span>
                    <h3 class="font-gaming text-2xl font-bold text-white mt-3">Invite Friends, Earn Free Diamonds</h3>
                    <p class="text-xs text-zinc-400 mt-2 leading-relaxed">
                        Share your personalized referral code with fellow gamers. Receive cash bonuses credited straight to your store wallet every time they complete a diamond top-up!
                    </p>
                </div>
                <div class="pt-6">
                    <a href="/referral.php" class="inline-flex items-center gap-2 btn-gaming-red text-white text-xs font-bold px-6 py-2.5 rounded-lg shadow-red-subtle">
                        <span>Get Referral Link</span> &rarr;
                    </a>
                </div>
            </div>

            <!-- Reseller B2B Card -->
            <div class="p-6 sm:p-8 rounded-2xl bg-gradient-to-r from-amber-950/40 via-gaming-900 to-gaming-900 border border-amber-800/40 relative overflow-hidden flex flex-col justify-between">
                <div>
                    <span class="px-2.5 py-1 rounded text-[10px] font-bold bg-amber-950 text-amber-400 border border-amber-700/50 uppercase font-mono">
                        B2B Reseller Portal
                    </span>
                    <h3 class="font-gaming text-2xl font-bold text-white mt-3">Wholesale Pricing & Automated API</h3>
                    <p class="text-xs text-zinc-400 mt-2 leading-relaxed">
                        Run your own gaming shop or top-up service. Access exclusive discounted tier pricing, sub-reseller management, and developer REST API keys with instant status webhooks.
                    </p>
                </div>
                <div class="pt-6">
                    <a href="/reseller.php" class="inline-flex items-center gap-2 bg-gaming-800 hover:bg-gaming-750 text-amber-400 border border-amber-700/50 text-xs font-bold px-6 py-2.5 rounded-lg transition-colors">
                        <span>Open Reseller Panel</span> &rarr;
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- VERIFIED CUSTOMER REVIEWS (Loaded Dynamically from MySQL) -->
<?php if (!empty($reviews)): ?>
<section class="py-14 bg-gaming-900 border-b border-gaming-border">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-2xl mx-auto mb-10">
            <span class="text-xs font-bold text-red-400 uppercase tracking-widest font-mono">Gamer Feedback</span>
            <h2 class="font-gaming text-3xl font-extrabold text-white mt-1">VERIFIED PLAYER REVIEWS</h2>
            <p class="text-xs text-zinc-400 mt-2">Real reviews from gamers who completed authentic orders on our store</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <?php foreach ($reviews as $rev): ?>
                <div class="p-5 rounded-2xl bg-gaming-850 border border-gaming-border flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center gap-1 text-amber-400">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <svg class="w-4 h-4 <?php echo $i <= $rev['rating'] ? 'fill-current' : 'text-zinc-600'; ?>" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                <?php endfor; ?>
                            </div>
                            <span class="text-[10px] text-zinc-400"><?php echo date('M d, Y', strtotime($rev['created_at'])); ?></span>
                        </div>
                        <p class="text-xs text-zinc-300 italic mb-4">"<?php echo e($rev['review_text']); ?>"</p>
                    </div>
                    <div class="pt-3 border-t border-gaming-border flex items-center justify-between text-xs">
                        <div>
                            <span class="font-bold text-white block"><?php echo e($rev['user_name'] ?: $rev['username']); ?></span>
                            <span class="text-[10px] text-zinc-400"><?php echo e($rev['product_name']); ?></span>
                        </div>
                        <span class="text-[10px] text-emerald-400 font-mono font-bold flex items-center gap-1">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            Verified
                        </span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Why Choose Us -->
<section class="py-14 bg-gaming-950">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-2xl mx-auto mb-10">
            <span class="text-xs font-bold text-red-400 uppercase tracking-widest font-mono">Store Guarantee</span>
            <h2 class="font-gaming text-3xl font-extrabold text-white mt-1">WHY CHOOSE <?php echo strtoupper(e($site_name)); ?></h2>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="p-6 rounded-2xl bg-gaming-900 border border-gaming-border hover:border-red-600/40 transition-colors">
                <div class="w-12 h-12 rounded-xl bg-red-950 border border-red-800/40 flex items-center justify-center text-red-400 mb-4">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                </div>
                <h3 class="font-gaming text-lg font-bold text-white mb-2">Automated Server Delivery</h3>
                <p class="text-xs text-zinc-400 leading-relaxed">
                    Direct automated queue processing delivers diamonds directly to your Free Fire account ID in 0-3 minutes after payment confirmation.
                </p>
            </div>

            <div class="p-6 rounded-2xl bg-gaming-900 border border-gaming-border hover:border-red-600/40 transition-colors">
                <div class="w-12 h-12 rounded-xl bg-red-950 border border-red-800/40 flex items-center justify-center text-red-400 mb-4">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                </div>
                <h3 class="font-gaming text-lg font-bold text-white mb-2">100% Safe (UID Only)</h3>
                <p class="text-xs text-zinc-400 leading-relaxed">
                    Zero risk to your account credentials. We only need your public Player ID / UID to deliver diamonds. Never share your password with anyone.
                </p>
            </div>

            <div class="p-6 rounded-2xl bg-gaming-900 border border-gaming-border hover:border-red-600/40 transition-colors">
                <div class="w-12 h-12 rounded-xl bg-red-950 border border-red-800/40 flex items-center justify-center text-red-400 mb-4">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                </div>
                <h3 class="font-gaming text-lg font-bold text-white mb-2">Multiple Payment Methods</h3>
                <p class="text-xs text-zinc-400 leading-relaxed">
                    Pay securely using Store Wallet, International Credit Cards (Stripe), Instant UPI (Razorpay), Mobile Wallets (bKash/Nagad), or Direct Bank Transfer.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- Slider JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    const slides = document.querySelectorAll('.slider-slide');
    if (slides.length <= 1) return;
    
    let currentSlide = 0;
    const showSlide = (n) => {
        slides.forEach((s, idx) => {
            if (idx === n) {
                s.classList.remove('hidden');
                s.classList.add('block');
            } else {
                s.classList.add('hidden');
                s.classList.remove('block');
            }
        });
    };
    
    const prevBtn = document.getElementById('prevSlideBtn');
    const nextBtn = document.getElementById('nextSlideBtn');
    
    if (nextBtn) {
        nextBtn.addEventListener('click', () => {
            currentSlide = (currentSlide + 1) % slides.length;
            showSlide(currentSlide);
        });
    }
    if (prevBtn) {
        prevBtn.addEventListener('click', () => {
            currentSlide = (currentSlide - 1 + slides.length) % slides.length;
            showSlide(currentSlide);
        });
    }
    
    // Auto-advance slider every 6 seconds
    setInterval(() => {
        currentSlide = (currentSlide + 1) % slides.length;
        showSlide(currentSlide);
    }, 6000);
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
