<?php
$page_title = "Manage Categories";
require_once __DIR__ . '/includes/admin_header.php';

$errors = [];
$success_msg = '';

// Handle Delete Category
if (isset($_GET['action']) && $_GET['action'] === 'delete') {
    $cat_id = (int)($_GET['id'] ?? 0);
    if ($cat_id > 0) {
        // Safety check: verify no products are assigned to this category
        $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
        $stmt_check->execute([$cat_id]);
        $prod_count = (int)$stmt_check->fetchColumn();

        if ($prod_count > 0) {
            set_flash('error', "Cannot delete category: {$prod_count} product(s) are currently assigned to it. Please reassign or delete the products first.");
        } else {
            $stmt_del = $pdo->prepare("DELETE FROM categories WHERE id = ?");
            $stmt_del->execute([$cat_id]);
            log_admin_activity('delete_category', "Deleted category ID: {$cat_id}");
            set_flash('success', "Category successfully deleted.");
        }
        header("Location: /admin/categories.php");
        exit;
    }
}

// Handle Status Toggle
if (isset($_GET['action']) && $_GET['action'] === 'toggle') {
    $cat_id = (int)($_GET['id'] ?? 0);
    if ($cat_id > 0) {
        $stmt_toggle = $pdo->prepare("UPDATE categories SET status = IF(status = 'active', 'inactive', 'active') WHERE id = ?");
        $stmt_toggle->execute([$cat_id]);
        log_admin_activity('toggle_category_status', "Toggled status for category ID: {$cat_id}");
        set_flash('success', "Category status updated.");
        header("Location: /admin/categories.php");
        exit;
    }
}

