<?php
$page_title = "Add New Product";
require_once __DIR__ . '/includes/admin_header.php';

$errors = [];
$categories = $pdo->query("SELECT * FROM categories ORDER BY sort_order ASC")->fetchAll();

$name = '';
$category_id = '';
$price = '';
$original_price = '';
$diamonds = 0;
$bonus = 0;
$badge = '';
$description = '';
$status = 'active';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validate();

    $name = sanitize($_POST['name'] ?? '');
    $category_id = (int)($_POST['category_id'] ?? 0);
    $price = (float)($_POST['price'] ?? 0);
    $original_price = !empty($_POST['original_price']) ? (float)$_POST['original_price'] : null;
    $diamonds = (int)($_POST['diamonds_amount'] ?? 0);
    $bonus = (int)($_POST['bonus_diamonds'] ?? 0);
    $badge = sanitize($_POST['badge'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $status = in_array($_POST['status'] ?? '', ['active', 'inactive']) ? $_POST['status'] : 'active';
    $image_path = '/assets/images/diamonds.svg'; // Default

    if (empty($name)) {
        $errors[] = "Product Name is required.";
    }
    if (!$category_id) {
        $errors[] = "Please select a Category.";
    }
    if ($price <= 0) {
        $errors[] = "Please enter a valid price greater than 0.";
    }

    // Handle File Upload if provided
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['product_image'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed_exts = ['jpg', 'jpeg', 'png', 'webp', 'svg'];
        
        if (!in_array($ext, $allowed_exts)) {
            $errors[] = "Invalid image type. Allowed: " . implode(', ', $allowed_exts);
        } elseif ($file['size'] > 3 * 1024 * 1024) {
            $errors[] = "Image size exceeds 3MB limit.";
        } else {
            $target_dir = __DIR__ . '/../../uploads/products/';
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            $filename = 'prod_' . uniqid() . '.' . $ext;
            if (move_uploaded_file($file['tmp_name'], $target_dir . $filename)) {
                $image_path = '/uploads/products/' . $filename;
            } else {
                $errors[] = "Failed to upload image.";
            }
        }
    } elseif (!empty($_POST['image_url'])) {
        $image_path = sanitize($_POST['image_url']);
    }

    if (empty($errors)) {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name), '-')) . '-' . mt_rand(10, 99);

        $stmt = $pdo->prepare("
            INSERT INTO products 
            (category_id, name, slug, description, image, price, original_price, diamonds_amount, bonus_diamonds, badge, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $category_id,
            $name,
            $slug,
            $description,
            $image_path,
            $price,
            $original_price,
            $diamonds,
            $bonus,
            $badge,
            $status
        ]);

        set_flash('success', "Product '$name' added successfully!");
        header("Location: /admin/products.php");
        exit;
    }
}
?>

