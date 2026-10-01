<?php
$page_title = "Manage Products";
require_once __DIR__ . '/includes/admin_header.php';

// Handle single delete
if (isset($_GET['action']) && $_GET['action'] === 'delete') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id) {
        $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
        $stmt->execute([$id]);
        log_admin_activity('delete_product', "Deleted product ID: {$id}");
        set_flash('success', "Product #$id deleted successfully.");
        header("Location: /admin/products.php");
        exit;
    }
}

// Handle single status toggle
if (isset($_GET['action']) && $_GET['action'] === 'toggle') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id) {
        $stmt = $pdo->prepare("UPDATE products SET status = IF(status = 'active', 'inactive', 'active') WHERE id = ?");
        $stmt->execute([$id]);
        log_admin_activity('toggle_product_status', "Toggled status for product ID: {$id}");
        set_flash('success', "Product status toggled.");
        header("Location: /admin/products.php");
        exit;
    }
}

// Handle Bulk Product Actions POST (Requirement 20)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_action'])) {
    csrf_validate();
    $action = sanitize($_POST['bulk_action'] ?? '');
    $selected_ids = array_filter(array_map('intval', $_POST['product_ids'] ?? []));

    if (empty($selected_ids)) {
        set_flash('error', "No products selected for bulk action.");
    } else {
        $in_clause = implode(',', $selected_ids);

        if ($action === 'enable') {
            $pdo->query("UPDATE products SET status = 'active' WHERE id IN ($in_clause)");
            log_admin_activity('bulk_enable_products', "Enabled products: " . implode(',', $selected_ids));
            set_flash('success', count($selected_ids) . " product(s) marked as Active.");
        } elseif ($action === 'disable') {
            $pdo->query("UPDATE products SET status = 'inactive' WHERE id IN ($in_clause)");
            log_admin_activity('bulk_disable_products', "Disabled products: " . implode(',', $selected_ids));
            set_flash('success', count($selected_ids) . " product(s) marked as Inactive.");
        } elseif ($action === 'change_category') {
            $new_cat_id = (int)($_POST['bulk_category_id'] ?? 0);
            if ($new_cat_id > 0) {
                $stmt_up_cat = $pdo->prepare("UPDATE products SET category_id = ? WHERE id IN ($in_clause)");
                $stmt_up_cat->execute([$new_cat_id]);
                log_admin_activity('bulk_change_category', "Changed category for products " . implode(',', $selected_ids) . " to {$new_cat_id}");
                set_flash('success', count($selected_ids) . " product(s) category updated.");
            } else {
                set_flash('error', "Please select a valid target category.");
            }
        } elseif ($action === 'delete') {
            // Delete safely
            $pdo->query("DELETE FROM products WHERE id IN ($in_clause)");
            log_admin_activity('bulk_delete_products', "Deleted products: " . implode(',', $selected_ids));
            set_flash('success', count($selected_ids) . " product(s) permanently removed.");
        }
    }
    header("Location: /admin/products.php");
    exit;
}

$category_filter = sanitize($_GET['category'] ?? '');
$search = sanitize($_GET['q'] ?? '');

$where = ["1=1"];
$params = [];

