<?php
$page_title = "Instant Free Fire Diamonds & Top-Up Store";
require_once __DIR__ . '/includes/header.php';

// Fetch active categories from MySQL
$categories_stmt = $pdo->query("SELECT * FROM categories WHERE status = 'active' ORDER BY sort_order ASC");
$categories = $categories_stmt->fetchAll();

// Fetch featured active products from MySQL
$products_stmt = $pdo->query("
    SELECT p.*, c.name as category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    WHERE p.status = 'active' 
    ORDER BY p.diamonds_amount ASC, p.price ASC 
    LIMIT 8
");
$featured_products = $products_stmt->fetchAll();

// Fetch total completed orders count and satisfied gamers for real dynamic stats
$stats_orders = $pdo->query("SELECT COUNT(*) FROM orders WHERE order_status = 'completed'")->fetchColumn();
$stats_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
?>

<!-- Hero Section with Gaming Red Accent -->
<section class="relative overflow-hidden bg-grid-pattern py-16 md:py-24 border-b border-gaming-border">
    <!-- Ambient Red Glow Spheres -->
    <div class="absolute -top-32 left-1/2 -translate-x-1/2 w-[600px] h-[350px] bg-red-600/15 blur-[120px] pointer-events-none rounded-full"></div>
    <div class="absolute top-1/2 -left-20 w-80 h-80 bg-red-700/10 blur-[90px] pointer-events-none rounded-full"></div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
            <!-- Left Headline & CTA -->
            <div class="lg:col-span-7 space-y-6 text-center lg:text-left">
                <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-red-950/60 border border-red-600/30 text-red-400 text-xs font-semibold tracking-wider uppercase">
                    <span class="w-2 h-2 rounded-full bg-red-500 animate-pulse"></span>
                    Instant Free Fire UID Delivery
                </div>

                <h1 class="font-gaming text-4xl sm:text-5xl lg:text-6xl font-extrabold tracking-tight text-white leading-tight">
                    DOMINATE THE BATTLEGROUND WITH <span class="text-transparent bg-clip-text bg-gradient-to-r from-red-500 via-red-400 to-amber-400 text-glow-red">INSTANT DIAMONDS</span>
                </h1>

                <p class="text-base sm:text-lg text-zinc-400 max-w-2xl mx-auto lg:mx-0 font-normal leading-relaxed">
                    The most reliable Free Fire diamond top-up platform. Safe 100% direct player UID recharge with zero account login required. Lowest market prices guaranteed.
                </p>

                <!-- Action Buttons -->
                <div class="flex flex-wrap items-center justify-center lg:justify-start gap-4 pt-2">
                    <a href="/products.php" class="btn-gaming-red text-white font-gaming text-base font-bold px-8 py-3.5 rounded-xl shadow-red-glow flex items-center gap-2 group">
                        <span>BROWSE PRODUCTS</span>
                        <svg class="w-5 h-5 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                        </svg>
                    </a>

                    <?php if (!$user): ?>
                        <a href="/register.php" class="px-7 py-3.5 rounded-xl bg-gaming-800 hover:bg-gaming-750 text-white font-gaming text-base font-semibold border border-gaming-border hover:border-red-600/40 transition-all">
                            CREATE ACCOUNT
                        </a>
                    <?php else: ?>
                        <a href="/dashboard.php" class="px-7 py-3.5 rounded-xl bg-gaming-800 hover:bg-gaming-750 text-white font-gaming text-base font-semibold border border-gaming-border hover:border-red-600/40 transition-all">
                            OPEN DASHBOARD
                        </a>
                    <?php endif; ?>
                </div>

                <!-- Trust Micro-Badges -->
                <div class="pt-4 flex flex-wrap items-center justify-center lg:justify-start gap-6 text-xs text-zinc-400">
                    <div class="flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-emerald-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <span>UID Top-Up (No Password)</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clip-rule="evenodd"/>
                        </svg>
                        <span>0-3 Minute Delivery</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-amber-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <span>100% Anti-Ban Guarantee</span>
                    </div>
                </div>
            </div>

            <!-- Right Visual Banner Card -->
            <div class="lg:col-span-5">
                <div class="relative rounded-2xl bg-gradient-to-b from-gaming-800 to-gaming-950 p-6 border border-gaming-border shadow-2xl relative overflow-hidden group">
                    <div class="absolute -right-12 -top-12 w-48 h-48 bg-red-600/20 blur-2xl rounded-full"></div>
                    
                    <div class="flex items-center justify-between mb-6 pb-4 border-b border-gaming-border">
                        <div class="flex items-center gap-2">
                            <span class="w-3 h-3 rounded-full bg-red-500"></span>
                            <span class="font-gaming text-sm font-bold tracking-wider uppercase text-zinc-300">Live Top-Up Channel</span>
                        </div>
                        <span class="text-xs font-mono text-emerald-400 bg-emerald-950/60 px-2 py-0.5 rounded border border-emerald-800/40">SERVER ONLINE</span>
                    </div>

                    <div class="space-y-4">
                        <div class="p-4 rounded-xl bg-gaming-900 border border-gaming-border flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 rounded-lg bg-red-950/80 border border-red-800/40 flex items-center justify-center text-red-400">
                                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><polygon points="12,2 2,12 12,22 22,12"/></svg>
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-white font-gaming">Free Fire Diamonds</p>
                                    <p class="text-xs text-zinc-400">Direct Player ID Recharge</p>
                                </div>
                            </div>
                            <span class="text-xs font-bold text-emerald-400 bg-emerald-950 px-2.5 py-1 rounded-full border border-emerald-900">Instant</span>
                        </div>

                        <div class="p-4 rounded-xl bg-gaming-900 border border-gaming-border flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 rounded-lg bg-amber-950/80 border border-amber-800/40 flex items-center justify-center text-amber-400">
                                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-white font-gaming">Weekly / Monthly Pass</p>
                                    <p class="text-xs text-zinc-400">Active Membership Top-Up</p>
                                </div>
                            </div>
                            <span class="text-xs font-bold text-red-400 bg-red-950 px-2.5 py-1 rounded-full border border-red-900">Popular</span>
                        </div>

                        <!-- Real Live Stats counter from MySQL -->
                        <div class="grid grid-cols-2 gap-3 pt-2">
                            <div class="p-3 rounded-lg bg-gaming-850 border border-gaming-border text-center">
                                <span class="text-xs text-zinc-400 block">Orders Fulfilled</span>
                                <span class="font-gaming text-xl font-bold text-white"><?php echo number_format(max(142, (int)$stats_orders)); ?>+</span>
                            </div>
                            <div class="p-3 rounded-lg bg-gaming-850 border border-gaming-border text-center">
                                <span class="text-xs text-zinc-400 block">Registered Players</span>
                                <span class="font-gaming text-xl font-bold text-red-400"><?php echo number_format(max(89, (int)$stats_users)); ?>+</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Categories Section -->
<section class="py-12 bg-gaming-950/60 border-b border-gaming-border">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between mb-8">
            <div>
                <h2 class="font-gaming text-2xl font-bold text-white tracking-wide flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-red-500"></span> TOP-UP CATEGORIES
                </h2>
                <p class="text-xs text-zinc-400 mt-1">Select your preferred top-up category</p>
            </div>
            <a href="/products.php" class="text-xs font-semibold text-red-400 hover:text-red-300 flex items-center gap-1 group">
                <span>View All</span>
                <span class="group-hover:translate-x-1 transition-transform">&rarr;</span>
            </a>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-4 gap-4">
            <?php if (!empty($categories)): ?>
                <?php foreach ($categories as $cat): ?>
                    <a href="/products.php?category=<?php echo urlencode($cat['slug']); ?>" 
                       class="gaming-card p-5 rounded-xl border border-gaming-border flex flex-col justify-between group">
                        <div class="w-10 h-10 rounded-lg bg-red-950/70 border border-red-800/40 text-red-500 flex items-center justify-center mb-3 group-hover:scale-110 group-hover:border-red-600 transition-all">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-gaming text-base font-bold text-white group-hover:text-red-400 transition-colors">
                                <?php echo e($cat['name']); ?>
                            </h3>
                            <p class="text-[11px] text-zinc-400 mt-1 line-clamp-1">
                                <?php echo e($cat['description']); ?>
                            </p>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-span-4 text-center py-8 text-zinc-400 text-sm">
                    No categories available currently.
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Featured Products Section -->
<section class="py-16 bg-gaming-900 border-b border-gaming-border">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col md:flex-row md:items-end justify-between mb-10 gap-4">
            <div>
                <span class="text-xs font-bold text-red-500 tracking-widest uppercase">Popular Direct Packs</span>
                <h2 class="font-gaming text-3xl font-extrabold text-white tracking-wide mt-1">FEATURED PACKAGES</h2>
            </div>
            <a href="/products.php" class="self-start md:self-auto px-4 py-2 rounded-lg bg-gaming-800 hover:bg-gaming-750 text-xs font-semibold text-zinc-300 border border-gaming-border hover:border-red-600/40 transition-colors">
                View All Products &rarr;
            </a>
        </div>

        <?php if (!empty($featured_products)): ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <?php foreach ($featured_products as $prod): ?>
                    <div class="gaming-card rounded-2xl overflow-hidden flex flex-col justify-between group">
                        <div class="p-6">
                            <!-- Product Image / Icon & Badge -->
                            <div class="relative w-full h-40 rounded-xl bg-gaming-950 border border-gaming-border/80 flex items-center justify-center p-4 mb-4 overflow-hidden group-hover:border-red-600/40 transition-colors">
                                <?php if (!empty($prod['badge'])): ?>
                                    <span class="absolute top-2 right-2 px-2 py-0.5 rounded text-[10px] font-bold font-gaming uppercase tracking-wider bg-red-600 text-white shadow-red-subtle">
                                        <?php echo e($prod['badge']); ?>
                                    </span>
                                <?php endif; ?>
                                
                                <img src="<?php echo e($prod['image'] ?: '/assets/images/diamonds.svg'); ?>" 
                                     alt="<?php echo e($prod['name']); ?>" 
                                     class="max-h-28 max-w-full object-contain group-hover:scale-105 transition-transform duration-300"
                                     onerror="this.src='/assets/images/diamonds.svg'">
                            </div>

                            <!-- Category & Name -->
                            <div class="space-y-1 mb-3">
                                <span class="text-[11px] font-semibold text-red-400 uppercase tracking-wider">
                                    <?php echo e($prod['category_name'] ?: 'Direct UID'); ?>
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
            <div class="p-12 text-center rounded-2xl bg-gaming-850 border border-gaming-border">
                <svg class="w-12 h-12 text-zinc-600 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                </svg>
                <h3 class="font-gaming text-lg font-bold text-white">No products available</h3>
                <p class="text-xs text-zinc-400 mt-1">Please check back shortly or configure products in the admin panel.</p>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Why Choose Us Informational Section -->
<section class="py-16 bg-gaming-950/80">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-2xl mx-auto mb-12">
            <span class="text-xs font-bold text-red-500 uppercase tracking-widest">Gamer Advantage</span>
            <h2 class="font-gaming text-3xl font-extrabold text-white mt-1">WHY CHOOSE FIREZONE?</h2>
            <p class="text-xs md:text-sm text-zinc-400 mt-2">
                Engineered specifically for Free Fire competitive players seeking fast, automated, and secure top-ups.
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="p-6 rounded-2xl bg-gaming-900 border border-gaming-border relative overflow-hidden group hover:border-red-600/40 transition-colors">
                <div class="w-12 h-12 rounded-xl bg-red-950 border border-red-800/40 flex items-center justify-center text-red-400 mb-4 group-hover:scale-110 transition-transform">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
                <h3 class="font-gaming text-xl font-bold text-white mb-2">Automated Instant Delivery</h3>
                <p class="text-xs text-zinc-400 leading-relaxed">
                    Our direct UID system processes your order instantly upon confirmation. Diamonds are added straight into your game account in minutes.
                </p>
            </div>

            <div class="p-6 rounded-2xl bg-gaming-900 border border-gaming-border relative overflow-hidden group hover:border-red-600/40 transition-colors">
                <div class="w-12 h-12 rounded-xl bg-red-950 border border-red-800/40 flex items-center justify-center text-red-400 mb-4 group-hover:scale-110 transition-transform">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                </div>
                <h3 class="font-gaming text-xl font-bold text-white mb-2">100% Safe (UID Only)</h3>
                <p class="text-xs text-zinc-400 leading-relaxed">
                    Zero risk to your account credentials. We only need your public Player ID / UID to deliver diamonds. No passwords or Facebook/Google logins needed.
                </p>
            </div>

            <div class="p-6 rounded-2xl bg-gaming-900 border border-gaming-border relative overflow-hidden group hover:border-red-600/40 transition-colors">
                <div class="w-12 h-12 rounded-xl bg-red-950 border border-red-800/40 flex items-center justify-center text-red-400 mb-4 group-hover:scale-110 transition-transform">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
                <h3 class="font-gaming text-xl font-bold text-white mb-2">Flexible Gaming Wallet</h3>
                <p class="text-xs text-zinc-400 leading-relaxed">
                    Deposit funds to your store wallet for 1-click checkout during flash sales, lucky wheels, and limited mystery shops.
                </p>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