<div class="max-w-3xl mx-auto space-y-6">
    <div class="flex items-center justify-between pb-4 border-b border-gaming-border">
        <div>
            <h1 class="font-gaming text-2xl font-bold text-white tracking-wide">ADD NEW PRODUCT / PACK</h1>
            <p class="text-xs text-zinc-400 mt-1">Configure diamond amount, pricing, and category</p>
        </div>
        <a href="/admin/products.php" class="text-xs font-semibold text-zinc-400 hover:text-white">
            &larr; Back to Products
        </a>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="p-4 rounded-xl bg-red-950/70 border border-red-500/60 text-red-200 text-xs space-y-1">
            <?php foreach ($errors as $err): ?>
                <p>• <?php echo e($err); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="bg-gaming-850 border border-gaming-border rounded-2xl p-6 sm:p-8 shadow-2xl">
        <form method="POST" action="/admin/product-add.php" enctype="multipart/form-data" class="space-y-5">
            <?php echo csrf_field(); ?>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1">Product Name *</label>
                    <input type="text" name="name" value="<?php echo e($name); ?>" required
                           class="w-full px-3.5 py-2.5 rounded-xl bg-gaming-900 border border-gaming-border focus:border-red-500 text-sm text-white focus:outline-none"
                           placeholder="e.g. 520 + 52 Diamonds Pack">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1">Category *</label>
                    <select name="category_id" required
                            class="w-full px-3.5 py-2.5 rounded-xl bg-gaming-900 border border-gaming-border focus:border-red-500 text-sm text-white focus:outline-none">
                        <option value="">Select Category</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" <?php echo ($category_id == $cat['id']) ? 'selected' : ''; ?>>
                                <?php echo e($cat['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1">Badge (Optional)</label>
                    <input type="text" name="badge" value="<?php echo e($badge); ?>"
                           class="w-full px-3.5 py-2.5 rounded-xl bg-gaming-900 border border-gaming-border focus:border-red-500 text-sm text-white focus:outline-none"
                           placeholder="e.g. Popular, Best Value, Hot">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1">Selling Price (₹ INR) *</label>
                    <input type="number" step="0.01" min="0.1" name="price" value="<?php echo e($price); ?>" required
                           class="w-full px-3.5 py-2.5 rounded-xl bg-gaming-900 border border-gaming-border focus:border-red-500 text-sm text-white focus:outline-none"
                           placeholder="80.00">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1">Original / Strikethrough Price (₹ INR)</label>
                    <input type="number" step="0.01" min="0" name="original_price" value="<?php echo e($original_price); ?>"
                           class="w-full px-3.5 py-2.5 rounded-xl bg-gaming-900 border border-gaming-border focus:border-red-500 text-sm text-white focus:outline-none"
                           placeholder="100.00">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1">Base Diamonds Amount</label>
                    <input type="number" name="diamonds_amount" value="<?php echo e($diamonds); ?>" min="0"
                           class="w-full px-3.5 py-2.5 rounded-xl bg-gaming-900 border border-gaming-border focus:border-red-500 text-sm text-white focus:outline-none"
                           placeholder="520">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1">Bonus Diamonds Amount</label>
                    <input type="number" name="bonus_diamonds" value="<?php echo e($bonus); ?>" min="0"
                           class="w-full px-3.5 py-2.5 rounded-xl bg-gaming-900 border border-gaming-border focus:border-red-500 text-sm text-white focus:outline-none"
                           placeholder="52">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1">Product Description</label>
                <textarea name="description" rows="3"
                          class="w-full px-3.5 py-2.5 rounded-xl bg-gaming-900 border border-gaming-border focus:border-red-500 text-sm text-white focus:outline-none"
                          placeholder="Brief package details or instructions..."><?php echo e($description); ?></textarea>
            </div>

            <!-- Image Upload or Preset -->
            <div class="p-4 rounded-xl bg-gaming-900 border border-gaming-border space-y-3">
                <span class="text-xs font-semibold text-zinc-300 uppercase tracking-wider block">Product Artwork / Image</span>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[11px] text-zinc-400 mb-1">Upload Image (JPG, PNG, WebP, SVG)</label>
                        <input type="file" name="product_image" accept="image/*"
                               class="w-full text-xs text-zinc-400 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-gaming-800 file:text-white hover:file:bg-gaming-750">
                    </div>
                    <div>
                        <label class="block text-[11px] text-zinc-400 mb-1">Or Default Asset / Image URL</label>
                        <select name="image_url" class="w-full px-3 py-2 rounded-lg bg-gaming-950 border border-gaming-border text-xs text-white">
                            <option value="/assets/images/diamonds.svg">Default Diamonds Artwork</option>
                            <option value="/assets/images/pass-weekly.svg">Weekly Pass Artwork</option>
                            <option value="/assets/images/pass-monthly.svg">Monthly Pass Artwork</option>
                            <option value="/assets/images/evo-token.svg">Evo Gun Token Artwork</option>
                        </select>
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1">Status</label>
                <select name="status" class="w-full px-3.5 py-2.5 rounded-xl bg-gaming-900 border border-gaming-border focus:border-red-500 text-sm text-white focus:outline-none">
                    <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Active (Visible on Store)</option>
                    <option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>Inactive (Hidden)</option>
                </select>
            </div>

            <div class="pt-4 flex items-center justify-end gap-3">
                <a href="/admin/products.php" class="px-5 py-2.5 rounded-xl bg-gaming-800 text-zinc-400 hover:text-white text-xs font-semibold">
                    Cancel
                </a>
                <button type="submit" class="btn-gaming-red text-white font-gaming text-sm font-bold px-6 py-2.5 rounded-xl shadow-red-glow">
                    CREATE PRODUCT &rarr;
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