if (!empty($category_filter)) {
    $where[] = "c.slug = ?";
    $params[] = $category_filter;
}
if (!empty($search)) {
    $where[] = "(p.name LIKE ? OR p.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sql = "
    SELECT p.*, c.name as category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    WHERE " . implode(' AND ', $where) . " 
    ORDER BY p.id DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

$categories = $pdo->query("SELECT * FROM categories ORDER BY sort_order ASC")->fetchAll();
?>

<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="font-gaming text-2xl sm:text-3xl font-bold text-white tracking-wide flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-red-500"></span> PRODUCT & PACK MANAGEMENT
            </h1>
            <p class="text-xs text-zinc-400 mt-1">Add, edit, bulk manage, and configure Free Fire diamond packages in ₹ INR</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="/admin/product-variants.php" class="px-4 py-2.5 rounded-xl bg-gaming-800 hover:bg-gaming-750 text-xs font-semibold text-zinc-300 border border-gaming-border">
                Manage Package Variants &rarr;
            </a>
            <a href="/admin/product-add.php" class="btn-gaming-red text-white text-xs font-gaming font-bold px-5 py-2.5 rounded-xl shadow-red-subtle flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span>+ ADD NEW PRODUCT</span>
            </a>
        </div>
    </div>

    <!-- Search & Filter Bar -->
    <div class="bg-gaming-850 border border-gaming-border rounded-xl p-4 flex flex-col sm:flex-row items-center justify-between gap-4">
        <form method="GET" action="/admin/products.php" class="flex flex-wrap items-center gap-3 w-full sm:w-auto flex-1">
            <input type="text" name="q" value="<?php echo e($search); ?>" placeholder="Search product name..."
                   class="px-3.5 py-2 rounded-lg bg-gaming-900 border border-gaming-border text-xs text-white focus:outline-none w-full sm:w-64">
            
            <select name="category" onchange="this.form.submit()"
                    class="px-3 py-2 rounded-lg bg-gaming-900 border border-gaming-border text-xs text-white focus:outline-none">
                <option value="">All Categories</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo e($cat['slug']); ?>" <?php echo $category_filter === $cat['slug'] ? 'selected' : ''; ?>>
                        <?php echo e($cat['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <button type="submit" class="px-4 py-2 rounded-lg bg-gaming-800 hover:bg-gaming-750 text-xs font-semibold text-zinc-200 border border-gaming-border">
                Filter
            </button>
            <?php if (!empty($search) || !empty($category_filter)): ?>
                <a href="/admin/products.php" class="text-xs text-red-400 hover:underline">Reset</a>
            <?php endif; ?>
        </form>

        <span class="text-xs text-zinc-400 font-mono"><?php echo count($products); ?> Products Listed</span>
    </div>

    <!-- Bulk Action Form Container -->
    <form method="POST" action="/admin/products.php" id="bulkProductForm">
        <?php echo csrf_field(); ?>

        <!-- Bulk Action Toolbar -->
        <div class="bg-gaming-900 border border-gaming-border rounded-xl p-3.5 mb-4 flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <label class="flex items-center gap-2 cursor-pointer text-xs font-semibold text-zinc-300">
                    <input type="checkbox" id="selectAllCheckbox" onchange="toggleSelectAll(this)" class="w-4 h-4 rounded text-red-600 bg-gaming-950 border-gaming-border">
                    <span>Select All</span>
                </label>
                <span id="selectedCountText" class="text-[11px] text-zinc-500 font-mono">0 selected</span>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <select name="bulk_action" id="bulkActionSelect" onchange="handleBulkActionChange(this.value)" class="px-3 py-1.5 rounded-lg bg-gaming-950 border border-gaming-border text-xs text-white focus:outline-none">
                    <option value="">-- Bulk Action --</option>
                    <option value="enable">Bulk Enable</option>
                    <option value="disable">Bulk Disable</option>
                    <option value="change_category">Bulk Change Category</option>
                    <option value="delete">Bulk Delete (Destructive)</option>
                </select>

                <select name="bulk_category_id" id="bulkCategorySelect" class="hidden px-3 py-1.5 rounded-lg bg-gaming-950 border border-gaming-border text-xs text-white focus:outline-none">
                    <option value="">-- Choose Category --</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>"><?php echo e($cat['name']); ?></option>
                    <?php endforeach; ?>
                </select>

                <button type="submit" onclick="return confirmBulkAction()" class="px-3.5 py-1.5 rounded-lg bg-red-600 hover:bg-red-500 text-white font-bold text-xs shadow-red-subtle transition-colors">
                    Apply to Selected
                </button>
            </div>
        </div>

        <!-- Products Table -->
        <div class="bg-gaming-850 border border-gaming-border rounded-2xl overflow-hidden shadow-2xl">
            <?php if (!empty($products)): ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-gaming-900 border-b border-gaming-border text-zinc-400 uppercase font-gaming">
                                <th class="py-3.5 px-3 w-8"></th>
                                <th class="py-3.5 px-4">Item</th>
                                <th class="py-3.5 px-4">Category</th>
                                <th class="py-3.5 px-4">Price (₹ INR)</th>
                                <th class="py-3.5 px-4">Diamonds / Bonus</th>
                                <th class="py-3.5 px-4">Status</th>
                                <th class="py-3.5 px-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gaming-border/60">
                            <?php foreach ($products as $prod): 
                                $is_active = $prod['status'] === 'active';
                            ?>
                                <tr class="hover:bg-gaming-800/40 transition-colors">
                                    <td class="py-3.5 px-3">
                                        <input type="checkbox" name="product_ids[]" value="<?php echo $prod['id']; ?>" class="prod-checkbox w-4 h-4 rounded text-red-600 bg-gaming-950 border-gaming-border" onchange="updateSelectedCount()">
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-lg bg-gaming-950 border border-gaming-border p-1 flex items-center justify-center shrink-0">
                                                <img src="<?php echo e($prod['image'] ?: '/assets/images/diamonds.svg'); ?>" alt="" class="max-h-8 max-w-full">
                                            </div>
                                            <div>
                                                <span class="font-gaming font-bold text-sm text-white block"><?php echo e($prod['name']); ?></span>
                                                <div class="flex items-center gap-2">
                                                    <span class="text-[10px] text-zinc-500 font-mono">ID: #<?php echo $prod['id']; ?></span>
                                                    <?php if (!empty($prod['flash_sale_enabled'])): ?>
                                                        <span class="text-[10px] text-amber-400 font-bold">⚡ Flash Sale: <?php echo format_currency($prod['flash_sale_price']); ?></span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-3.5 px-4 text-zinc-300 font-medium"><?php echo e($prod['category_name']); ?></td>
                                    <td class="py-3.5 px-4 font-gaming font-bold text-white text-sm">
                                        <?php echo format_currency($prod['price']); ?>
                                        <?php if (!empty($prod['original_price']) && $prod['original_price'] > $prod['price']): ?>
                                            <span class="text-[10px] text-zinc-500 line-through block font-normal"><?php echo format_currency($prod['original_price']); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-3.5 px-4 text-zinc-300">
                                        <?php if ($prod['diamonds_amount'] > 0): ?>
                                            <span class="font-bold text-white"><?php echo $prod['diamonds_amount']; ?></span>
                                            <?php if ($prod['bonus_diamonds'] > 0): ?>
                                                <span class="text-emerald-400 font-semibold text-[11px]">(+<?php echo $prod['bonus_diamonds']; ?>)</span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-zinc-500">Special Item</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <a href="/admin/products.php?action=toggle&id=<?php echo $prod['id']; ?>" 
                                           title="Click to toggle status"
                                           class="inline-block px-2.5 py-0.5 rounded text-[10px] font-bold uppercase border <?php echo $is_active ? 'bg-emerald-950 text-emerald-400 border-emerald-800' : 'bg-zinc-800 text-zinc-400 border-zinc-700'; ?>">
                                            <?php echo $is_active ? 'Active' : 'Disabled'; ?>
                                        </a>
                                    </td>
                                    <td class="py-3.5 px-4 text-right space-x-2">
                                        <a href="/admin/product-variants.php?product_id=<?php echo $prod['id']; ?>" 
                                           title="Manage Packages & Variants"
                                           class="px-2 py-1 rounded bg-gaming-900 hover:bg-gaming-800 text-amber-300 font-semibold border border-gaming-border text-[11px]">
                                            Variants
                                        </a>
                                        <a href="/admin/product-edit.php?id=<?php echo $prod['id']; ?>" 
                                           class="px-2.5 py-1 rounded bg-gaming-800 hover:bg-gaming-750 text-white font-semibold border border-gaming-border">
                                            Edit
                                        </a>
                                        <a href="/admin/products.php?action=delete&id=<?php echo $prod['id']; ?>" 
                                           onclick="return confirm('Are you sure you want to permanently delete this product?');"
                                           class="px-2.5 py-1 rounded bg-red-950/80 hover:bg-red-900 text-red-300 font-semibold border border-red-800/40">
                                            Delete
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-12 text-zinc-500 text-xs">
                    No products found. Click "+ Add New Product" to create one.
                </div>
            <?php endif; ?>
        </div>
    </form>
</div>

<script>
function toggleSelectAll(master) {
    const checkboxes = document.querySelectorAll('.prod-checkbox');
    checkboxes.forEach(cb => cb.checked = master.checked);
    updateSelectedCount();
}

function updateSelectedCount() {
    const checked = document.querySelectorAll('.prod-checkbox:checked').length;
    document.getElementById('selectedCountText').innerText = checked + ' selected';
}

function handleBulkActionChange(val) {
    const catSelect = document.getElementById('bulkCategorySelect');
    if (val === 'change_category') {
        catSelect.classList.remove('hidden');
    } else {
        catSelect.classList.add('hidden');
    }
}

function confirmBulkAction() {
    const action = document.getElementById('bulkActionSelect').value;
    const checked = document.querySelectorAll('.prod-checkbox:checked').length;
    if (checked === 0) {
        alert('Please select at least one product using the checkboxes.');
        return false;
    }
    if (!action) {
        alert('Please select a bulk action from the dropdown.');
        return false;
    }
    if (action === 'delete') {
        return confirm('Are you sure you want to permanently DELETE ' + checked + ' product(s)? This action cannot be undone.');
    }
    return true;
}
</script>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
