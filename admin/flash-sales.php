<?php
$page_title = "Flash Sales & Limited Deals";
require_once __DIR__ . '/includes/admin_header.php';

$errors = [];

// Handle End / Disable Flash Sale
if (isset($_GET['action']) && $_GET['action'] === 'end') {
    $prod_id = (int)($_GET['id'] ?? 0);
    if ($prod_id > 0) {
        $stmt_end = $pdo->prepare("UPDATE products SET flash_sale_enabled = 0 WHERE id = ?");
        $stmt_end->execute([$prod_id]);
        log_admin_activity('end_flash_sale', "Ended flash sale for product ID: {$prod_id}");
        set_flash('success', "Flash sale disabled for this product.");
        header("Location: /admin/flash-sales.php");
        exit;
    }
}

// Handle Add / Update Flash Sale POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validate();

    $prod_id = (int)($_POST['product_id'] ?? 0);
    $sale_price = (float)($_POST['flash_sale_price'] ?? 0);
    $start_time = !empty($_POST['flash_sale_start']) ? date('Y-m-d H:i:s', strtotime($_POST['flash_sale_start'])) : date('Y-m-d H:i:s');
    $end_time = !empty($_POST['flash_sale_end']) ? date('Y-m-d H:i:s', strtotime($_POST['flash_sale_end'])) : date('Y-m-d H:i:s', time() + 86400 * 3);
    $enabled = isset($_POST['flash_sale_enabled']) ? 1 : 0;

    if ($prod_id <= 0) {
        $errors[] = "Please select a product.";
    }

    // Verify product price
    $stmt_p = $pdo->prepare("SELECT name, price FROM products WHERE id = ?");
    $stmt_p->execute([$prod_id]);
    $prod = $stmt_p->fetch();

    if (!$prod) {
        $errors[] = "Selected product was not found.";
    } else {
        if ($sale_price <= 0) {
            $errors[] = "Flash sale price in ₹ must be greater than 0.";
        } elseif ($sale_price >= (float)$prod['price']) {
            $errors[] = "Flash sale price (" . format_currency($sale_price) . ") must be lower than standard price (" . format_currency($prod['price']) . ").";
        }
    }

    if (strtotime($end_time) <= strtotime($start_time)) {
        $errors[] = "End time must be after start time.";
    }

    if (empty($errors)) {
        $stmt_up = $pdo->prepare("
            UPDATE products 
            SET flash_sale_enabled = ?, 
                flash_sale_price = ?, 
                flash_sale_start = ?, 
                flash_sale_end = ? 
            WHERE id = ?
        ");
        $stmt_up->execute([$enabled, $sale_price, $start_time, $end_time, $prod_id]);
        log_admin_activity('configure_flash_sale', "Configured flash sale for product {$prod['name']} at " . format_currency($sale_price));
        set_flash('success', "Flash sale for '{$prod['name']}' successfully configured!");
        header("Location: /admin/flash-sales.php");
        exit;
    }
}

// Fetch all products that have flash sales configured
$flash_sales = $pdo->query("
    SELECT id, name, price, original_price, image, flash_sale_enabled, flash_sale_price, flash_sale_start, flash_sale_end 
    FROM products 
    WHERE flash_sale_enabled = 1 OR flash_sale_price IS NOT NULL 
    ORDER BY flash_sale_enabled DESC, flash_sale_end ASC
")->fetchAll();

// All products for dropdown
$all_products = $pdo->query("SELECT id, name, price FROM products ORDER BY name ASC")->fetchAll();

// If editing a flash sale
$editing_sale = null;
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $stmt_es = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt_es->execute([$edit_id]);
    $editing_sale = $stmt_es->fetch();
}
?>

