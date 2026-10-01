<?php
$page_title = "Manage Product Variants";
require_once __DIR__ . '/includes/admin_header.php';

$errors = [];

// Handle Delete Variant
if (isset($_GET['action']) && $_GET['action'] === 'delete') {
    $var_id = (int)($_GET['id'] ?? 0);
    if ($var_id > 0) {
        $stmt_del = $pdo->prepare("DELETE FROM product_variants WHERE id = ?");
        $stmt_del->execute([$var_id]);
        log_admin_activity('delete_variant', "Deleted product variant ID: {$var_id}");
        set_flash('success', "Variant deleted successfully.");
        header("Location: /admin/product-variants.php" . (isset($_GET['product_id']) ? "?product_id=" . (int)$_GET['product_id'] : ''));
        exit;
    }
}

// Handle Status Toggle
if (isset($_GET['action']) && $_GET['action'] === 'toggle') {
    $var_id = (int)($_GET['id'] ?? 0);
    if ($var_id > 0) {
        $stmt_toggle = $pdo->prepare("UPDATE product_variants SET status = IF(status = 'active', 'inactive', 'active') WHERE id = ?");
        $stmt_toggle->execute([$var_id]);
        log_admin_activity('toggle_variant_status', "Toggled status for variant ID: {$var_id}");
        set_flash('success', "Variant status updated.");
        header("Location: /admin/product-variants.php" . (isset($_GET['product_id']) ? "?product_id=" . (int)$_GET['product_id'] : ''));
        exit;
    }
}

