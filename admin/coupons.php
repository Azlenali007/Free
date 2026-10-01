<?php
$page_title = "Manage Coupons & Promo Codes";
require_once __DIR__ . '/includes/admin_header.php';

$errors = [];

// Handle Delete Coupon
if (isset($_GET['action']) && $_GET['action'] === 'delete') {
    $cid = (int)($_GET['id'] ?? 0);
    if ($cid > 0) {
        $stmt_del = $pdo->prepare("DELETE FROM coupons WHERE id = ?");
        $stmt_del->execute([$cid]);
        log_admin_activity('delete_coupon', "Deleted coupon ID: {$cid}");
        set_flash('success', "Coupon deleted.");
        header("Location: /admin/coupons.php");
        exit;
    }
}

// Handle Status Toggle
if (isset($_GET['action']) && $_GET['action'] === 'toggle') {
    $cid = (int)($_GET['id'] ?? 0);
    if ($cid > 0) {
        $stmt_t = $pdo->prepare("UPDATE coupons SET status = IF(status = 'active', 'inactive', 'active') WHERE id = ?");
        $stmt_t->execute([$cid]);
        log_admin_activity('toggle_coupon_status', "Toggled status for coupon ID: {$cid}");
        set_flash('success', "Coupon status updated.");
        header("Location: /admin/coupons.php");
        exit;
    }
}