<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="font-gaming text-2xl sm:text-3xl font-bold text-white tracking-wide flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-amber-500 animate-pulse"></span> FLASH DEALS & LIMITED-TIME SALES
            </h1>
            <p class="text-xs text-zinc-400 mt-1">Boost store conversions with real-time countdown deals and special promotional diamond pricing</p>
        </div>
        <div>
            <a href="/index.php" target="_blank" class="px-4 py-2.5 rounded-xl bg-gaming-800 hover:bg-gaming-750 text-xs font-semibold text-zinc-300 border border-gaming-border inline-flex items-center gap-2">
                <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                <span>Preview Storefront</span>
            </a>
        </div>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="p-4 rounded-xl bg-red-950/70 border border-red-500/60 text-red-200 text-xs space-y-1">
            <?php foreach ($errors as $err): ?>
                <p>• <?php echo e($err); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Add / Edit Flash Sale Card -->
        <div class="lg:col-span-1">
            <div class="bg-gaming-850 border border-gaming-border rounded-2xl p-6 space-y-5 sticky top-20 shadow-xl">
                <div class="flex items-center justify-between border-b border-gaming-border pb-3">
                    <h2 class="font-gaming text-lg font-bold text-white flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                        <?php echo $editing_sale ? 'Edit Flash Sale' : 'Create Flash Sale'; ?>
                    </h2>
                    <?php if ($editing_sale): ?>
                        <a href="/admin/flash-sales.php" class="text-xs text-zinc-400 hover:text-white">Cancel</a>
                    <?php endif; ?>
                </div>

                <form method="POST" action="/admin/flash-sales.php" class="space-y-4">
                    <?php echo csrf_field(); ?>

                    <div>
                        <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1">Select Product *</label>
                        <select name="product_id" required class="w-full px-3.5 py-2.5 rounded-xl bg-gaming-900 border border-gaming-border focus:border-red-500 text-sm text-white focus:outline-none">
                            <option value="">-- Choose Product --</option>
                            <?php foreach ($all_products as $p): ?>
                                <option value="<?php echo $p['id']; ?>" <?php echo (($editing_sale['id'] ?? 0) == $p['id']) ? 'selected' : ''; ?>>
                                    <?php echo e($p['name']); ?> (Normal: <?php echo format_currency($p['price']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1">Sale Price (₹ INR) *</label>
                        <input type="number" step="0.01" min="0.01" name="flash_sale_price" 
                               value="<?php echo e($editing_sale['flash_sale_price'] ?? ''); ?>" required
                               placeholder="e.g. 69.00"
                               class="w-full px-3.5 py-2.5 rounded-xl bg-gaming-900 border border-gaming-border focus:border-red-500 text-sm text-white focus:outline-none">
                        <p class="text-[10px] text-zinc-500 mt-1">Must be strictly lower than regular price.</p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1">Start Date & Time</label>
                        <input type="datetime-local" name="flash_sale_start" 
                               value="<?php echo !empty($editing_sale['flash_sale_start']) ? date('Y-m-d\TH:i', strtotime($editing_sale['flash_sale_start'])) : date('Y-m-d\TH:i'); ?>"
                               class="w-full px-3.5 py-2.5 rounded-xl bg-gaming-900 border border-gaming-border focus:border-red-500 text-sm text-white focus:outline-none">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1">End Date & Time</label>
                        <input type="datetime-local" name="flash_sale_end" 
                               value="<?php echo !empty($editing_sale['flash_sale_end']) ? date('Y-m-d\TH:i', strtotime($editing_sale['flash_sale_end'])) : date('Y-m-d\TH:i', time() + 86400 * 3); ?>"
                               class="w-full px-3.5 py-2.5 rounded-xl bg-gaming-900 border border-gaming-border focus:border-red-500 text-sm text-white focus:outline-none">
                    </div>

                    <div class="flex items-center gap-3 pt-1">
                        <input type="checkbox" name="flash_sale_enabled" id="fs_enabled" value="1"
                               <?php echo (!isset($editing_sale) || !empty($editing_sale['flash_sale_enabled'])) ? 'checked' : ''; ?>
                               class="w-4 h-4 rounded text-red-600 bg-gaming-900 border-gaming-border">
                        <label for="fs_enabled" class="text-xs font-semibold text-zinc-300 cursor-pointer">
                            Activate Flash Sale Deal Immediately
                        </label>
                    </div>

                    <button type="submit" class="w-full py-3 rounded-xl bg-gradient-to-r from-amber-600 to-red-600 hover:from-amber-500 hover:to-red-500 text-white font-gaming font-bold text-sm uppercase tracking-wider shadow-red-subtle transition-all">
                        <?php echo $editing_sale ? 'Save Flash Deal' : 'Launch Flash Deal'; ?>
                    </button>
                </form>
            </div>
        </div>

        <!-- Current Deals Table Card -->
        <div class="lg:col-span-2">
            <div class="bg-gaming-850 border border-gaming-border rounded-2xl overflow-hidden shadow-2xl">
                <div class="p-5 border-b border-gaming-border flex items-center justify-between">
                    <h2 class="font-gaming text-lg font-bold text-white flex items-center gap-2">
                        <span>Configured Flash Deals</span>
                        <span class="text-xs px-2 py-0.5 rounded-full bg-amber-500/20 text-amber-300 border border-amber-500/40">
                            <?php echo count($flash_sales); ?>
                        </span>
                    </h2>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="text-zinc-400 bg-gaming-900/60 uppercase border-b border-gaming-border">
                                <th class="py-3 px-4">Product</th>
                                <th class="py-3 px-4">Deal Price</th>
                                <th class="py-3 px-4">Time Window</th>
                                <th class="py-3 px-4 text-center">Status</th>
                                <th class="py-3 px-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gaming-border/60">
                            <?php if (empty($flash_sales)): ?>
                                <tr>
                                    <td colspan="5" class="py-8 text-center text-zinc-500">No flash sales configured yet. Use the form to launch a deal.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($flash_sales as $fs): 
                                    $now = time();
                                    $is_active = $fs['flash_sale_enabled'] && 
                                                 (!empty($fs['flash_sale_start']) ? strtotime($fs['flash_sale_start']) <= $now : true) && 
                                                 (!empty($fs['flash_sale_end']) ? strtotime($fs['flash_sale_end']) >= $now : true);
                                    $is_expired = !empty($fs['flash_sale_end']) && strtotime($fs['flash_sale_end']) < $now;
                                ?>
                                    <tr class="hover:bg-gaming-800/40 transition-colors">
                                        <td class="py-3 px-4">
                                            <div class="font-bold text-white"><?php echo e($fs['name']); ?></div>
                                            <div class="text-[11px] text-zinc-400">Normal: <?php echo format_currency($fs['price']); ?></div>
                                        </td>
                                        <td class="py-3 px-4">
                                            <div class="font-gaming font-bold text-amber-400 text-sm">
                                                <?php echo format_currency($fs['flash_sale_price']); ?>
                                            </div>
                                            <?php if ($fs['price'] > $fs['flash_sale_price']): ?>
                                                <span class="text-[10px] text-emerald-400 font-semibold">
                                                    -<?php echo round((($fs['price'] - $fs['flash_sale_price']) / $fs['price']) * 100); ?>% OFF
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-3 px-4 text-zinc-300">
                                            <div class="text-[11px]">Start: <?php echo !empty($fs['flash_sale_start']) ? date('M d, H:i', strtotime($fs['flash_sale_start'])) : 'Immediate'; ?></div>
                                            <div class="text-[11px] text-zinc-400">End: <?php echo !empty($fs['flash_sale_end']) ? date('M d, H:i', strtotime($fs['flash_sale_end'])) : 'Manual'; ?></div>
                                        </td>
                                        <td class="py-3 px-4 text-center">
                                            <?php if ($is_active): ?>
                                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-950 text-amber-300 border border-amber-800 animate-pulse">
                                                    LIVE NOW 🔥
                                                </span>
                                            <?php elseif ($is_expired): ?>
                                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-zinc-800 text-zinc-400 border border-zinc-700">
                                                    Expired
                                                </span>
                                            <?php else: ?>
                                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-zinc-800 text-zinc-400 border border-zinc-700">
                                                    Disabled
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-3 px-4 text-right space-x-2">
                                            <a href="/admin/flash-sales.php?edit=<?php echo $fs['id']; ?>" class="px-2.5 py-1 rounded-lg bg-gaming-800 hover:bg-gaming-700 text-zinc-200 border border-gaming-border text-[11px] font-semibold">
                                                Edit
                                            </a>
                                            <?php if ($fs['flash_sale_enabled']): ?>
                                                <a href="/admin/flash-sales.php?action=end&id=<?php echo $fs['id']; ?>" 
                                                   onclick="return confirm('Stop this flash sale now?')"
                                                   class="px-2.5 py-1 rounded-lg bg-red-950/60 hover:bg-red-900 text-red-300 border border-red-800/40 text-[11px] font-semibold">
                                                    End Sale
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