// Handle Add / Edit Variant POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validate();

    $var_id = (int)($_POST['variant_id'] ?? 0);
    $product_id = (int)($_POST['product_id'] ?? 0);
    $name = sanitize($_POST['name'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $original_price = !empty($_POST['original_price']) ? (float)$_POST['original_price'] : null;
    $diamonds_amount = (int)($_POST['diamonds_amount'] ?? 0);
    $bonus_diamonds = (int)($_POST['bonus_diamonds'] ?? 0);
    $description = sanitize($_POST['description'] ?? '');
    $status = in_array($_POST['status'] ?? '', ['active', 'inactive']) ? $_POST['status'] : 'active';
    $sort_order = (int)($_POST['sort_order'] ?? 0);

    if ($product_id <= 0) {
        $errors[] = "Please select a valid product.";
    }
    if (empty($name)) {
        $errors[] = "Variant name is required (e.g. Single Pack, 2x Pack).";
    }
    if ($price <= 0) {
        $errors[] = "Variant price in ₹ must be greater than 0.";
    }

    if (empty($errors)) {
        if ($var_id > 0) {
            $stmt_u = $pdo->prepare("UPDATE product_variants SET product_id = ?, name = ?, price = ?, original_price = ?, diamonds_amount = ?, bonus_diamonds = ?, description = ?, status = ?, sort_order = ? WHERE id = ?");
            $stmt_u->execute([$product_id, $name, $price, $original_price, $diamonds_amount, $bonus_diamonds, $description, $status, $sort_order, $var_id]);
            log_admin_activity('update_variant', "Updated variant '{$name}' for product ID {$product_id}");
            set_flash('success', "Variant '{$name}' updated successfully.");
        } else {
            $stmt_i = $pdo->prepare("INSERT INTO product_variants (product_id, name, price, original_price, diamonds_amount, bonus_diamonds, description, status, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt_i->execute([$product_id, $name, $price, $original_price, $diamonds_amount, $bonus_diamonds, $description, $status, $sort_order]);
            $new_id = $pdo->lastInsertId();
            log_admin_activity('create_variant', "Created variant '{$name}' (ID: {$new_id}) for product ID {$product_id}");
            set_flash('success', "New variant created successfully.");
        }
        header("Location: /admin/product-variants.php?product_id=" . $product_id);
        exit;
    }
}

// Fetch all active products for the dropdown
$products_list = $pdo->query("SELECT id, name, price FROM products ORDER BY name ASC")->fetchAll();

// Filter by product if specified
$filter_product_id = (int)($_GET['product_id'] ?? 0);

// Editing variant
$editing_var = null;
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $stmt_edit = $pdo->prepare("SELECT * FROM product_variants WHERE id = ?");
    $stmt_edit->execute([$edit_id]);
    $editing_var = $stmt_edit->fetch();
    if ($editing_var && empty($filter_product_id)) {
        $filter_product_id = (int)$editing_var['product_id'];
    }
}

// Build query for variants
$sql = "
    SELECT pv.*, p.name as product_name, p.price as base_product_price 
    FROM product_variants pv 
    JOIN products p ON pv.product_id = p.id 
";
if ($filter_product_id > 0) {
    $sql .= " WHERE pv.product_id = " . $filter_product_id;
}
$sql .= " ORDER BY p.name ASC, pv.sort_order ASC, pv.id ASC";

$variants = $pdo->query($sql)->fetchAll();
?>

<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="font-gaming text-2xl sm:text-3xl font-bold text-white tracking-wide flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-red-500"></span> PRODUCT PACKAGES & VARIANTS
            </h1>
            <p class="text-xs text-zinc-400 mt-1">Configure multi-pack choices (Single, Double 2x, Bonus packs) with accurate INR pricing</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="/admin/products.php" class="px-4 py-2.5 rounded-xl bg-gaming-800 hover:bg-gaming-750 text-xs font-semibold text-zinc-300 border border-gaming-border">
                &larr; Back to Catalog
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
        <!-- Add / Edit Variant Card -->
        <div class="lg:col-span-1">
            <div class="bg-gaming-850 border border-gaming-border rounded-2xl p-6 space-y-5 sticky top-20 shadow-xl">
                <div class="flex items-center justify-between border-b border-gaming-border pb-3">
                    <h2 class="font-gaming text-lg font-bold text-white flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full <?php echo $editing_var ? 'bg-amber-500' : 'bg-red-500'; ?>"></span>
                        <?php echo $editing_var ? 'Edit Variant' : 'Add New Variant'; ?>
                    </h2>
                    <?php if ($editing_var): ?>
                        <a href="/admin/product-variants.php" class="text-xs text-zinc-400 hover:text-white">Cancel</a>
                    <?php endif; ?>
                </div>

                <form method="POST" action="/admin/product-variants.php" class="space-y-4">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="variant_id" value="<?php echo $editing_var['id'] ?? 0; ?>">

                    <div>
                        <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1">Parent Product *</label>
                        <select name="product_id" required class="w-full px-3.5 py-2.5 rounded-xl bg-gaming-900 border border-gaming-border focus:border-red-500 text-sm text-white focus:outline-none">
                            <option value="">-- Choose Product --</option>
                            <?php foreach ($products_list as $prod): ?>
                                <option value="<?php echo $prod['id']; ?>" <?php echo (($editing_var['product_id'] ?? $filter_product_id) == $prod['id']) ? 'selected' : ''; ?>>
                                    <?php echo e($prod['name']); ?> (Base: <?php echo format_currency($prod['price']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1">Variant Name *</label>
                        <input type="text" name="name" value="<?php echo e($editing_var['name'] ?? ''); ?>" required
                               placeholder="e.g. Single Pack, Double Pack (2x)"
                               class="w-full px-3.5 py-2.5 rounded-xl bg-gaming-900 border border-gaming-border focus:border-red-500 text-sm text-white focus:outline-none">
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1">Price (₹ INR) *</label>
                            <input type="number" step="0.01" min="0.01" name="price" value="<?php echo e($editing_var['price'] ?? ''); ?>" required
                                   placeholder="80.00"
                                   class="w-full px-3.5 py-2.5 rounded-xl bg-gaming-900 border border-gaming-border focus:border-red-500 text-sm text-white focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1">Original Price (₹)</label>
                            <input type="number" step="0.01" min="0" name="original_price" value="<?php echo e($editing_var['original_price'] ?? ''); ?>"
                                   placeholder="100.00"
                                   class="w-full px-3.5 py-2.5 rounded-xl bg-gaming-900 border border-gaming-border focus:border-red-500 text-sm text-white focus:outline-none">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1">Base Diamonds</label>
                            <input type="number" name="diamonds_amount" value="<?php echo e($editing_var['diamonds_amount'] ?? 0); ?>" min="0"
                                   class="w-full px-3.5 py-2.5 rounded-xl bg-gaming-900 border border-gaming-border focus:border-red-500 text-sm text-white focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1">Bonus Diamonds</label>
                            <input type="number" name="bonus_diamonds" value="<?php echo e($editing_var['bonus_diamonds'] ?? 0); ?>" min="0"
                                   class="w-full px-3.5 py-2.5 rounded-xl bg-gaming-900 border border-gaming-border focus:border-red-500 text-sm text-white focus:outline-none">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1">Description / Perks</label>
                        <textarea name="description" rows="2"
                                  placeholder="e.g. Instant delivery to Free Fire UID"
                                  class="w-full px-3.5 py-2.5 rounded-xl bg-gaming-900 border border-gaming-border focus:border-red-500 text-sm text-white focus:outline-none"><?php echo e($editing_var['description'] ?? ''); ?></textarea>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1">Status</label>
                            <select name="status" class="w-full px-3.5 py-2.5 rounded-xl bg-gaming-900 border border-gaming-border focus:border-red-500 text-sm text-white focus:outline-none">
                                <option value="active" <?php echo ($editing_var['status'] ?? 'active') === 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo ($editing_var['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1">Display Order</label>
                            <input type="number" name="sort_order" value="<?php echo e($editing_var['sort_order'] ?? 0); ?>" min="0"
                                   class="w-full px-3.5 py-2.5 rounded-xl bg-gaming-900 border border-gaming-border focus:border-red-500 text-sm text-white focus:outline-none">
                        </div>
                    </div>

                    <button type="submit" class="w-full py-3 rounded-xl bg-red-600 hover:bg-red-500 text-white font-gaming font-bold text-sm uppercase tracking-wider shadow-red-subtle transition-colors">
                        <?php echo $editing_var ? 'Update Variant' : 'Create Variant'; ?>
                    </button>
                </form>
            </div>
        </div>

        <!-- Variants Table Card -->
        <div class="lg:col-span-2 space-y-4">
            <!-- Filter Bar -->
            <div class="bg-gaming-850 border border-gaming-border rounded-xl p-4 flex flex-col sm:flex-row items-center justify-between gap-4">
                <form method="GET" action="/admin/product-variants.php" class="flex items-center gap-3 w-full sm:w-auto">
                    <label class="text-xs font-semibold text-zinc-400">Filter by Product:</label>
                    <select name="product_id" onchange="this.form.submit()" class="px-3 py-1.5 rounded-lg bg-gaming-900 border border-gaming-border text-xs text-white focus:outline-none">
                        <option value="0">-- All Products (<?php echo count($variants); ?>) --</option>
                        <?php foreach ($products_list as $prod): ?>
                            <option value="<?php echo $prod['id']; ?>" <?php echo $filter_product_id == $prod['id'] ? 'selected' : ''; ?>>
                                <?php echo e($prod['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
                <?php if ($filter_product_id > 0): ?>
                    <a href="/admin/product-variants.php" class="text-xs text-red-400 hover:underline">Clear Filter</a>
                <?php endif; ?>
            </div>

            <div class="bg-gaming-850 border border-gaming-border rounded-2xl overflow-hidden shadow-2xl">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="text-zinc-400 bg-gaming-900/60 uppercase border-b border-gaming-border">
                                <th class="py-3 px-4">Product</th>
                                <th class="py-3 px-4">Variant Name</th>
                                <th class="py-3 px-4">Diamonds</th>
                                <th class="py-3 px-4">Price (₹)</th>
                                <th class="py-3 px-4 text-center">Status</th>
                                <th class="py-3 px-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gaming-border/60">
                            <?php if (empty($variants)): ?>
                                <tr>
                                    <td colspan="6" class="py-8 text-center text-zinc-500">No variants found. Add a variant on the left to offer package tiers.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($variants as $v): ?>
                                    <tr class="hover:bg-gaming-800/40 transition-colors">
                                        <td class="py-3 px-4 font-semibold text-zinc-300">
                                            <?php echo e($v['product_name']); ?>
                                        </td>
                                        <td class="py-3 px-4 font-bold text-white">
                                            <?php echo e($v['name']); ?>
                                            <?php if (!empty($v['description'])): ?>
                                                <p class="text-[11px] text-zinc-400 font-normal"><?php echo e($v['description']); ?></p>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-3 px-4">
                                            <div class="font-gaming font-bold text-emerald-400">
                                                <?php echo number_format($v['diamonds_amount']); ?> 💎
                                            </div>
                                            <?php if ($v['bonus_diamonds'] > 0): ?>
                                                <span class="text-[10px] text-amber-400 font-semibold">+<?php echo $v['bonus_diamonds']; ?> Bonus</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-3 px-4">
                                            <div class="font-gaming font-bold text-white text-sm">
                                                <?php echo format_currency($v['price']); ?>
                                            </div>
                                            <?php if ($v['original_price'] > $v['price']): ?>
                                                <div class="text-[10px] text-zinc-500 line-through">
                                                    <?php echo format_currency($v['original_price']); ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-3 px-4 text-center">
                                            <a href="/admin/product-variants.php?action=toggle&id=<?php echo $v['id']; ?><?php echo $filter_product_id ? '&product_id='.$filter_product_id : ''; ?>">
                                                <?php if ($v['status'] === 'active'): ?>
                                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-950 text-emerald-300 border border-emerald-800">Active</span>
                                                <?php else: ?>
                                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-zinc-800 text-zinc-400 border border-zinc-700">Inactive</span>
                                                <?php endif; ?>
                                            </a>
                                        </td>
                                        <td class="py-3 px-4 text-right space-x-2">
                                            <a href="/admin/product-variants.php?edit=<?php echo $v['id']; ?><?php echo $filter_product_id ? '&product_id='.$filter_product_id : ''; ?>" class="px-2.5 py-1 rounded-lg bg-gaming-800 hover:bg-gaming-700 text-zinc-200 border border-gaming-border text-[11px] font-semibold">
                                                Edit
                                            </a>
                                            <a href="/admin/product-variants.php?action=delete&id=<?php echo $v['id']; ?><?php echo $filter_product_id ? '&product_id='.$filter_product_id : ''; ?>" 
                                               onclick="return confirm('Delete this variant package?')"
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
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