// Handle Add / Edit Category POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validate();

    $cat_id = (int)($_POST['category_id'] ?? 0);
    $name = sanitize($_POST['name'] ?? '');
    $slug = sanitize($_POST['slug'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $icon = sanitize($_POST['icon'] ?? '');
    $sort_order = (int)($_POST['sort_order'] ?? 0);
    $status = in_array($_POST['status'] ?? '', ['active', 'inactive']) ? $_POST['status'] : 'active';

    if (empty($name)) {
        $errors[] = "Category name is required.";
    }

    if (empty($slug)) {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
    }

    // Check slug uniqueness
    $stmt_slug = $pdo->prepare("SELECT id FROM categories WHERE slug = ? AND id != ?");
    $stmt_slug->execute([$slug, $cat_id]);
    if ($stmt_slug->fetch()) {
        $slug .= '-' . mt_rand(10, 99);
    }

    if (empty($errors)) {
        if ($cat_id > 0) {
            // Update
            $stmt_update = $pdo->prepare("UPDATE categories SET name = ?, slug = ?, description = ?, icon = ?, sort_order = ?, status = ? WHERE id = ?");
            $stmt_update->execute([$name, $slug, $description, $icon, $sort_order, $status, $cat_id]);
            log_admin_activity('update_category', "Updated category: {$name} (ID: {$cat_id})");
            set_flash('success', "Category '{$name}' updated successfully.");
        } else {
            // Insert
            $stmt_insert = $pdo->prepare("INSERT INTO categories (name, slug, description, icon, sort_order, status) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt_insert->execute([$name, $slug, $description, $icon, $sort_order, $status]);
            $new_id = $pdo->lastInsertId();
            log_admin_activity('create_category', "Created category: {$name} (ID: {$new_id})");
            set_flash('success', "New category '{$name}' created successfully.");
        }
        header("Location: /admin/categories.php");
        exit;
    }
}

// Check if editing a specific category
$editing_cat = null;
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $stmt_get = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt_get->execute([$edit_id]);
    $editing_cat = $stmt_get->fetch();
}

// Fetch all categories with product counts
$categories = $pdo->query("
    SELECT c.*, COUNT(p.id) as product_count 
    FROM categories c 
    LEFT JOIN products p ON c.id = p.category_id 
    GROUP BY c.id 
    ORDER BY c.sort_order ASC, c.id ASC
")->fetchAll();
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="font-gaming text-2xl sm:text-3xl font-bold text-white tracking-wide flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-red-500"></span> CATEGORY MANAGEMENT
            </h1>
            <p class="text-xs text-zinc-400 mt-1">Organize diamonds, passes, memberships, and weapon bundles</p>
        </div>
        <div>
            <a href="/admin/products.php" class="px-4 py-2.5 rounded-xl bg-gaming-800 hover:bg-gaming-750 text-xs font-semibold text-zinc-300 border border-gaming-border inline-flex items-center gap-2">
                <svg class="w-4 h-4 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                <span>View Products Catalog</span>
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
        <!-- Add / Edit Form Card -->
        <div class="lg:col-span-1">
            <div class="bg-gaming-850 border border-gaming-border rounded-2xl p-6 space-y-5 sticky top-20 shadow-xl">
                <div class="flex items-center justify-between border-b border-gaming-border pb-3">
                    <h2 class="font-gaming text-lg font-bold text-white flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full <?php echo $editing_cat ? 'bg-amber-500' : 'bg-red-500'; ?>"></span>
                        <?php echo $editing_cat ? 'Edit Category' : 'Add New Category'; ?>
                    </h2>
                    <?php if ($editing_cat): ?>
                        <a href="/admin/categories.php" class="text-xs text-zinc-400 hover:text-white">Cancel</a>
                    <?php endif; ?>
                </div>

                <form method="POST" action="/admin/categories.php" class="space-y-4">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="category_id" value="<?php echo $editing_cat['id'] ?? 0; ?>">

                    <div>
                        <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1">Category Name *</label>
                        <input type="text" name="name" value="<?php echo e($editing_cat['name'] ?? ''); ?>" required
                               placeholder="e.g. Diamond Top-Up, Passes"
                               class="w-full px-3.5 py-2.5 rounded-xl bg-gaming-900 border border-gaming-border focus:border-red-500 text-sm text-white focus:outline-none">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1">Slug (URL)</label>
                        <input type="text" name="slug" value="<?php echo e($editing_cat['slug'] ?? ''); ?>"
                               placeholder="Leave blank to auto-generate"
                               class="w-full px-3.5 py-2.5 rounded-xl bg-gaming-900 border border-gaming-border focus:border-red-500 text-sm text-white focus:outline-none">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1">Description</label>
                        <textarea name="description" rows="3"
                                  placeholder="Short description for gamers..."
                                  class="w-full px-3.5 py-2.5 rounded-xl bg-gaming-900 border border-gaming-border focus:border-red-500 text-sm text-white focus:outline-none"><?php echo e($editing_cat['description'] ?? ''); ?></textarea>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1">Icon / Emoji</label>
                            <input type="text" name="icon" value="<?php echo e($editing_cat['icon'] ?? ''); ?>"
                                   placeholder="💎 or fire"
                                   class="w-full px-3.5 py-2.5 rounded-xl bg-gaming-900 border border-gaming-border focus:border-red-500 text-sm text-white focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1">Sort Order</label>
                            <input type="number" name="sort_order" value="<?php echo e($editing_cat['sort_order'] ?? 0); ?>" min="0"
                                   class="w-full px-3.5 py-2.5 rounded-xl bg-gaming-900 border border-gaming-border focus:border-red-500 text-sm text-white focus:outline-none">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1">Status</label>
                        <select name="status" class="w-full px-3.5 py-2.5 rounded-xl bg-gaming-900 border border-gaming-border focus:border-red-500 text-sm text-white focus:outline-none">
                            <option value="active" <?php echo ($editing_cat['status'] ?? 'active') === 'active' ? 'selected' : ''; ?>>Active (Visible)</option>
                            <option value="inactive" <?php echo ($editing_cat['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>Inactive (Hidden)</option>
                        </select>
                    </div>

                    <button type="submit" class="w-full py-3 rounded-xl bg-red-600 hover:bg-red-500 text-white font-gaming font-bold text-sm uppercase tracking-wider shadow-red-subtle transition-colors">
                        <?php echo $editing_cat ? 'Update Category' : 'Create Category'; ?>
                    </button>
                </form>
            </div>
        </div>

        <!-- Categories Table Card -->
        <div class="lg:col-span-2">
            <div class="bg-gaming-850 border border-gaming-border rounded-2xl overflow-hidden shadow-2xl">
                <div class="p-5 border-b border-gaming-border flex items-center justify-between">
                    <h2 class="font-gaming text-lg font-bold text-white">All Categories (<?php echo count($categories); ?>)</h2>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="text-zinc-400 bg-gaming-900/60 uppercase border-b border-gaming-border">
                                <th class="py-3 px-4">Order</th>
                                <th class="py-3 px-4">Category Name</th>
                                <th class="py-3 px-4">Slug</th>
                                <th class="py-3 px-4 text-center">Products</th>
                                <th class="py-3 px-4 text-center">Status</th>
                                <th class="py-3 px-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gaming-border/60">
                            <?php if (empty($categories)): ?>
                                <tr>
                                    <td colspan="6" class="py-8 text-center text-zinc-500">No categories found. Create one using the form on the left.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($categories as $c): ?>
                                    <tr class="hover:bg-gaming-800/40 transition-colors">
                                        <td class="py-3 px-4 font-mono text-zinc-400"><?php echo e($c['sort_order']); ?></td>
                                        <td class="py-3 px-4 font-bold text-white">
                                            <div class="flex items-center gap-2">
                                                <?php if (!empty($c['icon'])): ?>
                                                    <span class="text-base"><?php echo e($c['icon']); ?></span>
                                                <?php endif; ?>
                                                <span><?php echo e($c['name']); ?></span>
                                            </div>
                                            <?php if (!empty($c['description'])): ?>
                                                <p class="text-[11px] text-zinc-400 font-normal truncate max-w-xs"><?php echo e($c['description']); ?></p>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-3 px-4 font-mono text-zinc-400 text-[11px]"><?php echo e($c['slug']); ?></td>
                                        <td class="py-3 px-4 text-center">
                                            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-gaming-800 border border-gaming-border text-zinc-300">
                                                <?php echo $c['product_count']; ?> products
                                            </span>
                                        </td>
                                        <td class="py-3 px-4 text-center">
                                            <a href="/admin/categories.php?action=toggle&id=<?php echo $c['id']; ?>" title="Click to toggle status">
                                                <?php if ($c['status'] === 'active'): ?>
                                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-950 text-emerald-300 border border-emerald-800">Active</span>
                                                <?php else: ?>
                                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-zinc-800 text-zinc-400 border border-zinc-700">Inactive</span>
                                                <?php endif; ?>
                                            </a>
                                        </td>
                                        <td class="py-3 px-4 text-right space-x-2">
                                            <a href="/admin/categories.php?edit=<?php echo $c['id']; ?>" class="px-2.5 py-1 rounded-lg bg-gaming-800 hover:bg-gaming-700 text-zinc-200 border border-gaming-border text-[11px] font-semibold">
                                                Edit
                                            </a>
                                            <a href="/admin/categories.php?action=delete&id=<?php echo $c['id']; ?>" 
                                               onclick="return confirm('Delete this category? (Only allowed if no products are assigned)')"
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
