<?php
$page_title = "Slider & Banners Management";
require_once __DIR__ . '/includes/admin_header.php';

$errors = [];

// Handle Delete Banner
if (isset($_GET['action']) && $_GET['action'] === 'delete') {
    $bid = (int)($_GET['id'] ?? 0);
    if ($bid > 0) {
        $stmt_del = $pdo->prepare("DELETE FROM banners WHERE id = ?");
        $stmt_del->execute([$bid]);
        log_admin_activity('delete_banner', "Deleted promotional banner ID: {$bid}");
        set_flash('success', "Banner deleted successfully.");
        header("Location: /admin/banners.php");
        exit;
    }
}

// Handle Status Toggle
if (isset($_GET['action']) && $_GET['action'] === 'toggle') {
    $bid = (int)($_GET['id'] ?? 0);
    if ($bid > 0) {
        $stmt_t = $pdo->prepare("UPDATE banners SET status = IF(status = 'active', 'inactive', 'active') WHERE id = ?");
        $stmt_t->execute([$bid]);
        log_admin_activity('toggle_banner_status', "Toggled status for banner ID: {$bid}");
        set_flash('success', "Banner status updated.");
        header("Location: /admin/banners.php");
        exit;
    }
}

// Handle Add / Edit Banner POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validate();

    $bid = (int)($_POST['banner_id'] ?? 0);
    $title = sanitize($_POST['title'] ?? '');
    $subtitle = sanitize($_POST['subtitle'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $badge = sanitize($_POST['badge'] ?? '');
    $button_text = sanitize($_POST['button_text'] ?? 'Top-Up Now');
    $button_url = sanitize($_POST['button_url'] ?? '/products.php');
    $sort_order = (int)($_POST['sort_order'] ?? 0);
    $status = in_array($_POST['status'] ?? '', ['active', 'inactive']) ? $_POST['status'] : 'active';
    $image_url = sanitize($_POST['image_url'] ?? '');

    // Secure Image Upload Handling
    if (!empty($_FILES['image_file']['name']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['image_file']['tmp_name'];
        $file_name = $_FILES['image_file']['name'];
        $file_size = $_FILES['image_file']['size'];
        $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        $allowed_exts = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        if (!in_array($ext, $allowed_exts)) {
            $errors[] = "Invalid image extension. Allowed: jpg, jpeg, png, webp, gif.";
        } elseif ($file_size > 5 * 1024 * 1024) {
            $errors[] = "Image file is too large (maximum 5MB).";
        } else {
            // Verify image contents
            $img_info = @getimagesize($file_tmp);
            if ($img_info === false) {
                $errors[] = "The uploaded file is not a valid image.";
            } else {
                $upload_dir = __DIR__ . '/../uploads/banners/';
                if (!is_dir($upload_dir)) {
                    @mkdir($upload_dir, 0777, true);
                }
                $new_filename = 'banner_' . time() . '_' . mt_rand(100, 999) . '.' . $ext;
                $dest = $upload_dir . $new_filename;
                if (move_uploaded_file($file_tmp, $dest)) {
                    $image_url = '/uploads/banners/' . $new_filename;
                } else {
                    $errors[] = "Failed to save uploaded image. Check folder permissions.";
                }
            }
        }
    }

    if (empty($title)) {
        $errors[] = "Banner title is required.";
    }

    if (empty($errors)) {
        if ($bid > 0) {
            if (!empty($image_url)) {
                $stmt_u = $pdo->prepare("UPDATE banners SET title = ?, subtitle = ?, description = ?, badge = ?, image = ?, button_text = ?, button_url = ?, sort_order = ?, status = ? WHERE id = ?");
                $stmt_u->execute([$title, $subtitle, $description, $badge, $image_url, $button_text, $button_url, $sort_order, $status, $bid]);
            } else {
                $stmt_u = $pdo->prepare("UPDATE banners SET title = ?, subtitle = ?, description = ?, badge = ?, button_text = ?, button_url = ?, sort_order = ?, status = ? WHERE id = ?");
                $stmt_u->execute([$title, $subtitle, $description, $badge, $button_text, $button_url, $sort_order, $status, $bid]);
            }
            log_admin_activity('update_banner', "Updated banner '{$title}'");
            set_flash('success', "Banner '{$title}' updated successfully.");
        } else {
            if (empty($image_url)) {
                $image_url = 'https://images.unsplash.com/photo-1542751371-adc38448a05e?w=1200&q=80';
            }
            $stmt_i = $pdo->prepare("INSERT INTO banners (title, subtitle, description, badge, image, button_text, button_url, sort_order, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt_i->execute([$title, $subtitle, $description, $badge, $image_url, $button_text, $button_url, $sort_order, $status]);
            log_admin_activity('create_banner', "Created banner '{$title}'");
            set_flash('success', "New banner created successfully.");
        }
        header("Location: /admin/banners.php");
        exit;
    }
}

// Fetch all banners
$banners = $pdo->query("SELECT * FROM banners ORDER BY sort_order ASC, id DESC")->fetchAll();

// Editing banner
$editing_banner = null;
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $stmt_eb = $pdo->prepare("SELECT * FROM banners WHERE id = ?");
    $stmt_eb->execute([$edit_id]);
    $editing_banner = $stmt_eb->fetch();
}
?>

<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="font-gaming text-2xl sm:text-3xl font-bold text-white tracking-wide flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-red-500"></span> HERO SLIDER & PROMOTIONAL BANNERS
            </h1>
            <p class="text-xs text-zinc-400 mt-1">Manage dynamic slideshow banners on the homepage loaded from MySQL</p>
        </div>
        <a href="/index.php" target="_blank" class="px-4 py-2.5 rounded-xl bg-gaming-800 hover:bg-gaming-750 text-xs font-semibold text-zinc-300 border border-gaming-border inline-flex items-center gap-2">
            <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
            <span>View Live Slider</span>
        </a>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="p-4 rounded-xl bg-red-950/70 border border-red-500/60 text-red-200 text-xs space-y-1">
            <?php foreach ($errors as $err): ?>
                <p>• <?php echo e($err); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Add / Edit Banner Card -->
        <div class="lg:col-span-1">
            <div class="bg-gaming-850 border border-gaming-border rounded-2xl p-6 space-y-5 sticky top-20 shadow-xl">
                <div class="flex items-center justify-between border-b border-gaming-border pb-3">
                    <h2 class="font-gaming text-lg font-bold text-white flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full <?php echo $editing_banner ? 'bg-amber-500' : 'bg-red-500'; ?>"></span>
                        <?php echo $editing_banner ? 'Edit Banner' : 'Add New Banner'; ?>
                    </h2>
                    <?php if ($editing_banner): ?>
                        <a href="/admin/banners.php" class="text-xs text-zinc-400 hover:text-white">Cancel</a>
                    <?php endif; ?>
                </div>

                <form method="POST" action="/admin/banners.php" enctype="multipart/form-data" class="space-y-4">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="banner_id" value="<?php echo $editing_banner['id'] ?? 0; ?>">

                    <div>
                        <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1">Headline Title *</label>
                        <input type="text" name="title" value="<?php echo e($editing_banner['title'] ?? ''); ?>" required
                               placeholder="e.g. Free Fire Diamond Top-Up"
                               class="w-full px-3.5 py-2.5 rounded-xl bg-gaming-900 border border-gaming-border focus:border-red-500 text-sm text-white focus:outline-none">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1">Subtitle / Tagline</label>
                        <input type="text" name="subtitle" value="<?php echo e($editing_banner['subtitle'] ?? ''); ?>"
                               placeholder="e.g. Instant Delivery in 0 - 5 Minutes"
                               class="w-full px-3.5 py-2.5 rounded-xl bg-gaming-900 border border-gaming-border focus:border-red-500 text-sm text-white focus:outline-none">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1">Pill Badge Text</label>
                        <input type="text" name="badge" value="<?php echo e($editing_banner['badge'] ?? ''); ?>"
                               placeholder="e.g. HOT DEAL, 10% BONUS"
                               class="w-full px-3.5 py-2.5 rounded-xl bg-gaming-900 border border-gaming-border focus:border-red-500 text-sm text-white focus:outline-none">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1">Description</label>
                        <textarea name="description" rows="2"
                                  placeholder="Short banner copy displayed on slider..."
                                  class="w-full px-3.5 py-2.5 rounded-xl bg-gaming-900 border border-gaming-border focus:border-red-500 text-sm text-white focus:outline-none"><?php echo e($editing_banner['description'] ?? ''); ?></textarea>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1">Image URL</label>
                        <input type="text" name="image_url" value="<?php echo e($editing_banner['image'] ?? ''); ?>"
                               placeholder="https://... or upload below"
                               class="w-full px-3.5 py-2.5 rounded-xl bg-gaming-900 border border-gaming-border focus:border-red-500 text-sm text-white focus:outline-none">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1">Or Upload Image File</label>
                        <input type="file" name="image_file" accept="image/*"
                               class="w-full px-3 py-2 rounded-xl bg-gaming-900 border border-gaming-border text-xs text-zinc-400 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-red-600 file:text-white hover:file:bg-red-500">
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1">Button Text</label>
                            <input type="text" name="button_text" value="<?php echo e($editing_banner['button_text'] ?? 'Top-Up Now'); ?>"
                                   class="w-full px-3.5 py-2.5 rounded-xl bg-gaming-900 border border-gaming-border focus:border-red-500 text-sm text-white focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1">Button URL</label>
                            <input type="text" name="button_url" value="<?php echo e($editing_banner['button_url'] ?? '/products.php'); ?>"
                                   class="w-full px-3.5 py-2.5 rounded-xl bg-gaming-900 border border-gaming-border focus:border-red-500 text-sm text-white focus:outline-none">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1">Sort Order</label>
                            <input type="number" name="sort_order" value="<?php echo e($editing_banner['sort_order'] ?? 0); ?>" min="0"
                                   class="w-full px-3.5 py-2.5 rounded-xl bg-gaming-900 border border-gaming-border focus:border-red-500 text-sm text-white focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1">Status</label>
                            <select name="status" class="w-full px-3.5 py-2.5 rounded-xl bg-gaming-900 border border-gaming-border focus:border-red-500 text-sm text-white focus:outline-none">
                                <option value="active" <?php echo ($editing_banner['status'] ?? 'active') === 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo ($editing_banner['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>
                    </div>

                    <button type="submit" class="w-full py-3 rounded-xl bg-red-600 hover:bg-red-500 text-white font-gaming font-bold text-sm uppercase tracking-wider shadow-red-subtle transition-colors">
                        <?php echo $editing_banner ? 'Update Banner' : 'Create Banner'; ?>
                    </button>
                </form>
            </div>
        </div>

        <!-- Banners List Card -->
        <div class="lg:col-span-2 space-y-4">
            <div class="bg-gaming-850 border border-gaming-border rounded-2xl overflow-hidden shadow-2xl">
                <div class="p-5 border-b border-gaming-border flex items-center justify-between">
                    <h2 class="font-gaming text-lg font-bold text-white">Active Homepage Banners (<?php echo count($banners); ?>)</h2>
                </div>

                <div class="divide-y divide-gaming-border/60">
                    <?php if (empty($banners)): ?>
                        <div class="p-8 text-center text-zinc-500 text-xs">No banners created yet. Add one using the form on the left.</div>
                    <?php else: ?>
                        <?php foreach ($banners as $b): ?>
                            <div class="p-4 sm:p-5 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 hover:bg-gaming-800/30 transition-colors">
                                <div class="flex items-center gap-4">
                                    <div class="w-20 h-14 rounded-xl overflow-hidden bg-gaming-900 border border-gaming-border shrink-0">
                                        <img src="<?php echo e($b['image']); ?>" alt="Banner" class="w-full h-full object-cover" onerror="this.src='/assets/images/banner-placeholder.jpg'">
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <h3 class="font-bold text-white text-sm"><?php echo e($b['title']); ?></h3>
                                            <?php if (!empty($b['badge'])): ?>
                                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-red-600/30 text-red-300 border border-red-500/40 uppercase">
                                                    <?php echo e($b['badge']); ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <p class="text-xs text-zinc-400 mt-0.5"><?php echo e($b['subtitle']); ?></p>
                                        <div class="flex items-center gap-3 text-[11px] text-zinc-500 mt-1">
                                            <span>Order: #<?php echo $b['sort_order']; ?></span>
                                            <span>&bull;</span>
                                            <span>Btn: <?php echo e($b['button_text']); ?> &rarr; <?php echo e($b['button_url']); ?></span>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex items-center gap-2 self-end sm:self-center">
                                    <a href="/admin/banners.php?action=toggle&id=<?php echo $b['id']; ?>">
                                        <?php if ($b['status'] === 'active'): ?>
                                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-950 text-emerald-300 border border-emerald-800">Active</span>
                                        <?php else: ?>
                                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-zinc-800 text-zinc-400 border border-zinc-700">Inactive</span>
                                        <?php endif; ?>
                                    </a>
                                    <a href="/admin/banners.php?edit=<?php echo $b['id']; ?>" class="px-3 py-1.5 rounded-lg bg-gaming-800 hover:bg-gaming-750 text-zinc-200 border border-gaming-border text-xs font-semibold">
                                        Edit
                                    </a>
                                    <a href="/admin/banners.php?action=delete&id=<?php echo $b['id']; ?>" 
                                       onclick="return confirm('Delete this banner?')"
                                       class="px-3 py-1.5 rounded-lg bg-red-950/60 hover:bg-red-900 text-red-300 border border-red-800/40 text-xs font-semibold">
                                        Delete
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