// Handle Add / Edit Coupon POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validate();

    $cid = (int)($_POST['coupon_id'] ?? 0);
    $code = strtoupper(trim(sanitize($_POST['code'] ?? '')));
    $discount_type = in_array($_POST['discount_type'] ?? '', ['percentage', 'fixed']) ? $_POST['discount_type'] : 'percentage';
    $discount_value = (float)($_POST['discount_value'] ?? 0);
    $min_order_amount = (float)($_POST['min_order_amount'] ?? 0);
    $max_discount = !empty($_POST['max_discount']) ? (float)$_POST['max_discount'] : null;
    $start_date = !empty($_POST['start_date']) ? date('Y-m-d H:i:s', strtotime($_POST['start_date'])) : null;
    $expiry_date = !empty($_POST['expiry_date']) ? date('Y-m-d H:i:s', strtotime($_POST['expiry_date'])) : null;
    $usage_limit = (int)($_POST['usage_limit'] ?? 0);
    $per_user_limit = (int)($_POST['per_user_limit'] ?? 1);
    $status = in_array($_POST['status'] ?? '', ['active', 'inactive']) ? $_POST['status'] : 'active';

    if (empty($code)) {
        $errors[] = "Coupon code is required.";
    }

    if ($discount_value <= 0) {
        $errors[] = "Discount value must be greater than zero.";
    }

    if ($discount_type === 'percentage' && $discount_value > 100) {
        $errors[] = "Percentage discount cannot exceed 100%.";
    }

    // Uniqueness check
    $stmt_chk = $pdo->prepare("SELECT id FROM coupons WHERE code = ? AND id != ?");
    $stmt_chk->execute([$code, $cid]);
    if ($stmt_chk->fetch()) {
        $errors[] = "Coupon code '{$code}' already exists.";
    }

    if (empty($errors)) {
        if ($cid > 0) {
            $stmt_u = $pdo->prepare("
                UPDATE coupons 
                SET code = ?, discount_type = ?, discount_value = ?, min_order_amount = ?, max_discount = ?, start_date = ?, expiry_date = ?, usage_limit = ?, per_user_limit = ?, status = ? 
                WHERE id = ?
            ");
            $stmt_u->execute([$code, $discount_type, $discount_value, $min_order_amount, $max_discount, $start_date, $expiry_date, $usage_limit, $per_user_limit, $status, $cid]);
            log_admin_activity('update_coupon', "Updated coupon '{$code}'");
            set_flash('success', "Coupon '{$code}' updated successfully.");
        } else {
            $stmt_i = $pdo->prepare("
                INSERT INTO coupons 
                (code, discount_type, discount_value, min_order_amount, max_discount, start_date, expiry_date, usage_limit, per_user_limit, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt_i->execute([$code, $discount_type, $discount_value, $min_order_amount, $max_discount, $start_date, $expiry_date, $usage_limit, $per_user_limit, $status]);
            log_admin_activity('create_coupon', "Created coupon '{$code}'");
            set_flash('success', "New coupon '{$code}' created successfully.");
        }
        header("Location: /admin/coupons.php");
        exit;
    }
}

// Search & Filter
$search = sanitize($_GET['q'] ?? '');
$status_filter = sanitize($_GET['status'] ?? '');

$where = ["1=1"];
$params = [];

if (!empty($search)) {
    $where[] = "code LIKE ?";
    $params[] = "%$search%";
}

if (!empty($status_filter)) {
    $where[] = "status = ?";
    $params[] = $status_filter;
}

$stmt_c = $pdo->prepare("SELECT * FROM coupons WHERE " . implode(' AND ', $where) . " ORDER BY id DESC");
$stmt_c->execute($params);
$coupons = $stmt_c->fetchAll();

// Editing Coupon
$editing_coupon = null;
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $stmt_ec = $pdo->prepare("SELECT * FROM coupons WHERE id = ?");
    $stmt_ec->execute([$edit_id]);
    $editing_coupon = $stmt_ec->fetch();
}
?>

<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="font-gaming text-2xl sm:text-3xl font-bold text-white tracking-wide flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-red-500"></span> COUPONS & DISCOUNT MANAGEMENT
            </h1>
            <p class="text-xs text-zinc-400 mt-1">Create percentage and fixed INR promo discounts with real-time limits</p>
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
                        <span class="w-2 h-2 rounded-full <?php echo $editing_coupon ? 'bg-amber-500' : 'bg-red-500'; ?>"></span>
                        <?php echo $editing_coupon ? 'Edit Coupon' : 'Create New Coupon'; ?>
                    </h2>
                    <?php if ($editing_coupon): ?>
                        <a href="/admin/coupons.php" class="text-xs text-zinc-400 hover:text-white">Cancel</a>
                    <?php endif; ?>
                </div>

                <form method="POST" action="/admin/coupons.php" class="space-y-4">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="coupon_id" value="<?php echo $editing_coupon['id'] ?? 0; ?>">

                    <div>
                        <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1">Coupon Code *</label>
                        <input type="text" name="code" value="<?php echo e($editing_coupon['code'] ?? ''); ?>" required
                               placeholder="e.g. FIRE10, DIWALI50"
                               class="w-full px-3.5 py-2.5 rounded-xl bg-gaming-900 border border-gaming-border focus:border-red-500 text-sm font-mono uppercase text-white focus:outline-none">
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1">Discount Type</label>
                            <select name="discount_type" class="w-full px-3.5 py-2.5 rounded-xl bg-gaming-900 border border-gaming-border focus:border-red-500 text-sm text-white focus:outline-none">
                                <option value="percentage" <?php echo ($editing_coupon['discount_type'] ?? 'percentage') === 'percentage' ? 'selected' : ''; ?>>Percentage (%)</option>
                                <option value="fixed" <?php echo ($editing_coupon['discount_type'] ?? '') === 'fixed' ? 'selected' : ''; ?>>Fixed (₹ INR)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1">Discount Value *</label>
                            <input type="number" step="0.01" min="0.01" name="discount_value" 
                                   value="<?php echo e($editing_coupon['discount_value'] ?? ''); ?>" required
                                   placeholder="e.g. 10 or 50.00"
                                   class="w-full px-3.5 py-2.5 rounded-xl bg-gaming-900 border border-gaming-border focus:border-red-500 text-sm text-white focus:outline-none">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1">Min Order (₹)</label>
                            <input type="number" step="0.01" min="0" name="min_order_amount" 
                                   value="<?php echo e($editing_coupon['min_order_amount'] ?? '0.00'); ?>"
                                   placeholder="0.00"
                                   class="w-full px-3.5 py-2.5 rounded-xl bg-gaming-900 border border-gaming-border focus:border-red-500 text-sm text-white focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1">Max Discount (₹)</label>
                            <input type="number" step="0.01" min="0" name="max_discount" 
                                   value="<?php echo e($editing_coupon['max_discount'] ?? ''); ?>"
                                   placeholder="Optional cap"
                                   class="w-full px-3.5 py-2.5 rounded-xl bg-gaming-900 border border-gaming-border focus:border-red-500 text-sm text-white focus:outline-none">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1">Total Usage Limit</label>
                            <input type="number" min="0" name="usage_limit" 
                                   value="<?php echo e($editing_coupon['usage_limit'] ?? 0); ?>"
                                   class="w-full px-3.5 py-2.5 rounded-xl bg-gaming-900 border border-gaming-border focus:border-red-500 text-sm text-white focus:outline-none">
                            <span class="text-[10px] text-zinc-500">0 = Unlimited</span>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1">Per User Limit</label>
                            <input type="number" min="1" name="per_user_limit" 
                                   value="<?php echo e($editing_coupon['per_user_limit'] ?? 1); ?>"
                                   class="w-full px-3.5 py-2.5 rounded-xl bg-gaming-900 border border-gaming-border focus:border-red-500 text-sm text-white focus:outline-none">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1">Start Date</label>
                            <input type="date" name="start_date" 
                                   value="<?php echo !empty($editing_coupon['start_date']) ? date('Y-m-d', strtotime($editing_coupon['start_date'])) : ''; ?>"
                                   class="w-full px-3.5 py-2.5 rounded-xl bg-gaming-900 border border-gaming-border focus:border-red-500 text-xs text-white focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1">Expiry Date</label>
                            <input type="date" name="expiry_date" 
                                   value="<?php echo !empty($editing_coupon['expiry_date']) ? date('Y-m-d', strtotime($editing_coupon['expiry_date'])) : ''; ?>"
                                   class="w-full px-3.5 py-2.5 rounded-xl bg-gaming-900 border border-gaming-border focus:border-red-500 text-xs text-white focus:outline-none">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1">Status</label>
                        <select name="status" class="w-full px-3.5 py-2.5 rounded-xl bg-gaming-900 border border-gaming-border focus:border-red-500 text-sm text-white focus:outline-none">
                            <option value="active" <?php echo ($editing_coupon['status'] ?? 'active') === 'active' ? 'selected' : ''; ?>>Active (Can be redeemed)</option>
                            <option value="inactive" <?php echo ($editing_coupon['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>Inactive (Disabled)</option>
                        </select>
                    </div>

                    <button type="submit" class="w-full py-3 rounded-xl bg-red-600 hover:bg-red-500 text-white font-gaming font-bold text-sm uppercase tracking-wider shadow-red-subtle transition-colors">
                        <?php echo $editing_coupon ? 'Update Coupon' : 'Save Coupon'; ?>
                    </button>
                </form>
            </div>
        </div>

        <!-- Coupons List Table Card -->
        <div class="lg:col-span-2 space-y-4">
            <!-- Search & Filter Bar -->
            <div class="bg-gaming-850 border border-gaming-border rounded-xl p-4 flex flex-col sm:flex-row items-center justify-between gap-4">
                <form method="GET" action="/admin/coupons.php" class="flex flex-wrap items-center gap-3 w-full sm:w-auto flex-1">
                    <input type="text" name="q" value="<?php echo e($search); ?>" placeholder="Search coupon code..."
                           class="px-3.5 py-2 rounded-xl bg-gaming-900 border border-gaming-border text-xs text-white placeholder-zinc-500 focus:outline-none focus:border-red-500 flex-1 sm:w-60">
                    <select name="status" class="px-3 py-2 rounded-xl bg-gaming-900 border border-gaming-border text-xs text-white focus:outline-none">
                        <option value="">All Statuses</option>
                        <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                    <button type="submit" class="px-4 py-2 bg-gaming-800 hover:bg-gaming-750 text-xs font-semibold text-white rounded-xl border border-gaming-border">Filter</button>
                    <?php if (!empty($search) || !empty($status_filter)): ?>
                        <a href="/admin/coupons.php" class="text-xs text-red-400 hover:underline">Clear</a>
                    <?php endif; ?>
                </form>
            </div>

            <div class="bg-gaming-850 border border-gaming-border rounded-2xl overflow-hidden shadow-2xl">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="text-zinc-400 bg-gaming-900/60 uppercase border-b border-gaming-border">
                                <th class="py-3 px-4">Code</th>
                                <th class="py-3 px-4">Discount</th>
                                <th class="py-3 px-4">Conditions</th>
                                <th class="py-3 px-4">Usage</th>
                                <th class="py-3 px-4 text-center">Status</th>
                                <th class="py-3 px-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gaming-border/60">
                            <?php if (empty($coupons)): ?>
                                <tr>
                                    <td colspan="6" class="py-8 text-center text-zinc-500">No coupons found. Create a promo code using the form.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($coupons as $cp): 
                                    $is_expired = !empty($cp['expiry_date']) && strtotime($cp['expiry_date']) < time();
                                ?>
                                    <tr class="hover:bg-gaming-800/40 transition-colors">
                                        <td class="py-3 px-4">
                                            <span class="font-mono font-bold text-white text-sm bg-gaming-900 border border-gaming-border px-2.5 py-1 rounded-lg">
                                                <?php echo e($cp['code']); ?>
                                            </span>
                                        </td>
                                        <td class="py-3 px-4">
                                            <div class="font-gaming font-bold text-emerald-400 text-sm">
                                                <?php echo $cp['discount_type'] === 'percentage' ? $cp['discount_value'] . '%' : format_currency($cp['discount_value']); ?>
                                            </div>
                                            <?php if (!empty($cp['max_discount']) && $cp['max_discount'] > 0): ?>
                                                <div class="text-[10px] text-zinc-400">Max cap: <?php echo format_currency($cp['max_discount']); ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-3 px-4 text-zinc-300">
                                            <div>Min: <?php echo format_currency($cp['min_order_amount']); ?></div>
                                            <?php if (!empty($cp['expiry_date'])): ?>
                                                <div class="text-[10px] <?php echo $is_expired ? 'text-red-400 font-bold' : 'text-zinc-500'; ?>">
                                                    Exp: <?php echo date('M d, Y', strtotime($cp['expiry_date'])); ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-3 px-4">
                                            <span class="font-mono font-semibold text-zinc-200"><?php echo $cp['used_count']; ?></span>
                                            <span class="text-zinc-500 text-[10px]">/ <?php echo $cp['usage_limit'] > 0 ? $cp['usage_limit'] : '∞'; ?></span>
                                        </td>
                                        <td class="py-3 px-4 text-center">
                                            <a href="/admin/coupons.php?action=toggle&id=<?php echo $cp['id']; ?>">
                                                <?php if ($is_expired): ?>
                                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-zinc-800 text-zinc-400 border border-zinc-700">Expired</span>
                                                <?php elseif ($cp['status'] === 'active'): ?>
                                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-950 text-emerald-300 border border-emerald-800">Active</span>
                                                <?php else: ?>
                                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-zinc-800 text-zinc-400 border border-zinc-700">Inactive</span>
                                                <?php endif; ?>
                                            </a>
                                        </td>
                                        <td class="py-3 px-4 text-right space-x-2">
                                            <a href="/admin/coupons.php?edit=<?php echo $cp['id']; ?>" class="px-2.5 py-1 rounded-lg bg-gaming-800 hover:bg-gaming-700 text-zinc-200 border border-gaming-border text-[11px] font-semibold">
                                                Edit
                                            </a>
                                            <a href="/admin/coupons.php?action=delete&id=<?php echo $cp['id']; ?>" 
                                               onclick="return confirm('Delete this coupon?')"
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
