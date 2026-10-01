<?php
$page_title = "Store Settings";
require_once __DIR__ . '/includes/admin_header.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validate();

    $settings_to_update = [
        'site_name' => sanitize($_POST['site_name'] ?? 'FireZone Store'),
        'site_tagline' => sanitize($_POST['site_tagline'] ?? ''),
        'currency_symbol' => sanitize($_POST['currency_symbol'] ?? '$'),
        'currency_code' => sanitize($_POST['currency_code'] ?? 'USD'),
        'site_status' => sanitize($_POST['site_status'] ?? 'online'),
        'support_email' => sanitize($_POST['support_email'] ?? ''),
        'support_whatsapp' => sanitize($_POST['support_whatsapp'] ?? ''),
        'support_hours' => sanitize($_POST['support_hours'] ?? ''),
        'deposit_instructions' => trim($_POST['deposit_instructions'] ?? ''),
        'announcement' => trim($_POST['announcement'] ?? '')
    ];

    if (empty($settings_to_update['site_name'])) {
        $errors[] = "Store Name cannot be empty.";
    }

    if (empty($errors)) {
        foreach ($settings_to_update as $key => $val) {
            update_setting($key, $val);
        }
        set_flash('success', "Store configuration updated successfully in MySQL!");
        header("Location: /admin/settings.php");
        exit;
    }
}

$site_name = get_setting('site_name', 'FireZone Store');
$site_tagline = get_setting('site_tagline', 'Instant Free Fire Diamonds & Gaming Top-Ups');
$currency_symbol = get_setting('currency_symbol', '$');
$currency_code = get_setting('currency_code', 'USD');
$site_status = get_setting('site_status', 'online');
$support_email = get_setting('support_email', 'support@firezonestore.com');
$support_whatsapp = get_setting('support_whatsapp', '+1 555 374 8391');
$support_hours = get_setting('support_hours', '24/7 Gamer Care');
$deposit_instructions = get_setting('deposit_instructions', '');
$announcement = get_setting('announcement', '');
?>

<div class="max-w-4xl mx-auto space-y-6">
    <div class="flex items-center justify-between pb-4 border-b border-gaming-border">
        <div>
            <h1 class="font-gaming text-2xl font-bold text-white tracking-wide flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-red-500"></span> STORE CONFIGURATION & SETTINGS
            </h1>
            <p class="text-xs text-zinc-400 mt-1">Changes are saved to MySQL and immediately reflected across the store</p>
        </div>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="p-4 rounded-xl bg-red-950/70 border border-red-500/60 text-red-200 text-xs space-y-1">
            <?php foreach ($errors as $err): ?>
                <p>• <?php echo e($err); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="bg-gaming-850 border border-gaming-border rounded-2xl p-6 sm:p-8 shadow-2xl">
        <form method="POST" action="/admin/settings.php" class="space-y-6">
            <?php echo csrf_field(); ?>

            <!-- General Settings -->
            <div class="space-y-4">
                <h3 class="font-gaming text-sm font-bold text-red-400 uppercase tracking-wider flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-red-500"></span> 1. Branding & Display
                </h3>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1">Store Name *</label>
                        <input type="text" name="site_name" value="<?php echo e($site_name); ?>" required
                               class="w-full px-3.5 py-2.5 rounded-xl bg-gaming-900 border border-gaming-border focus:border-red-500 text-sm text-white focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1">Store Slogan / Tagline</label>
                        <input type="text" name="site_tagline" value="<?php echo e($site_tagline); ?>"
                               class="w-full px-3.5 py-2.5 rounded-xl bg-gaming-900 border border-gaming-border focus:border-red-500 text-sm text-white focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1">Currency Symbol</label>
                        <input type="text" name="currency_symbol" value="<?php echo e($currency_symbol); ?>" required
                               class="w-full px-3.5 py-2.5 rounded-xl bg-gaming-900 border border-gaming-border focus:border-red-500 text-sm text-white focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1">Store Online Status</label>
                        <select name="site_status" class="w-full px-3.5 py-2.5 rounded-xl bg-gaming-900 border border-gaming-border focus:border-red-500 text-sm text-white focus:outline-none">
                            <option value="online" <?php echo $site_status === 'online' ? 'selected' : ''; ?>>Online (Active)</option>
                            <option value="maintenance" <?php echo $site_status === 'maintenance' ? 'selected' : ''; ?>>Maintenance Mode</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Top Banner Announcement -->
            <div class="space-y-2 pt-4 border-t border-gaming-border">
                <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider">Top Banner Announcement</label>
                <input type="text" name="announcement" value="<?php echo e($announcement); ?>"
                       class="w-full px-3.5 py-2.5 rounded-xl bg-gaming-900 border border-gaming-border focus:border-red-500 text-sm text-white focus:outline-none"
                       placeholder="e.g. FLASH SALE: 10% Extra Diamonds on all packs this weekend!">
                <span class="text-[10px] text-zinc-500">Displayed at the very top of all customer pages. Leave blank to hide.</span>
            </div>

            <!-- Contact Information -->
            <div class="space-y-4 pt-4 border-t border-gaming-border">
                <h3 class="font-gaming text-sm font-bold text-red-400 uppercase tracking-wider flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-red-500"></span> 2. Support & Contact Info
                </h3>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1">Support Email</label>
                        <input type="email" name="support_email" value="<?php echo e($support_email); ?>"
                               class="w-full px-3.5 py-2.5 rounded-xl bg-gaming-900 border border-gaming-border focus:border-red-500 text-sm text-white focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1">WhatsApp / Hotline</label>
                        <input type="text" name="support_whatsapp" value="<?php echo e($support_whatsapp); ?>"
                               class="w-full px-3.5 py-2.5 rounded-xl bg-gaming-900 border border-gaming-border focus:border-red-500 text-sm text-white focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1">Operating Hours</label>
                        <input type="text" name="support_hours" value="<?php echo e($support_hours); ?>"
                               class="w-full px-3.5 py-2.5 rounded-xl bg-gaming-900 border border-gaming-border focus:border-red-500 text-sm text-white focus:outline-none">
                    </div>
                </div>
            </div>

            <!-- Payment & Deposit Instructions -->
            <div class="space-y-2 pt-4 border-t border-gaming-border">
                <h3 class="font-gaming text-sm font-bold text-red-400 uppercase tracking-wider flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-red-500"></span> 3. Payment & Deposit Instructions
                </h3>
                <textarea name="deposit_instructions" rows="4"
                          class="w-full px-3.5 py-2.5 rounded-xl bg-gaming-900 border border-gaming-border focus:border-red-500 text-xs text-white focus:outline-none font-mono leading-relaxed"><?php echo e($deposit_instructions); ?></textarea>
                <span class="text-[10px] text-zinc-500">These instructions appear in checkout and wallet "Add Money" screens.</span>
            </div>

            <div class="pt-4 border-t border-gaming-border flex justify-end">
                <button type="submit" class="btn-gaming-red text-white font-gaming text-sm font-bold px-8 py-3 rounded-xl shadow-red-glow">
                    SAVE CONFIGURATION &rarr;
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
